<?php

namespace App\Services\Kahya\Dis;

use App\Contracts\AiProvider;
use App\Models\KahyaHarcamasi;
use App\Models\TelegramSohbeti;
use App\Models\TemsilcilikIslemi;
use App\Models\YasamKonuIcerigi;
use App\Support\HassasKonuBekcisi;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kâhya'nın Telegram grubuna açılan kulağı ve ağzı — tasarım kararı
 * (2026-09-11): "üçüncü taraf gruplara girip reklam gibi görünmek yerine
 * kendi grubumuzu kurup Kâhya'yı normal bir üye gibi katılımcı yap."
 *
 * ---------------------------------------------------------------------------
 * BİLEREK KahyaAjani KULLANILMIYOR
 *
 * Panel içi "Kâhya ile Konuş" (KahyaAjani) araç-çağıran (tool-calling) bir
 * ajan — ülke ekle, ayar doldur, görev aç gibi ADMİN eylemlerine erişebiliyor.
 * Telegram grubundaki mesaj GÜVENİLMEYEN bir kaynak (herhangi biri yazabilir);
 * bu girdiyi admin eylemlerine erişimi olan bir ajana vermek bir prompt-
 * injection'ın "ülke durumunu değiştir" gibi bir eylemi tetiklemesine kapı
 * açardı. Bu yüzden burada AYRI, araçsız, salt-metin bir AiProvider çağrısı
 * kullanılır (ErisimMesajiYazari/TurkishBusinessDetector ile aynı aile) —
 * aynı bilgi tabanını (Ülke Rehberi) okur ama hiçbir eylemi TETİKLEYEMEZ.
 *
 * ---------------------------------------------------------------------------
 * NEDEN "El Kitabı" DEĞİL "Ülke Rehberi"
 *
 * Kâhya'nın panel içi RehberOku aracı `ElKitabiRehberi`yi okur — bu sahibin
 * KENDİ operasyon kılavuzu (yedekleme, kurtarma...), ziyaretçiye gösterilecek
 * bir şey değil. Telegram grubunun ihtiyacı farklı: YAYINDAki (doğrulanmış)
 * TemsilcilikIslemi + YasamKonuIcerigi kayıtları — bkz. {@see rehberBaglami}.
 *
 * ---------------------------------------------------------------------------
 * ÇİFT SAVUNMA HATTI (limit + ucuz ön-eleme)
 *
 * WebAramasi/IsletmeKesfi'deki "her çağrı deftere yazılır, aylık limite
 * tabidir" ilkesi burada da geçerli — canlı bir grup (yüzlerce eşzamanlı
 * kişi) tek kullanıcılı admin sohbetinden çok farklı bir hacim riski taşır.
 * Buna ek olarak her mesaj AI'a gitmez: {@see soruGibiMi} önce ucuz bir
 * sezgiyle gerçek bir soruyu ayıklar (TurkishBusinessDetector'daki
 * "önce deterministik ön-eleme" ilkesiyle aynı) — sıradan sohbete cevap
 * vermemek hem bütçeyi hem "her yerde konuşan bot" izlenimini korur.
 */
class TelegramDinleyici
{
    public const KAYNAK = 'telegram-sohbet';

    public function __construct(private readonly AiProvider $ai) {}

    public function hazirMi(): bool
    {
        return $this->aktif() && $this->token() !== '' && $this->izinliGrupId() !== '';
    }

