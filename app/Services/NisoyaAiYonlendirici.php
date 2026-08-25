<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Models\Country;
use App\Models\IslemTuru;
use App\Models\YasamKategorisi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Anasayfa "Nisoya AI ile ara" çubuğu (docs/plans/2026-08-19-…, madde C;
 * kapsam genişletmesi docs/plans/2026-08-25-…).
 *
 * ---------------------------------------------------------------------------
 * YÖNLENDİRİR, ÜRETMEZ
 *
 * Kullanıcının serbest metinle yazdığı soruyu TEK bir AI çağrısıyla
 * sınıflandırır (rehber/yasam/ilan/is/sss/belirsiz) ve var olan motorlardan
 * birine devreder: rehber niyeti → RehberDogalDilArama, yasam niyeti →
 * YasamDogalDilArama, sss niyeti → SssDogalDilArama (üçü de var olan
 * sayfaları bulur, AI çağırmaz), ilan/is niyeti → sırasıyla `/ilanlar` ve
 * `/isler`'ın kendi doğal dil/LIKE aramasına ham metni link olarak devreder.
 * Kendisi hiçbir zaman yeni bir
 * cevap METNİ ÜRETMEZ — yalnız var olan, insan doğrulamalı sayfalara işaret
 * eder. Konsolosluk/göçmenlik gibi hassas bir alanda modelin "cevap
 * uydurması" gerçek zarar demek; bu sınıf o riski yapısal olarak taşımaz.
 *
 * ---------------------------------------------------------------------------
 * MALİYET
 *
 * Sitenin EN görünür AI yüzeyi (anasayfa, ilk temas, misafire de açık).
 * `NisoyaAiAramaController`'daki throttle (perMinute+perDay) ve buradaki
 * 7 günlük önbellek bilerek var — bkz. bootstrap/app.php 'nisoya-ai-arama'.
 */
class NisoyaAiYonlendirici
{
    private const MIN_KELIME = 2;

    private const ONBELLEK_SURESI_GUN = 7;

    public function __construct(
        private readonly AiProvider $ai,
        private readonly RehberDogalDilArama $rehberArama,
        private readonly YasamDogalDilArama $yasamArama,
        private readonly SssDogalDilArama $sssArama,
        private readonly RehberYuzeyi $rehberYuzeyi,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('ai.features.nisoya_ai_arama') && $this->ai->isConfigured();
    }

    /** "iyi" / "merhaba" gibi tek kelimelik girdilere AI harcamamak için eşik. */
    public function aranmaliMi(string $sorgu): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $sorgu = trim($sorgu);

