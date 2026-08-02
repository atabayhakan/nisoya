<?php

namespace App\Services\Kahya\Sohbet;

use App\Ai\Kahya\EylemToplayici;
use App\Ai\Kahya\KahyaAjani;
use App\Ai\Kahya\YonlendirmeToplayici;
use App\Models\KahyaEylemKaydi;
use App\Models\KahyaHarcamasi;
use App\Models\KahyaMesaji;
use App\Models\User;
use App\Services\Kahya\BekleyenIsler;
use App\Services\Kahya\Eylem\EylemCalistirici;
use App\Services\Kahya\Eylem\EylemKatalogu;
use App\Services\Kahya\KahyaTeshisi;
use App\Services\Kahya\PanelHaritasi;
use App\Services\Kahya\PanelHedefi;
use App\Support\Settings;
use Illuminate\Support\Collection;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

/**
 * Sahibin Kâhya ile konuştuğu yer — F0 "beyin nakli" sonrası hâli.
 *
 * ---------------------------------------------------------------------------
 * ESKİ MODEL (F1) → YENİ MODEL (F0/Kâhya 2.0)
 *
 * F1: tek AI çağrısı, JSON çıktı ({cevap, eylem, parametreler}), tek eylem.
 * Model bilmediği bir id gerektiğinde "sisteme bakamıyorum" derdi.
 *
 * Şimdi: {@see KahyaAjani} bir araç döngüsünde koşar — önce bakar
 * (tablo-sorgula), sonra yapar (eylem araçları), sonucu görür, cevabını yazar.
 * JSON sözleşmesi ve onun "json kelimesi"/"geçersiz JSON" hata sınıfı öldü;
 * araçlar sağlayıcının native tool-calling API'siyle akar.
 *
 * ---------------------------------------------------------------------------
 * MODELİN CÜMLESİ İLE OLAN BİTEN HÂLÂ AYRI TUTULUR
 *
 * F1 ilkesi ("modelin 'ekledim' demesi bir şeyin eklendiği anlamına gelmez")
 * biçim değiştirdi ama ölmedi: araçlar modele yalnız GERÇEK durumu döndürür
 * ("BAŞARILI: ..." / "ONAY BEKLİYOR: ..." / "HATA: ...") ve arayüzdeki
 * geri-al/onay düğmeleri modelin metnine değil {@see EylemToplayici}'daki
 * kayıtların durumuna bağlanır.
 */
class KahyaSohbeti
{
    /** Sohbet bağlamına alınacak son mesaj sayısı. */
    private const GECMIS_SINIRI = 12;

    public function __construct(
        private readonly EylemKatalogu $katalog,
        private readonly EylemCalistirici $calistirici,
        private readonly KahyaTeshisi $teshis,
        private readonly BekleyenIsler $bekleyen,
        private readonly PanelHaritasi $harita,
        private readonly EylemToplayici $toplayici,
        private readonly YonlendirmeToplayici $yonlendirici,
    ) {}

    /**
     * Panel açıldığında gösterilen karşılama.
     *
     * SAHİP BİRDEN ÇOK PROJEYLE ÇALIŞIYOR. Buraya girdiğinde ihtiyacı olan şey
     * gösterge tablosu değil, "bugün senden ne bekleniyor" cümlesidir. Bu
     * yüzden karşılama KISA ve EYLEM ODAKLI: en fazla üç madde. AI ÇAĞRILMAZ.
     */
    public function karsila(User $sahip): string
    {
        $kuyruklar = $this->bekleyen->topla();
        $envanter = $this->teshis->gercekEnvanter();
        $sonKonusma = $this->sonKonusmaOzeti();

        $ad = trim(explode(' ', trim($sahip->name))[0] ?? '');
        $selam = $this->gunAralikSelami().($ad !== '' ? ' '.$ad : '');

        if ($kuyruklar === []) {
            $satir = $envanter['uyari'] !== null
                ? 'Seni bekleyen iş yok. Ama şunu hatırlatayım: '.lcfirst($envanter['uyari'])
                : 'Seni bekleyen iş yok, her şey yolunda görünüyor.';

            return $selam.'. '.$satir.$sonKonusma;
        }

        // Aciliyeti yüksek olanlar önce; sahip listenin ilk satırını okuyup
        // bırakırsa bile doğru işi görmüş olsun.
        usort($kuyruklar, fn (array $a, array $b): int => ($b['aciliyet'] === 'yuksek' ? 1 : 0) <=> ($a['aciliyet'] === 'yuksek' ? 1 : 0));

        $maddeler = array_map(
            fn (array $k): string => "· {$k['etiket']}: {$k['adet']}",
            array_slice($kuyruklar, 0, 3),
        );

        $kalan = count($kuyruklar) - 3;
        $ek = $kalan > 0 ? "\n· (+{$kalan} kuyruk daha)" : '';

        return $selam.". Bugün seni bekleyenler:\n".implode("\n", $maddeler).$ek.$sonKonusma;
    }