    private function aktif(): bool
    {
        return filter_var(Settings::get('kahya.telegram.aktif', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    /** Bu ay AI ile cevaplanmış mesaj sayısı — limit kapısının okuduğu sayı. */
    public function buAykiKullanim(): int
    {
        return KahyaHarcamasi::query()
            ->where('kaynak', self::KAYNAK)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function aylikLimit(): int
    {
        return max(0, (int) (Settings::get('kahya.telegram.aylik_mesaj_limiti') ?: config('kahya.telegram.aylik_mesaj_limiti', 600)));
    }

    /**
     * Telegram webhook'undan gelen TEK bir "update" nesnesini işler.
     *
     * Hatanın kuyruk işini KIRMAMASI bilerek: bozuk/beklenmedik bir Telegram
     * gövdesi (yeni bir alan, bir sticker, bir grup fotoğrafı güncellemesi)
     * tüm işlemeyi durdurmamalı — yalnız loglanır.
     *
     * @param  array<string, mixed>  $update
     */
    public function gelenGuncelleme(array $update): void
    {
        try {
            $this->isle($update);
        } catch (\Throwable $e) {
            Log::warning('Kâhya Telegram: güncelleme işlenemedi', ['sebep' => $e->getMessage()]);
        }
    }

    /** @param  array<string, mixed>  $update */
    private function isle(array $update): void
    {
        if (! $this->hazirMi()) {
            return;
        }

        $message = $update['message'] ?? null;
        if (! is_array($message)) {
            return;
        }

        $metin = trim((string) ($message['text'] ?? ''));
        if ($metin === '') {
            return; // Fotoğraf/sticker/ses — cevaplanacak bir metin yok.
        }

        $chat = is_array($message['chat'] ?? null) ? $message['chat'] : [];
        $chatId = (string) ($chat['id'] ?? '');
        $chatType = (string) ($chat['type'] ?? '');

        if ($chatId === '') {
            return;
        }

        $from = is_array($message['from'] ?? null) ? $message['from'] : [];
        $kullaniciId = (string) ($from['id'] ?? '');
        $kullaniciAdi = (string) ($from['username'] ?? $from['first_name'] ?? '');

        if ($chatType === 'private') {
            $this->ozelMesajiIsle($chatId, $kullaniciId, $kullaniciAdi, $metin);

            return;
        }

        // Grup dışı (kanal vb.) ya da izinli olmayan bir gruba bilerek
        // eklenmiş olabiliriz — YALNIZ pilot grupta konuşur.
        if ($chatId !== $this->izinliGrupId()) {
            return;
        }

        if (! $this->soruGibiMi($metin)) {
            return;
        }

        if ($this->buAykiKullanim() >= $this->aylikLimit()) {
            Log::info('Kâhya Telegram: aylık mesaj limiti doldu, cevap verilmedi.');

            return;
        }

        $this->soruyuCevapla($chatId, $kullaniciId, $kullaniciAdi, $metin, grupIcinde: true);
    }

    private function ozelMesajiIsle(string $chatId, string $kullaniciId, string $kullaniciAdi, string $metin): void
    {
        if (str_starts_with($metin, '/start')) {
            $this->gonder($chatId, "Merhaba! Ben {$this->personaAdi()}. Sana nasıl yardımcı olabilirim?");

            return;
        }

        // DM zaten kişinin kendi isteğiyle (botu başlatmasıyla) açılmış bir
        // kanal — grup tarafındaki "soru gibi mi" ön-elemesi burada aranmaz.
        $this->soruyuCevapla($chatId, $kullaniciId, $kullaniciAdi, $metin, grupIcinde: false);
    }

    private function soruyuCevapla(string $chatId, string $kullaniciId, string $kullaniciAdi, string $soru, bool $grupIcinde): void
    {
        $sonuc = $this->ai->analyzeText($this->buildPrompt($soru), [
            'type' => 'object',
            'properties' => [
                'cevap' => ['type' => 'string'],
                'ihtiyac' => ['type' => ['string', 'null']],
            ],
            'required' => ['cevap', 'ihtiyac'],
            'additionalProperties' => false,
        ], 20);

        $cevap = trim((string) ($sonuc['cevap'] ?? ''));
        if ($cevap === '') {
            // AI yapılandırılmamış/başarısız — sessiz kal. Genel bir gruba
            // hata mesajı düşürmek sessiz kalmaktan daha kötü bir izlenimdir.
            return;
        }

        $ihtiyac = trim((string) ($sonuc['ihtiyac'] ?? ''));

        $bekci = app(HassasKonuBekcisi::class);
        $kategori = $bekci->tespit($soru) ?? $bekci->tespit($cevap);
        if ($kategori !== null) {
            $cevap .= $bekci->uyariNotu($kategori);
        }

        // Yalnız GRUPTA ve yalnız net bir ihtiyaç belirtildiğinde tek satır
        // doğal davet + DM linki eklenir (tasarım kararı: "grupta ısrarcı
        // görünme, tıklama kişinin kendi tercihi olsun").
        if ($grupIcinde && $ihtiyac !== '' && $this->botKullaniciAdi() !== '') {
            $cevap .= "\n\n💬 Bunu birebir konuşalım, buradan yaz: https://t.me/{$this->botKullaniciAdi()}";
        }

        $this->gonder($chatId, $cevap);

        TelegramSohbeti::create([
            'telegram_chat_id' => $chatId,
            'telegram_kullanici_id' => $kullaniciId ?: null,
            'telegram_kullanici_adi' => $kullaniciAdi ?: null,
            'soru_metni' => $soru,
            'cevap_metni' => $cevap,
            'kategori' => $kategori,
            'needs_review' => $kategori !== null,
        ]);

        KahyaHarcamasi::create([
            'kaynak' => self::KAYNAK,
            'saglayici' => $this->ai->name(),
            'model' => (string) config('ai.providers.'.config('ai.default').'.model', ''),
            'detay' => mb_substr($soru, 0, 200),
        ]);
    }

    /**
     * Ucuz, deterministik "bu gerçek bir soru mu" sezgisi — her grup
     * mesajına AI çağırmamak için (TurkishBusinessDetector'daki "önce
     * deterministik ön-eleme" ilkesiyle aynı).
     */
    private function soruGibiMi(string $metin): bool
    {
        if (str_contains($metin, '?')) {
            return true;
        }

        $folded = mb_strtolower($metin, 'UTF-8');
        $ipuclari = ['nasıl', 'nerede', 'ne zaman', 'kaç ', 'hangi', 'var mı', 'biliyor musunuz', 'yardımcı olur musunuz', 'yardım'];

        foreach ($ipuclari as $ipucu) {
            if (str_contains($folded, $ipucu)) {
                return true;
            }
        }

        return false;
    }

    /**
     * YAYINDAki Ülke Rehberi içeriğini modele bağlam olarak verir — model
     * bunun dışına çıkıp uydurmamalı diye yönergede açıkça istenir.
     */
    private function rehberBaglami(): string
    {
        $ulke = $this->ulkeKodu();

        $islemler = TemsilcilikIslemi::query()
            ->whereHas('temsilcilik', fn ($q) => $q->where('country_code', $ulke))
            ->yayinda()
            ->with('islemTuru')
            ->get()
            ->map(function (TemsilcilikIslemi $i): string {
                $evraklar = collect($i->evraklar)->pluck('ad')->implode(', ') ?: '?';

                return sprintf(
                    '- %s: evraklar: %s; süre %s; ücret %s. %s',
                    $i->islemTuru->ad ?? '?',
                    $evraklar,
                    $i->sure_metni ?: '?',
                    $i->ucret_metni ?: '?',
                    mb_substr((string) $i->notlar, 0, 200)
                );
            })
            ->implode("\n");

        $konular = YasamKonuIcerigi::query()
            ->where('country_code', $ulke)
            ->yayinda()
            ->with('konu')
            ->get()
            ->map(function (YasamKonuIcerigi $k): string {
                $metin = collect($k->icerik ?? [])->pluck('metin')->implode(' ');

                return sprintf('- %s: %s', $k->konu->baslik ?? '?', mb_substr($metin, 0, 300));
            })
            ->implode("\n");

        return mb_substr("KONSOLOSLUK/RESMİ İŞLEMLER:\n{$islemler}\n\nYAŞAM REHBERİ:\n{$konular}", 0, 6000);
    }

    private function buildPrompt(string $soru): string
    {
        return <<<PROMPT
        Sen {$this->personaAdi()}, Nisoya (nisoya.com — yurtdışındaki Türkler
        için ücretsiz bir topluluk/pazaryeri) adına bir Telegram grubunda
        normal bir üye gibi sohbete katılan bir yardımcısın. Grup, o ülkede
        yaşayan Türklerden oluşuyor.

        KESİN KURALLAR:
        1. Doğal ve sıcak konuş — reklam dili, satış cümlesi, link YOK
           (link ayrıca eklenecek, sen ekleme).
        2. Aşağıdaki DOĞRULANMIŞ bilgiyi kullanabilirsin; orada olmayan bir
           konuda (özellikle vize/hukuk/tarih/tutar gibi kesin bilgi
           gerektiren şeylerde) UYDURMA — "emin değilim, resmî kaynağa bak"
           de.
        3. Kişi net bir ihtiyaç belirtiyorsa (iş arıyorum, ev arıyorum,
           hizmet arıyorum/veriyorum) "ihtiyac" alanına kısaca ne olduğunu
           yaz (örn. "ev arıyor"); yoksa null bırak.

        DOĞRULANMIŞ BİLGİ:
        {$this->rehberBaglami()}

        SADECE şu JSON'u döndür: {"cevap": "...", "ihtiyac": "..." ya da null}

        Soru: {$soru}
        PROMPT;
    }

    private function gonder(string $chatId, string $metin): void
    {
        try {
            Http::timeout(15)->post("https://api.telegram.org/bot{$this->token()}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $metin,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Kâhya Telegram: mesaj gönderilemedi', ['sebep' => $e->getMessage()]);
        }
    }

    private function token(): string
    {
        return trim((string) Settings::get('kahya.telegram.bot_token', config('kahya.telegram.bot_token', '')));
    }

    private function webhookSirri(): string
    {
        return trim((string) Settings::get('kahya.telegram.webhook_sirri', config('kahya.telegram.webhook_sirri', '')));
    }

    public function dogruSir(?string $gelenSir): bool
    {
        $sir = $this->webhookSirri();

        return $sir !== '' && hash_equals($sir, (string) $gelenSir);
    }

    private function izinliGrupId(): string
    {
        return trim((string) Settings::get('kahya.telegram.izinli_grup_id', ''));
    }

    private function ulkeKodu(): string
    {
        return strtoupper(trim((string) Settings::get('kahya.telegram.ulke_kodu', config('kahya.telegram.ulke_kodu', 'RU'))));
    }

    private function personaAdi(): string
    {
        return trim((string) Settings::get('kahya.telegram.persona_adi', '')) ?: 'Nisoya Kâhyası';
    }

    private function botKullaniciAdi(): string
    {
        return trim((string) Settings::get('kahya.telegram.bot_kullanici_adi', ''));
    }
}
