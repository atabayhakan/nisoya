<?php

namespace App\Services\Growth;

use App\Contracts\AiProvider;
use App\Models\OutreachTarget;
use App\Support\Growth\RegionPolicy;
use Illuminate\Support\Facades\Log;

/**
 * İşletmeye tanışma postası taslağı.
 *
 * ---------------------------------------------------------------------------
 * AI'A TÜM MESAJ YAZDIRILMIYOR — YALNIZ TEK CÜMLE
 *
 * Bu, bu sınıfın en önemli kararı. Mesajın gövdesi `docs/10-elle-erisim-
 * listesi.md`'deki şablondan PHP tarafında kuruluyor; modelden istenen tek
 * şey, o şablondaki köşeli parantezi dolduran KİŞİSEL CÜMLE.
 *
 * Üç sebebi var:
 *
 *   1. DEĞİŞMEZ KISIMLAR DEĞİŞMESİN. "Komisyon yok, üyelik ücreti yok,
 *      ödemeye aracılık etmiyoruz" cümlesi platformun temel vaadi. Modele
 *      yazdırılsaydı her seferinde biraz farklı, bazen fazla iddialı çıkardı.
 *   2. UYDURMA TRAFİK VAADİNİN ÖNÜ KESİLSİN. Şablonun kendi dürüstlük notu
 *      şunu söylüyor: platform yeni, ilan sayısı az, "binlerce müşteri"
 *      denmez. Tutulamayacak vaat ilk dönüşte anlaşılır ve o işletmeyi
 *      temelli kaybettirir.
 *   3. UCUZ. Tek cümle üretmek tüm mektubu üretmekten çok daha az jeton.
 *
 * ---------------------------------------------------------------------------
 * KİŞİSEL CÜMLE YALNIZ BİLİNENDEN KURULUR
 *
 * Modelin bu işletme hakkında bildiği tek şey veritabanındaki alanlar: ad,
 * şehir, ülke, sektör, kategori, web sitesi. Menüsünü, kaç yıldır açık
 * olduğunu, mahallesini BİLMİYOR.
 *
 * Bu yüzden prompt "elindekiyle yaz, yoksa boş bırak" diyor. Elde yeterli
 * bilgi yoksa `null` döner ve sahip o cümleyi kendisi yazar — uydurulmuş bir
 * "mozaik lamba atölyeniz" cümlesi, hiç cümle olmamasından çok daha kötüdür:
 * karşı taraf yanlışı fark eder ve mektubun tamamı güvenilirliğini kaybeder.
 *
 * ---------------------------------------------------------------------------
 * GÖNDERMEZ
 *
 * Bu sınıf taslak üretir, hiçbir şey göndermez. Gönderim kararı ve eylemi
 * sahibindir. (AWS üretim erişimi reddedildikten sonra soğuk e-posta
 * otomasyonu zaten bırakılmıştı; elle erişim tek kanal.)
 */
class ErisimMesajiYazari
{
    public function __construct(private readonly AiProvider $ai) {}

    public function isEnabled(): bool
    {
        return (bool) config('ai.features.outreach_draft') && $this->ai->isConfigured();
    }

    /**
     * Bu aday için taslak üretilebilir mi?
     *
     * HUKUKİ KAPI BURADA DA GEÇERLİ: yalnız gönderime açık bölgedeki Türk
     * işletmeler. Kapatılmış bir aday için taslak üretmek, o kapının varlık
     * sebebini boşa çıkarırdı — sahip taslağı görür, gönderir.
     */
    public function uygunMu(OutreachTarget $aday): bool
    {
        return $aday->detection_band === DetectionResult::BAND_TURKISH
            && $aday->marketing_status === RegionPolicy::ALLOWED;
    }

    /**
     * Taslak üretir. AI kapalıysa/başarısızsa kişisel cümle boş döner —
     * mesajın geri kalanı yine kurulur, sahip cümleyi kendisi yazar.
     *
     * @return array{konu: string, mesaj: string, kisisel_cumle: ?string}
     */
    public function taslak(OutreachTarget $aday): array
    {
        $cumle = $this->kisiselCumle($aday);

        return [
            'konu' => 'Yurtdışındaki Türkler için ücretsiz ilan alanı — Nisoya',
            'kisisel_cumle' => $cumle,
            'mesaj' => $this->mesajiKur($cumle),
        ];
    }

