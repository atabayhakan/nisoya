<?php

namespace App\Services\Kahya\Sohbet;

use App\Contracts\AiProvider;
use App\Models\Category;
use App\Models\KahyaEylemKaydi;
use App\Models\KahyaMesaji;
use App\Models\User;
use App\Services\Ai\AiManager;
use App\Services\Kahya\BekleyenIsler;
use App\Services\Kahya\Eylem\EylemCalistirici;
use App\Services\Kahya\Eylem\EylemKatalogu;
use App\Services\Kahya\KahyaTeshisi;
use App\Services\Kahya\PanelHaritasi;
use App\Support\Settings;
use RuntimeException;
use Throwable;

/**
 * Sahibin Kâhya ile konuştuğu yer.
 *
 * ---------------------------------------------------------------------------
 * YAPAY ZEKÂ NE YAPAR, NE YAPMAZ
 *
 * YAPAR: sahibin Türkçe cümlesini okur, katalogdan bir eylem adı seçer,
 * parametrelerini doldurur, bir de cevap cümlesi yazar.
 *
 * YAPMAZ: veritabanına dokunmaz, SQL yazmaz, kayıt kaydetmez. Çıktısı yalnızca
 * bir addır ve o ad katalogda yoksa hiçbir şey olmaz. Güvenlik sınırı modelin
 * doğru anlamasına değil, {@see EylemKatalogu}'nun kapsamına dayanır.
 *
 * ---------------------------------------------------------------------------
 * MODELİN CÜMLESİ İLE OLAN BİTEN AYRI TUTULUR
 *
 * Modelin "ekledim" demesi bir şeyin eklendiği anlamına gelmez. Bu yüzden
 * yanıt iki parçadır: modelin cümlesi + eylem kaydının GERÇEK sonucu. İkincisi
 * `EylemCalistirici`'den gelir ve modelin ne dediğinden bağımsızdır.
 *
 * Bu ayrım bu sistemdeki en tehlikeli hata sınıfını kapatır: sahip işin
 * yapıldığını sanır, oysa yapılmamıştır.
 *
 * ---------------------------------------------------------------------------
 * İKİ KATMANLI MODEL
 *
 * Karşılama ve özet gibi ucuz işler yapılandırılmış varsayılan modelle;
 * soru yanıtlama ve EYLEM SEÇİMİ güçlü modelle yapılır. Gerekçesi maliyet
 * değil hata bedeli: yanlış eylem seçmenin bedeli canlıda ödenir, birkaç
 * kuruşluk model farkından çok daha pahalıdır.
 *
 * Güçlü model ayarı boşsa varsayılana düşülür — yanlış bir model adı yüzünden
 * Kâhya'nın hiç cevap verememesindense, ucuz modelle cevap vermesi yeğdir.
 */
class KahyaSohbeti
{
    /** Sohbet bağlamına alınacak son mesaj sayısı. */
    private const GECMIS_SINIRI = 12;

    public function __construct(
        private readonly AiManager $ai,
        private readonly EylemKatalogu $katalog,
        private readonly EylemCalistirici $calistirici,
        private readonly KahyaTeshisi $teshis,
        private readonly BekleyenIsler $bekleyen,
        private readonly PanelHaritasi $harita,
    ) {}