    /**
     * Sahibin mesajını işler: ajan döngüsünü koşturur, yanıt döndürür.
     *
     * @param  array{yol: string, ad: string, tipi: ?string}|null  $ek  Sohbete
     *                                                                  eklenen dosya (varsa). Yalnızca SAKLANIR/GÖSTERİLİR — model
     *                                                                  metin tabanlı çalıştığı için içeriğini GÖREMEZ, promptta yalnız
     *                                                                  bir dosyanın eklendiği bilgisi geçer (bkz. aşağıdaki $promptMetni).
     */
    public function sor(string $mesaj, User $sahip, ?array $ek = null): SohbetYaniti
    {
        $mesaj = trim($mesaj);

        if ($mesaj === '' && $ek === null) {
            return new SohbetYaniti('Bir şey yazmadın.');
        }

        /*
         * Geçmiş, şu anki mesaj KAYDEDİLMEDEN önce çekilir: prompt zaten bu
         * mesajı taşıyor; geçmişte de görünürse model aynı cümleyi iki kez
         * okur ve "az önce sordun" diye şaşırır.
         */
        $gecmis = KahyaMesaji::query()
            ->latest('id')
            ->limit(self::GECMIS_SINIRI)
            ->get()
            ->reverse()
            ->values();

        KahyaMesaji::create([
            'rol' => KahyaMesaji::ROL_SAHIP,
            'metin' => $mesaj,
            'user_id' => $sahip->id,
            'ek_yolu' => $ek['yol'] ?? null,
            'ek_ad' => $ek['ad'] ?? null,
            'ek_tipi' => $ek['tipi'] ?? null,
        ]);

        // Uzun ömürlü süreçlerde (kuyruk işçisi) önceki turun kayıtları sızmasın.
        $this->toplayici->sifirla();
        $this->yonlendirici->sifirla();

        $saglayici = $this->saglayiciAdi();
        $model = $this->modelAdi($saglayici);

        // Model metin tabanlı — eki GÖREMEZ. Görmediği bir şeyi görmüş gibi
        // yapmasındansa ("harika bir görsel!") ekin varlığını bilip sahibe
        // sorması daha dürüst: bkz. bu sınıfın F1 ilkesi (üstteki yorum).
        $promptMetni = $mesaj.($ek !== null
            ? "\n\n[Sahip bir dosya ekledi: {$ek['ad']}. İçeriğini göremiyorsun — gerekirse ne olduğunu sor.]"
            : '');

        try {
            $yanit = $this->ajan($gecmis, $sahip)->prompt(
                $promptMetni,
                provider: $saglayici,
                model: $model,
                // Araç döngüsü tek çağrıdan uzun sürer; sağlayıcı varsayılanı
                // (30-60s) çok turlu bir işte yarıda kesebilir.
                timeout: 120,
            );
        } catch (Throwable $e) {
            report($e);

            return $this->yanitla(
                'Şu an düşünemiyorum — yapay zekâ sağlayıcısına ulaşılamadı. '.
                'Yönetim panelindeki Yapay Zekâ Ayarları sayfasından anahtarı ve modeli kontrol edebilirsin.',
                $sahip,
            );
        }

        $this->harcamaYaz($yanit, $saglayici, $model);

        $kayit = $this->toplayici->son();
        $metin = trim($yanit->text);

        /*
         * Model konuşmadan işi yapıp susarsa (nadir ama olur) gerçek durumu
         * defterden anlat — sessiz başarı, sahibin gözünde başarısızlıktır.
         */
        if ($metin === '') {
            $metin = $kayit !== null
                ? $this->eylemMetni('', $kayit)
                : 'Bunu anlayamadım, başka türlü sorar mısın?';
        }

        return $this->yanitla($metin, $sahip, $kayit, $this->yonlendirici->son());
    }

    /** Bekleyen bir eylemi onaylar ve sonucu sohbete yazar. */
    public function onayla(KahyaEylemKaydi $kayit, User $sahip): SohbetYaniti
    {
        $kayit = $this->calistirici->onayla($kayit);

        return $this->yanitla($this->eylemMetni('', $kayit), $sahip, $kayit);
    }