    /** Modelden yalnız kişisel cümleyi ister; üretemezse null. */
    private function kisiselCumle(OutreachTarget $aday): ?string
    {
        if (! $this->isEnabled() || ! $this->uygunMu($aday)) {
            return null;
        }

        try {
            $data = $this->ai->analyzeText($this->buildPrompt($aday), [
                'type' => 'object',
                'properties' => [
                    'kisisel_cumle' => ['type' => ['string', 'null']],
                ],
                'required' => ['kisisel_cumle'],
                'additionalProperties' => false,
            ]);

            $cumle = trim((string) ($data['kisisel_cumle'] ?? ''));

            return $cumle === '' ? null : $cumle;
        } catch (\Throwable $e) {
            Log::warning('Erişim mesajı taslağı başarısız', ['exception' => $e->getMessage()]);

            return null;
        }
    }

    private function buildPrompt(OutreachTarget $aday): string
    {
        $bilinen = collect([
            'İşletme adı' => $aday->name,
            'Şehir' => $aday->city,
            'Ülke' => $aday->country,
            'Sektör' => $aday->sector,
            'Kategori' => $aday->category,
            'Web sitesi' => $aday->website,
        ])->filter(fn ($v) => filled($v))
            ->map(fn ($v, $k) => "- {$k}: {$v}")
            ->implode("\n");

        return <<<PROMPT
        Yurtdışındaki Türklere yönelik ücretsiz bir pazaryeri olan Nisoya için,
        bir işletmeye gönderilecek TANIŞMA POSTASINDAKİ TEK KİŞİSEL CÜMLEYİ
        yazacaksın. Mektubun geri kalanı hazır; senden yalnız o cümle isteniyor.

        Cümlenin işi: mektubun toplu posta gibi görünmesini engellemek. Bir
        insanın gerçekten bakıp yazdığını hissettirmeli.

        SADECE şu JSON'u döndür: {"kisisel_cumle": "..."} ya da
        {"kisisel_cumle": null}

        KESİN KURALLAR:

        1. YALNIZ AŞAĞIDA VERİLEN BİLGİYİ KULLAN. Bu işletmenin menüsünü, kaç
           yıldır açık olduğunu, mahallesini, müşterilerini BİLMİYORSUN.
           Bilmediğin hiçbir şeyi yazma.
        2. ELDEKİ BİLGİ ZAYIFSA null DÖNDÜR. Uydurulmuş bir kişisel cümle,
           hiç cümle olmamasından ÇOK DAHA KÖTÜDÜR: karşı taraf yanlışı fark
           eder ve mektubun tamamı güvenilirliğini kaybeder. Yalnız işletme
           adı ve şehir varsa bile doğal bir cümle kurulabiliyorsa kur;
           kurulamıyorsa çekinmeden null döndür.
        3. VAAT VERME. Müşteri sayısı, trafik, kazanç, "binlerce kişi" gibi
           hiçbir şey söyleme. Platform yeni ve bunu gizlemiyoruz.
        4. TEK CÜMLE, Türkçe, sıcak ama satış diline kaçmayan bir ton.
           Övgü yağdırma; sade ve gerçek olsun.
        5. Selamlama ya da imza YAZMA — onlar mektupta zaten var.

        İşletme hakkında bilinenler:
        {$bilinen}
        PROMPT;
    }

    /**
     * Şablon gövdesi. `docs/10-elle-erisim-listesi.md` ile aynı metin —
     * orası sahibin okuduğu, burası gönderilen. İkisi ayrışırsa sahip bir
     * şey okuyup başka bir şey göndermiş olur.
     */
    private function mesajiKur(?string $kisiselCumle): string
    {
        $kisisel = $kisiselCumle ?? '[buraya tek cümlelik kişisel bir dokunuş ekle]';

        return <<<MESAJ
        Merhaba,

        Ben Hakan. Yurtdışında yaşayan Türklerin birbirine hizmet verip ürün
        satabildiği ücretsiz bir platform kurdum: nisoya.com

        {$kisisel}

        Sizi neden yazdım: işletmenizi Nisoya'da ücretsiz listeleyebilirsiniz.
        Komisyon yok, üyelik ücreti yok, ödemeye aracılık etmiyoruz — site
        yalnızca bir buluşma yeri. Bulunduğunuz şehirdeki Türkler sizi Türkçe
        arayarak bulabilir.

        İlgilenirseniz beş dakikada açılıyor: nisoya.com
        Soru olursa bu postayı yanıtlamanız yeterli.

        Kolay gelsin,
        Hakan · nisoya.com
        MESAJ;
    }
}