        return $sorgu !== '' && count(preg_split('/\s+/u', $sorgu) ?: []) >= self::MIN_KELIME;
    }

    /**
     * @return array{niyet: string, sonuclar: Collection<int, array{baslik: string, altbaslik: string, url: string}>, ilanBaglantisi: ?string}
     */
    public function ara(string $sorgu, ?string $varsayilanUlkeKodu): array
    {
        $yorum = $this->yorumla($sorgu);

        if ($yorum['niyet'] === 'ilan') {
            return $this->linkSonucu('ilan', $this->ilanBaglantisi($sorgu));
        }

        if ($yorum['niyet'] === 'is') {
            return $this->linkSonucu('is', $this->isBaglantisi($sorgu, $yorum['ulke_kodu']));
        }

        /*
         * 'sss' KENDİ BAŞINA bir dal — rehber/yaşam çapraz güvenlik ağına
         * KARIŞMAZ. SSS platformun kendisiyle ilgili (ücretsiz mi, ödeme
         * nasıl gibi); boş dönerse resmî/gündelik-yaşam motorlarını denemek
         * anlamsız (örtüşme yok) — doğrudan belirsize düşülür.
         */
        if ($yorum['niyet'] === 'sss') {
            $sonuclar = $this->sssArama->ara($yorum['anahtar_kelimeler']);

            return $sonuclar->isNotEmpty()
                ? ['niyet' => 'sss', 'sonuclar' => $sonuclar, 'ilanBaglantisi' => null]
                : $this->linkSonucu('belirsiz', $this->ilanBaglantisi($sorgu));
        }

        /*
         * 'rehber' / 'yasam' / 'belirsiz' — üçünde de HER İKİ motor (Rehber +
         * Yaşam Rehberi) sırayla denenir: "belirsiz" çıkan bir soru yine de
         * resmî bir konu ya da gündelik-yaşam konusu olabilir, sessiz
         * kalınmaz. Net sınıflandırılan niyetin motoru ÖNCE denenir, diğeri
         * yalnız güvenlik ağı — AI'nin rehber/yasam ayrımını yanlış çizdiği
         * durumlarda bile bir sonuç kaybolmaz.
         *
         * ZİYARETÇİNİN KENDİ ÜLKESİNE düşme (varsayılanUlkeKodu) YALNIZ o
         * motora NET işaret eden niyette — yani AI bunun o türden bir konu
         * OLDUĞUNDAN emin ama ülkeyi çıkaramadığında. "belirsiz" durumunda bu
         * kişiselleştirme UYGULANMAZ: soruda hiçbir gerçek ipucu (ülke/işlem/
         * kategori/anahtar kelime) yoksa "belirsiz" + varsayılan ülke =
         * soruyla hiç ilgisi olmayan bir "işte elçiliğin" cevabı üretirdi.
         * Gerçek olay (2026-08-20, canlıda ölçüldü): "merhaba naber
         * nasılsın" gibi rastgele bir selamlama bile ziyaretçinin
         * ülkesindeki temsilciliği döndürüyordu.
         */
        $rehberVarsayilan = $yorum['niyet'] === 'rehber' ? $varsayilanUlkeKodu : null;
        $yasamVarsayilan = $yorum['niyet'] === 'yasam' ? $varsayilanUlkeKodu : null;

        $sira = $yorum['niyet'] === 'yasam' ? ['yasam', 'rehber'] : ['rehber', 'yasam'];

        foreach ($sira as $motor) {
            $sonuclar = $motor === 'rehber'
                ? $this->rehberArama->ara($yorum['ulke_kodu'], $yorum['islem_turu_slug'], $yorum['anahtar_kelimeler'], $rehberVarsayilan)
                : $this->yasamArama->ara($yorum['ulke_kodu'], $yorum['yasam_kategori_slug'], $yorum['anahtar_kelimeler'], $yasamVarsayilan);

            if ($sonuclar->isNotEmpty()) {
                return ['niyet' => $motor, 'sonuclar' => $sonuclar, 'ilanBaglantisi' => null];
            }
        }

        /*
         * Rehber VE Yaşam Rehberi ikisi de boş — SSS'i son güvenlik ağı
         * olarak dene ("nasıl kayıt olurum" gibi AI'nin belirsiz dediği ama
         * aslında platform sorusu olan durumlar için).
         */
        $sssSonuclari = $this->sssArama->ara($yorum['anahtar_kelimeler']);
        if ($sssSonuclari->isNotEmpty()) {
            return ['niyet' => 'sss', 'sonuclar' => $sssSonuclari, 'ilanBaglantisi' => null];
        }

        return $this->linkSonucu('belirsiz', $this->ilanBaglantisi($sorgu));
    }

    /**
     * @return array{niyet: string, sonuclar: Collection<int, array{baslik: string, altbaslik: string, url: string}>, ilanBaglantisi: ?string}
     */
    private function linkSonucu(string $niyet, string $baglanti): array
    {
        return ['niyet' => $niyet, 'sonuclar' => collect(), 'ilanBaglantisi' => $baglanti];
    }

    private function ilanBaglantisi(string $sorgu): string
    {
        return url('/ilanlar').'?q='.urlencode($sorgu);
    }

    /**
     * `is` niyeti için yeni bir arama motoru YOK — `JobBrowseController`
     * zaten `q` (başlık+açıklama LIKE) ve `ulke` filtresini destekliyor;
     * `ilan` niyetinin `/ilanlar`'a yaptığı ham-metin handoff'unun aynısı.
     * `ulke_kodu` burada da uydurulmuş olabilir — linke eklemeden önce
     * gerçek aktif ülke listesine karşı doğrulanır.
     */
    private function isBaglantisi(string $sorgu, ?string $ulkeKodu): string
    {
        $params = ['q' => $sorgu];

        $ulkeKodu = $this->dogrulaUlke($ulkeKodu);
        if ($ulkeKodu !== null) {
            $params['ulke'] = $ulkeKodu;
        }

        return url('/isler').'?'.http_build_query($params);
    }

    private function dogrulaUlke(?string $kod): ?string
    {
        if ($kod === null || trim($kod) === '') {
            return null;
        }

        $kod = strtoupper(trim($kod));

        return Country::query()->where('is_active', true)->where('code', $kod)->exists() ? $kod : null;
    }

    /**
     * @return array{niyet: string, ulke_kodu: ?string, islem_turu_slug: ?string, yasam_kategori_slug: ?string, anahtar_kelimeler: list<string>}
     */
    private function yorumla(string $sorgu): array
    {
        $anahtar = 'nisoya_ai_arama:'.md5(mb_strtolower(trim($sorgu)));

        $yorum = Cache::remember($anahtar, now()->addDays(self::ONBELLEK_SURESI_GUN), function () use ($sorgu) {
            try {
                $veri = $this->ai->analyzeText($this->istem($sorgu), $this->sema());
            } catch (\Throwable $e) {
                Log::warning('Nisoya AI arama yorumlama başarısız', ['exception' => $e->getMessage()]);

                return null;
            }

            return is_array($veri) ? $this->dogrula($veri) : null;
        });

        return $yorum ?? [
            'niyet' => 'belirsiz',
            'ulke_kodu' => null,
            'islem_turu_slug' => null,
            'yasam_kategori_slug' => null,
            'anahtar_kelimeler' => $this->kabaKelimelerAyikla($sorgu),
        ];
    }

    /**
     * @param  array<string, mixed>  $veri
     * @return array{niyet: string, ulke_kodu: ?string, islem_turu_slug: ?string, yasam_kategori_slug: ?string, anahtar_kelimeler: list<string>}
     */
    private function dogrula(array $veri): array
    {
        // Ülke/işlem türü/yaşam kategorisi kodlarının GEÇERLİ olup olmadığı
        // burada değil, RehberDogalDilArama/YasamDogalDilArama'da doğrulanır
        // — tek doğrulama kaynağı orada kalsın diye kasıtlı olarak burada
        // tekrarlanmıyor.
        $niyet = $veri['niyet'] ?? null;
        if (! in_array($niyet, ['rehber', 'yasam', 'ilan', 'is', 'sss', 'belirsiz'], true)) {
            $niyet = 'belirsiz';
        }

        $anahtarKelimeler = is_array($veri['anahtar_kelimeler'] ?? null)
            ? array_values(array_filter(
                array_map(fn ($k) => trim((string) $k), $veri['anahtar_kelimeler']),
                fn (string $k) => $k !== '',
            ))
            : [];

        return [
            'niyet' => $niyet,
            'ulke_kodu' => filled($veri['ulke_kodu'] ?? null) ? (string) $veri['ulke_kodu'] : null,
            'islem_turu_slug' => filled($veri['islem_turu_slug'] ?? null) ? (string) $veri['islem_turu_slug'] : null,
            'yasam_kategori_slug' => filled($veri['yasam_kategori_slug'] ?? null) ? (string) $veri['yasam_kategori_slug'] : null,
            'anahtar_kelimeler' => $anahtarKelimeler,
        ];
    }

    /** AI tamamen başarısız olursa güvenlik ağı: kısa kelimeleri (ve/bir/mi) ele. */
    private function kabaKelimelerAyikla(string $sorgu): array
    {
        return array_values(array_filter(
            preg_split('/\s+/u', trim($sorgu)) ?: [],
            fn (string $kelime) => mb_strlen($kelime) >= 4,
        ));
    }

    private function istem(string $sorgu): string
    {
        // Temsilcilik kaydı olan TÜM ülkeler isteme VERİLİYOR (hazirUlkeler()
        // DEĞİL — o yalnız işlem içeriği olanları döner, bu daha geniş; bkz.
        // RehberYuzeyi::kapsananUlkeler() docblock'u). Model listede olmayan
        // bir değer uydurursa RehberDogalDilArama zaten atar, ama listeyi
        // baştan vermek isabet oranını yükseltir (DogalDilArama'nın aynı
        // disiplini).
        $ulkeler = $this->rehberYuzeyi->kapsananUlkeler()
            ->map(fn ($u) => $u->code.' ('.$u->name_tr.')')
            ->implode(', ');

        $islemTurleri = IslemTuru::query()
            ->where('is_active', true)
            ->get(['ad', 'slug'])
            ->map(fn ($t) => $t->slug.' ('.$t->ad.')')
            ->implode(', ');

        $yasamKategorileri = YasamKategorisi::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['ad', 'slug'])
            ->map(fn ($k) => $k->slug.' ('.$k->ad.')')
            ->implode(', ');

        return implode("\n", [
            'Kullanıcı, yurt dışındaki Türklere yönelik "Nisoya" adlı sitenin anasayfasındaki',
            'yapay zeka arama çubuğuna bir soru yazdı. Görevin bu sorunun NİYETİNİ sınıflandırmak.',
            '',
            'NİYET SEÇENEKLERİ:',
            '- "rehber": resmî/konsolosluk/göçmenlik/hukuki bir konu (ör. pasaport, vekaletname,',
            '  askerlik, apostil, vergi numarası, ehliyet gibi).',
            '- "yasam": resmî bir işlem DEĞİL, gündelik yaşam pratiği (ör. "Almanya\'da kira nasıl',
            '  ödenir", "SSN\'siz banka hesabı açma", sağlık sigortası, iş arama süreci gibi).',
            '- "ilan": bir hizmet/ürün/usta/hoca aranıyor (ör. "Berlin\'de temizlikçi", "ikinci el araba").',
            '- "is": bir İŞ/kariyer ilanı aranıyor (ör. "İstanbul\'da satış temsilcisi", "uzaktan yazılımcı iş").',
            '- "sss": Nisoya\'nın kendisiyle ilgili genel bir soru (ör. "Nisoya ücretsiz mi",',
            '  "ödeme nasıl yapılıyor", "ilanım neden görünmüyor", "kime güvenebilirim").',
            '- "belirsiz": hiçbirine net uymuyor.',
            '',
            'KURALLAR:',
            '- `ulke_kodu` YALNIZ aşağıdaki listeden bir kod olabilir; soruda ülke geçmiyorsa ya da',
            '  listede yoksa null bırak — uydurma.',
            '- `islem_turu_slug` YALNIZ niyet "rehber" ise VE aşağıdaki listeden net bir slug',
            '  eşleşiyorsa doldur; değilse null bırak.',
            '- `yasam_kategori_slug` YALNIZ niyet "yasam" ise VE aşağıdaki listeden net bir slug',
            '  eşleşiyorsa doldur; değilse null bırak.',
            '- `anahtar_kelimeler`: sorunun özünü yakalayan 2-4 kelime, HER ZAMAN doldur (arama yedeği).',
            '',
            'HAZIR ÜLKELER: '.($ulkeler ?: '(şu an hiçbiri hazır değil)'),
            '',
            'İŞLEM TÜRLERİ (yalnız "rehber" niyeti için): '.($islemTurleri ?: '(şu an tanımlı değil)'),
            '',
            'YAŞAM REHBERİ KATEGORİLERİ (yalnız "yasam" niyeti için): '.($yasamKategorileri ?: '(şu an tanımlı değil)'),
            '',
            '--- SORU ---',
            Str::limit($sorgu, 200, ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'niyet' => ['type' => 'string', 'enum' => ['rehber', 'yasam', 'ilan', 'is', 'sss', 'belirsiz']],
                'ulke_kodu' => ['type' => ['string', 'null']],
                'islem_turu_slug' => ['type' => ['string', 'null']],
                'yasam_kategori_slug' => ['type' => ['string', 'null']],
                'anahtar_kelimeler' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => ['niyet', 'ulke_kodu', 'islem_turu_slug', 'yasam_kategori_slug', 'anahtar_kelimeler'],
            'additionalProperties' => false,
        ];
    }
}