    /** Uygulanmış bir eylemi geri alır. */
    public function geriAl(KahyaEylemKaydi $kayit, User $sahip): SohbetYaniti
    {
        $kayit = $this->calistirici->geriAl($kayit);

        return $this->yanitla('Geri aldım. '.($kayit->sonuc ?? ''), $sahip, $kayit);
    }

    // ------------------------------------------------------------- İçeriden

    /** @param  Collection<int, KahyaMesaji>  $gecmis */
    protected function ajan($gecmis, User $sahip): KahyaAjani
    {
        return new KahyaAjani(
            $this->teshis,
            $this->bekleyen,
            $this->harita,
            $this->katalog,
            $this->calistirici,
            $this->toplayici,
            $this->yonlendirici,
            $gecmis,
            $sahip,
        );
    }

    private function saglayiciAdi(): string
    {
        return trim((string) Settings::get('ai.saglayici', '')) ?: (string) config('ai.default');
    }

    /**
     * Model önceliği: Kâhya'ya özel model > genel AI modeli > sağlayıcı
     * varsayılanı. Ayrı ayar var çünkü eylem seçiminin hata bedeli canlıda
     * ödenir — sahip isterse sohbete daha güçlü bir model atar.
     */
    private function modelAdi(string $saglayici): string
    {
        return trim((string) Settings::get('kahya.sohbet_modeli', ''))
            ?: (trim((string) Settings::get('ai.model', ''))
            ?: (string) config("ai.providers.{$saglayici}.model"));
    }

    /**
     * Harcamayı deftere işle — defter yazımı sohbeti asla KIRMAZ.
     */
    private function harcamaYaz(AgentResponse $yanit, string $saglayici, string $model): void
    {
        rescue(fn () => KahyaHarcamasi::kaydet('sohbet', $saglayici, $model, $yanit->usage), report: true);
    }

    /**
     * Karşılamaya eklenen "geçen sefer ne konuşmuştuk" hatırlatması.
     *
     * Sahibin kendi ifadesiyle: birden çok projeyle çalışıyor ve hafızasında
     * bazı şeyler kalmıyor. Kâhya'nın en somut faydası bu satır.
     */
    private function sonKonusmaOzeti(): string
    {
        $son = KahyaMesaji::query()
            ->where('rol', KahyaMesaji::ROL_SAHIP)
            ->latest('id')
            ->first();

        if ($son === null || $son->created_at === null) {
            return '';
        }

        $ne = mb_strimwidth($son->metin, 0, 70, '…');

        return "\n\nGeçen sefer ({$son->created_at->diffForHumans()}) şunu sormuştun: “{$ne}”";
    }

    private function gunAralikSelami(): string
    {
        return match (true) {
            now()->hour < 6 => 'İyi geceler',
            now()->hour < 12 => 'Günaydın',
            now()->hour < 18 => 'İyi günler',
            default => 'İyi akşamlar',
        };
    }

    private function eylemMetni(string $cevap, KahyaEylemKaydi $kayit): string
    {
        $on = $cevap !== '' ? $cevap."\n\n" : '';

        return $on.match ($kayit->durum) {
            KahyaEylemKaydi::DURUM_UYGULANDI => '✅ '.$kayit->sonuc.' (İstersen geri alabilirim.)',
            KahyaEylemKaydi::DURUM_BEKLEMEDE => '⏸ Bu işi onayına sunuyorum — '.$kayit->onizleme,
            KahyaEylemKaydi::DURUM_HATA => '⚠️ Yapamadım: '.$kayit->hata,
            default => $kayit->onizleme,
        };
    }

    private function yanitla(string $metin, User $sahip, ?KahyaEylemKaydi $kayit = null, ?PanelHedefi $hedef = null): SohbetYaniti
    {
        KahyaMesaji::create([
            'rol' => KahyaMesaji::ROL_KAHYA,
            'metin' => $metin,
            'kahya_eylemi_id' => $kayit?->id,
            'user_id' => $sahip->id,
            // Hedef mesaja da yazılır: "Aç" düğmesi sohbet geçmişinde kalıcı —
            // sahip iki gün sonra kaydırıp aynı düğmeden yine gidebilsin.
            'hedef_url' => $hedef?->adres,
            'hedef_etiket' => $hedef?->etiket,
        ]);

        return new SohbetYaniti(
            $metin,
            $kayit,
            $kayit?->durum === KahyaEylemKaydi::DURUM_BEKLEMEDE,
            $hedef,
        );
    }
}