    /**
     * Panel açıldığında gösterilen karşılama.
     *
     * SAHİP BİRDEN ÇOK PROJEYLE ÇALIŞIYOR. Buraya girdiğinde ihtiyacı olan şey
     * gösterge tablosu değil, "bugün senden ne bekleniyor" cümlesidir. Bu
     * yüzden karşılama KISA ve EYLEM ODAKLI: en fazla üç madde.
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
     * Sahibin mesajını işler: gerekiyorsa eylem çalıştırır, yanıt döndürür.
     */
    public function sor(string $mesaj, User $sahip): SohbetYaniti
    {
        $mesaj = trim($mesaj);

        if ($mesaj === '') {
            return new SohbetYaniti('Bir şey yazmadın.');
        }

        KahyaMesaji::create(['rol' => KahyaMesaji::ROL_SAHIP, 'metin' => $mesaj, 'user_id' => $sahip->id]);

        try {
            $karar = $this->karariAl($mesaj);
        } catch (Throwable $e) {
            report($e);

            return $this->yanitla(
                'Şu an düşünemiyorum — yapay zekâ sağlayıcısına ulaşılamadı. '.
                'Yönetim panelindeki Yapay Zekâ Ayarları sayfasından anahtarı ve modeli kontrol edebilirsin.',
                $sahip,
            );
        }

        $cevap = trim((string) ($karar['cevap'] ?? ''));
        $eylemAdi = trim((string) ($karar['eylem'] ?? ''));

        if ($eylemAdi === '') {
            return $this->yanitla($cevap !== '' ? $cevap : 'Bunu anlayamadım, başka türlü sorar mısın?', $sahip);
        }

        try {
            $kayit = $this->calistirici->calistir(
                $eylemAdi,
                is_array($karar['parametreler'] ?? null) ? $karar['parametreler'] : [],
                $sahip,
            );
        } catch (RuntimeException $e) {
            // Katalog dışı bir ad: güvenlik sınırının çalıştığı an. Sahibe
            // dürüstçe söylenir, model "yaptım" demiş olsa bile.
            return $this->yanitla(
                'Bunu yapamıyorum: '.$e->getMessage().' Yapabildiğim işleri sorabilirsin.',
                $sahip,
            );
        }

        return $this->yanitla($this->eylemMetni($cevap, $kayit), $sahip, $kayit);
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

    /**
     * Yapay zekâdan kararı alır.
     *
     * @return array<string, mixed>
     */
    private function karariAl(string $mesaj): array
    {
        $saglayici = $this->gucluSaglayici();

        $sonuc = $saglayici->analyzeText($this->yonerge($mesaj), [
            'type' => 'object',
            'properties' => [
                'cevap' => ['type' => 'string', 'description' => 'Sahibe Türkçe, kısa cevap.'],
                'eylem' => ['type' => 'string', 'description' => 'Katalogdaki eylem adı; iş istenmiyorsa boş bırak.'],
                'parametreler' => ['type' => 'object', 'description' => 'Eylemin parametreleri.'],
            ],
            'required' => ['cevap'],
        ], 60);

        if ($sonuc === null) {
            throw new RuntimeException('Yapay zekâ yanıt vermedi: '.($saglayici->lastError() ?? 'sebep bilinmiyor'));
        }

        return $sonuc;
    }

    private function yonerge(string $mesaj): string
    {
        $teshis = $this->teshis->gercekEnvanter();
        $kuyruklar = $this->bekleyen->topla();
        $gecmis = $this->gecmisMetni();

        $kuyrukMetni = $kuyruklar === []
            ? 'Bekleyen iş yok.'
            : implode("\n", array_map(fn (array $k): string => "- {$k['etiket']}: {$k['adet']}", $kuyruklar));

        return <<<METIN
        Sen "Kâhya"sın: Nisoya'nın (yurtdışındaki Türkler için ücretsiz Türkçe pazaryeri)
        yönetim asistanısın. Sahibiyle Türkçe, kısa ve doğrudan konuşursun. Yağcılık yapmaz,
        gereksiz özet çıkarmazsın.

        ## Sitenin şu anki durumu
        Aktif ilan: {$teshis['ilan']} · Benzersiz satıcı: {$teshis['satici']}
        Bekleyen işler:
        {$kuyrukMetni}

        ## Site kimliği (SEO ve metin yazarken buradan beslen)
        {$this->siteKimligi()}

        ## Yapabildiğin işler
        Aşağıdaki listede OLMAYAN hiçbir işi yapamazsın. Sana veritabanı erişimi verilmedi;
        yalnızca bu adlardan birini seçip parametrelerini doldurabilirsin.

        {$this->katalog->yapayZekaIcin()}

        ## Panel haritası (yol tarifi için)
        Sahip bir ekranın ya da özelliğin NEREDE olduğunu sorarsa buradan cevapla:
        sol menüdeki grup adını, ekran adını ve adresini söyle. Haritada olmayan bir
        yeri tarif etme.

        {$this->harita->metin()}

        ## Kurallar
        1. Sahip bir İŞ istiyorsa `eylem` alanına katalogdaki adı yaz ve `parametreler`i doldur.
        2. Sadece soru soruyorsa `eylem` alanını BOŞ bırak, `cevap`ta yanıtla.
        3. İstenen iş katalogda yoksa uydurma — `eylem`i boş bırak ve yapamadığını söyle;
           iş panelde elle yapılabiliyorsa panel haritasından yerini tarif et.
        4. Parametre için gereken bilgi eksikse iş SEÇME; önce eksik bilgiyi sor.
        5. `cevap` kısa olsun. Bir işi seçtiysen ne yapacağını tek cümlede söyle;
           yaptığını İDDİA ETME — sonucu sistem söyleyecek.
        6. Emin değilsen sor. Yanlış iş yapmak, sormaktan pahalıdır.

        ## Son konuşmalar
        {$gecmis}

        ## Sahibin şu anki mesajı
        {$mesaj}

        ## Yanıt biçimi
        Yanıtını SADECE JSON olarak ver: {"cevap": "...", "eylem": "...", "parametreler": {...}}
        METIN;
    }

    /**
     * Sitenin kimlik kartı: ad, mevcut SEO metinleri, ana kategoriler.
     *
     * `seo-doldur` gibi metin ÜRETEN eylemler için modelin tek malzeme
     * kaynağı burası — site hakkında bilmediği şeyi uydurmak zorunda
     * kalmasın diye kısa ve olgusal tutulur.
     */
    private function siteKimligi(): string
    {
        $ad = (string) Settings::get('genel.site_adi', 'Nisoya');
        $baslik = (string) Settings::get('seo.default_title', '');
        $aciklama = (string) Settings::get('seo.default_description', '');

        $kategoriler = Category::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->pluck('name')
            ->implode(', ');

        return "Site adı: {$ad}\n"
            ."Mevcut SEO başlığı: \"{$baslik}\"\n"
            ."Mevcut SEO açıklaması: \"{$aciklama}\"\n"
            .'Ana kategoriler: '.($kategoriler !== '' ? $kategoriler : '(henüz yok)');
    }

    private function gecmisMetni(): string
    {
        $mesajlar = KahyaMesaji::query()
            ->latest('id')
            ->limit(self::GECMIS_SINIRI)
            ->get()
            ->reverse();

        if ($mesajlar->isEmpty()) {
            return '(Henüz konuşmadınız.)';
        }

        return $mesajlar
            ->map(fn (KahyaMesaji $m): string => ($m->rol === KahyaMesaji::ROL_SAHIP ? 'Sahip: ' : 'Kâhya: ').$m->metin)
            ->implode("\n");
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

    private function yanitla(string $metin, User $sahip, ?KahyaEylemKaydi $kayit = null): SohbetYaniti
    {
        KahyaMesaji::create([
            'rol' => KahyaMesaji::ROL_KAHYA,
            'metin' => $metin,
            'kahya_eylemi_id' => $kayit?->id,
            'user_id' => $sahip->id,
        ]);

        return new SohbetYaniti(
            $metin,
            $kayit,
            $kayit?->durum === KahyaEylemKaydi::DURUM_BEKLEMEDE,
        );
    }

    /**
     * Eylem seçimi için güçlü sağlayıcı.
     *
     * Ayar boşsa varsayılana düşülür: yanlış yazılmış bir model adı yüzünden
     * Kâhya'nın hiç konuşamaması, ucuz modelle konuşmasından kötüdür.
     */
    private function gucluSaglayici(): AiProvider
    {
        $model = trim((string) Settings::get('kahya.sohbet_modeli', ''));
        $saglayiciAdi = trim((string) Settings::get('ai.saglayici', '')) ?: (string) config('ai.default');

        if ($model === '') {
            return $this->ai->provider();
        }

        $yapilandirma = config("ai.providers.{$saglayiciAdi}", []);
        $yapilandirma['model'] = $model;

        return $this->ai->make($saglayiciAdi, $yapilandirma);
    }
}
