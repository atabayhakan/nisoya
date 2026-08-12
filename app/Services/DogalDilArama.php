<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Doğal dille arama: cümleyi VAR OLAN süzgeçlere çevirir.
 *
 * ---------------------------------------------------------------------------
 * NE YAPMIYOR
 *
 * Yeni bir arama motoru, gömme (embedding) ya da anlamsal indeks YOK. Kullanıcı
 * "Berlin'de ucuz ev temizliği" yazdığında bugün olan şey, bu cümlenin tamamının
 * başlık/açıklamada LIKE ile aranması — yani hiçbir şey bulunmaması. Yapılan iş
 * cümleyi `q` + `sehir` + `tip` + `max` gibi ZATEN VAR OLAN alanlara ayırmak;
 * sorgu bundan sonra hiç değişmeden çalışıyor.
 *
 * ---------------------------------------------------------------------------
 * ÜÇ KURAL
 *
 * 1. GÖRÜNÜR OLACAK. Anlaşılan süzgeçler kullanıcıya YAZILIR ve tek tıkla
 *    kapatılabilir. Arama kutusuna yazdığından başka bir sonuç kümesi
 *    görüp de sebebini bilmemek, aramaya olan güveni bitirir.
 * 2. KULLANICININ SEÇİMİNİ EZMEZ. Kullanıcı ülkeyi/kategoriyi kendisi
 *    seçtiyse yorumlama hiç çalışmaz. Elle seçilen bir süzgecin AI
 *    tarafından değiştirilmesi, en sinir bozucu hata türüdür.
 * 3. UYDURMAZ. Kategori ve ülke listesi isteme VERİLİR; model listede
 *    olmayan bir değer döndürürse o alan atılır. Uydurulmuş bir kategori
 *    slug'ı boş sonuç sayfası demektir.
 *
 * ---------------------------------------------------------------------------
 * ÖNBELLEK ŞART
 *
 * Arama en çok çağrılan sayfa. Her istekte AI çağırmak hem yavaş hem pahalı.
 * Aynı cümle → aynı yorum, 7 gün önbellekte. Yorum ucuz olduğu için
 * önbelleğin bayatlaması bir sorun değil; kategori listesi değişirse
 * doğrulama katmanı zaten geçersiz değeri atıyor.
 */
class DogalDilArama
{
    /** Bu uzunluğun altındaki sorgu zaten anahtar kelimedir; yorumlanmaz. */
    private const MIN_KELIME = 3;

    private const ONBELLEK_SURESI_GUN = 7;

    public function __construct(private readonly AiProvider $ai) {}

    public function isEnabled(): bool
    {
        return (bool) config('ai.features.natural_search') && $this->ai->isConfigured();
    }

    /**
     * Bu sorgu yorumlanmalı mı?
     *
     * "iphone 15" yazan kişi zaten anahtar kelime arıyor; oraya AI sokmak
     * para harcamak ve sonucu bozma riskidir. Cümle kurulmuşsa (3+ kelime)
     * devreye girer.
     */
    public function yorumlanmaliMi(string $sorgu, bool $baskaSuzgecVarMi): bool
    {
        if (! $this->isEnabled() || $baskaSuzgecVarMi) {
            return false;
        }

        $sorgu = trim($sorgu);

        return $sorgu !== '' && count(preg_split('/\s+/u', $sorgu) ?: []) >= self::MIN_KELIME;
    }

    /**
     * Cümleyi süzgeçlere çevirir. Kapalı/başarısızsa null (arama aynen çalışır).
     *
     * @return array{q: ?string, sehir: ?string, ulke: ?string, tip: ?string, kategori: ?string, max: ?float}|null
     */
    public function yorumla(string $sorgu): ?array
    {
        $anahtar = 'dogal_arama:'.md5(mb_strtolower(trim($sorgu)));

        return Cache::remember($anahtar, now()->addDays(self::ONBELLEK_SURESI_GUN), function () use ($sorgu) {
            try {
                $veri = $this->ai->analyzeText($this->istem($sorgu), $this->sema());
            } catch (\Throwable $e) {
                Log::warning('Doğal dil araması başarısız', ['exception' => $e->getMessage()]);

                return null;
            }

            return is_array($veri) ? $this->dogrula($veri) : null;
        });
    }

