<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Models\IslemTuru;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Anasayfa "Nisoya AI ile ara" çubuğu (docs/plans/2026-08-19-…, madde C).
 *
 * ---------------------------------------------------------------------------
 * YÖNLENDİRİR, ÜRETMEZ
 *
 * Kullanıcının serbest metinle yazdığı soruyu TEK bir AI çağrısıyla
 * sınıflandırır (rehber/ilan/belirsiz) ve var olan iki motordan birine
 * devreder: rehber niyeti → RehberDogalDilArama (var olan Rehber sayfalarını
 * bulur), ilan niyeti → var olan `/ilanlar` doğal dil aramasına link verir.
 * Kendisi hiçbir zaman yeni bir cevap METNİ ÜRETMEZ — yalnız var olan,
 * insan doğrulamalı sayfalara işaret eder. Konsolosluk/göçmenlik gibi hassas
 * bir alanda modelin "cevap uydurması" gerçek zarar demek; bu sınıf o riski
 * yapısal olarak taşımaz.
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
            return [
                'niyet' => 'ilan',
                'sonuclar' => collect(),
                'ilanBaglantisi' => $this->ilanBaglantisi($sorgu),
            ];
        }

        /*
         * 'rehber' VEYA 'belirsiz' — ikisinde de Rehber denenir: "belirsiz"
         * çıkan bir soru yine de resmî bir konu olabilir, sessiz kalınmaz.
         *
         * ZİYARETÇİNİN KENDİ ÜLKESİNE düşme (varsayılanUlkeKodu) YALNIZ
         * "rehber" niyetinde — yani AI bunun resmî bir konu OLDUĞUNDAN emin
         * ama ülkeyi çıkaramadığında. "belirsiz" durumunda bu kişiselleştirme
         * UYGULANMAZ: soruda hiçbir gerçek ipucu (ülke/işlem/anahtar kelime)
         * yoksa "belirsiz" + varsayılan ülke = soruyla hiç ilgisi olmayan
         * bir "işte elçiliğin" cevabı üretirdi. Gerçek olay (2026-08-20,
         * canlıda ölçüldü): "merhaba naber nasılsın" gibi rastgele bir
         * selamlama bile ziyaretçinin ülkesindeki temsilciliği döndürüyordu.
         */
        $sonuclar = $this->rehberArama->ara(
            $yorum['ulke_kodu'],
            $yorum['islem_turu_slug'],
            $yorum['anahtar_kelimeler'],
            $yorum['niyet'] === 'rehber' ? $varsayilanUlkeKodu : null,
        );

        return [
            'niyet' => $sonuclar->isNotEmpty() ? 'rehber' : 'belirsiz',
            'sonuclar' => $sonuclar,
            'ilanBaglantisi' => $sonuclar->isEmpty() ? $this->ilanBaglantisi($sorgu) : null,
        ];
    }

    private function ilanBaglantisi(string $sorgu): string
    {
        return url('/ilanlar').'?q='.urlencode($sorgu);
    }

    /**
     * @return array{niyet: string, ulke_kodu: ?string, islem_turu_slug: ?string, anahtar_kelimeler: list<string>}
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
            'anahtar_kelimeler' => $this->kabaKelimelerAyikla($sorgu),
        ];
    }

    /**
     * @param  array<string, mixed>  $veri
     * @return array{niyet: string, ulke_kodu: ?string, islem_turu_slug: ?string, anahtar_kelimeler: list<string>}
     */
    private function dogrula(array $veri): array
    {
        // Ülke/işlem türü kodlarının GEÇERLİ olup olmadığı burada değil,
        // RehberDogalDilArama'da doğrulanır — tek doğrulama kaynağı orada
        // kalsın diye kasıtlı olarak burada tekrarlanmıyor.
        $niyet = $veri['niyet'] ?? null;
        if (! in_array($niyet, ['rehber', 'ilan', 'belirsiz'], true)) {
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

        return implode("\n", [
            'Kullanıcı, yurt dışındaki Türklere yönelik "Nisoya" adlı sitenin anasayfasındaki',
            'yapay zeka arama çubuğuna bir soru yazdı. Görevin bu sorunun NİYETİNİ sınıflandırmak.',
            '',
            'NİYET SEÇENEKLERİ:',
            '- "rehber": resmî/konsolosluk/göçmenlik/hukuki bir konu (ör. pasaport, vekaletname,',
            '  askerlik, apostil, banka hesabı açma, vergi numarası, ehliyet gibi).',
            '- "ilan": bir hizmet/ürün/usta/hoca aranıyor (ör. "Berlin\'de temizlikçi", "ikinci el araba").',
            '- "belirsiz": ikisine de net uymuyor.',
            '',
            'KURALLAR:',
            '- `ulke_kodu` YALNIZ aşağıdaki listeden bir kod olabilir; soruda ülke geçmiyorsa ya da',
            '  listede yoksa null bırak — uydurma.',
            '- `islem_turu_slug` YALNIZ aşağıdaki listeden bir slug olabilir; net değilse null bırak.',
            '- `anahtar_kelimeler`: sorunun özünü yakalayan 2-4 kelime, HER ZAMAN doldur (arama yedeği).',
            '',
            'HAZIR ÜLKELER: '.($ulkeler ?: '(şu an hiçbiri hazır değil)'),
            '',
            'İŞLEM TÜRLERİ: '.($islemTurleri ?: '(şu an tanımlı değil)'),
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
                'niyet' => ['type' => 'string', 'enum' => ['rehber', 'ilan', 'belirsiz']],
                'ulke_kodu' => ['type' => ['string', 'null']],
                'islem_turu_slug' => ['type' => ['string', 'null']],
                'anahtar_kelimeler' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => ['niyet', 'ulke_kodu', 'islem_turu_slug', 'anahtar_kelimeler'],
            'additionalProperties' => false,
        ];
    }
}