    /**
     * Modelin döndürdüğünü GERÇEK verilere karşı doğrular.
     *
     * Uydurulmuş bir kategori slug'ı ya da olmayan bir ülke kodu, kullanıcıya
     * boş sonuç sayfası gösterir — üstelik sebebi anlaşılmaz. Listede
     * olmayan her değer sessizce atılıyor.
     *
     * @param  array<string, mixed>  $veri
     * @return array{q: ?string, sehir: ?string, ulke: ?string, tip: ?string, kategori: ?string, max: ?float}
     */
    private function dogrula(array $veri): array
    {
        $kategori = filled($veri['kategori'] ?? null) ? (string) $veri['kategori'] : null;
        if ($kategori !== null && ! $this->kategoriSluglari()->contains($kategori)) {
            $kategori = null;
        }

        $ulke = filled($veri['ulke'] ?? null) ? strtoupper((string) $veri['ulke']) : null;
        if ($ulke !== null && ! $this->ulkeKodlari()->contains($ulke)) {
            $ulke = null;
        }

        $tip = filled($veri['tip'] ?? null) ? (string) $veri['tip'] : null;
        if (! in_array($tip, ['hizmet', 'urun', 'emlak', 'vasita'], true)) {
            $tip = null;
        }

        $max = isset($veri['max']) && is_numeric($veri['max']) ? (float) $veri['max'] : null;
        if ($max !== null && $max <= 0) {
            $max = null;
        }

        return [
            'q' => filled($veri['q'] ?? null) ? Str::limit((string) $veri['q'], 80, '') : null,
            'sehir' => filled($veri['sehir'] ?? null) ? Str::limit((string) $veri['sehir'], 60, '') : null,
            'ulke' => $ulke,
            'tip' => $tip,
            'kategori' => $kategori,
            'max' => $max,
        ];
    }

    /** @return Collection<int, string> */
    private function kategoriSluglari()
    {
        return Cache::remember('dogal_arama:kategori_sluglari', now()->addHours(6),
            fn () => Category::query()->where('is_active', true)->pluck('slug'));
    }

    /** @return Collection<int, string> */
    private function ulkeKodlari()
    {
        return Cache::remember('dogal_arama:ulke_kodlari', now()->addHours(6),
            fn () => Country::query()->where('is_active', true)->pluck('code'));
    }

    public function istem(string $sorgu): string
    {
        // Kategori listesi isteme VERİLİYOR: model uydurmasın diye. Ad+slug
        // birlikte, çünkü kullanıcı "temizlik" der, slug "ev-temizligi"dir.
        $kategoriler = Category::query()
            ->where('is_active', true)
            ->whereNotNull('parent_id')
            ->get(['name', 'slug'])
            ->map(fn ($k) => $k->slug.' ('.$k->name.')')
            ->implode(', ');

        $ulkeler = Country::query()
            ->where('is_active', true)
            ->get(['code', 'name_tr'])
            ->map(fn ($u) => $u->code.' ('.$u->name_tr.')')
            ->implode(', ');

        return implode("\n", [
            'Kullanıcının serbest metinle yazdığı arama cümlesini, bir ilan',
            'sitesinin süzgeçlerine ayır.',
            '',
            'KURALLAR:',
            '- Cümlede AÇIKÇA geçmeyen hiçbir alanı doldurma; emin değilsen null bırak.',
            '- `q` alanına yalnız ARANAN ŞEYİ yaz (şehir, ülke, fiyat ve',
            '  "ucuz/uygun" gibi kelimeler oraya GİRMEZ; onların kendi alanı var).',
            '- `kategori` yalnız aşağıdaki listeden bir slug olabilir. Listede',
            '  yoksa null yaz — uydurma.',
            '- `ulke` yalnız aşağıdaki listeden bir kod olabilir.',
            '- `max` yalnız cümlede SAYIYLA bir üst sınır varsa doldurulur',
            '  ("500 euro altı" → 500). "Ucuz", "uygun fiyatlı" gibi ifadeler',
            '  için SAYI UYDURMA, null bırak.',
            '- `tip`: hizmet, urun, emlak, vasita — yalnız bunlardan biri.',
            '',
            'KATEGORİLER: '.$kategoriler,
            '',
            'ÜLKELER: '.$ulkeler,
            '',
            '--- ARAMA CÜMLESİ ---',
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
                'q' => ['type' => ['string', 'null']],
                'sehir' => ['type' => ['string', 'null']],
                'ulke' => ['type' => ['string', 'null']],
                'tip' => ['type' => ['string', 'null']],
                'kategori' => ['type' => ['string', 'null']],
                'max' => ['type' => ['number', 'null']],
            ],
            'required' => ['q', 'sehir', 'ulke', 'tip', 'kategori', 'max'],
            'additionalProperties' => false,
        ];
    }
}
