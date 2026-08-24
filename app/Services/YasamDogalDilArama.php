<?php

namespace App\Services;

use App\Models\Country;
use App\Models\YasamKategorisi;
use App\Models\YasamKonuIcerigi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Rehber'de doğal dil ipuçlarını (ülke kodu, kategori, anahtar kelimeler)
 * GERÇEK Yaşam Rehberi sonuçlarına çevirir. `RehberDogalDilArama`'nın
 * aynadaşı — docs/plans/2026-08-25-…, birebir aynı disiplin.
 *
 * AI ÇAĞIRMAZ — yorumlama tek bir yerde, `NisoyaAiYonlendirici`'de yapılır.
 * Bu sınıf o çağrının SONUCUNU alır, GERÇEK verilere karşı doğrular.
 *
 * UYDURMAZ, K7'Yİ BOZMAZ — geçersiz ülke/kategori kodu sessizce atılır;
 * yalnız `YasamKonuIcerigi::STATUS_YAYIN` kayıtlar döner, taslak sızmaz.
 */
class YasamDogalDilArama
{
    private const SONUC_LIMIT = 5;

    /**
     * @param  list<string>  $anahtarKelimeler
     * @return Collection<int, array{baslik: string, altbaslik: string, url: string}>
     */
    public function ara(?string $ulkeKodu, ?string $kategoriSlug, array $anahtarKelimeler, ?string $varsayilanUlkeKodu = null): Collection
    {
        $ulkeKodu = $this->dogrulaUlke($ulkeKodu) ?? $this->dogrulaUlke($varsayilanUlkeKodu);
        $kategoriSlug = $this->dogrulaKategori($kategoriSlug);

        if ($ulkeKodu !== null && $kategoriSlug !== null) {
            $dogrudan = $this->sorgula(fn ($q) => $q
                ->where('country_code', $ulkeKodu)
                ->whereHas('konu.kategori', fn ($k) => $k->where('slug', $kategoriSlug)->where('is_active', true)));

            if ($dogrudan->isNotEmpty()) {
                return $dogrudan;
            }
        }

        if ($kategoriSlug !== null) {
            $kategoriBazli = $this->sorgula(fn ($q) => $q
                ->whereHas('konu.kategori', fn ($k) => $k->where('slug', $kategoriSlug)->where('is_active', true))
                ->when($ulkeKodu, fn ($q2) => $q2->where('country_code', $ulkeKodu)));

            if ($kategoriBazli->isNotEmpty()) {
                return $kategoriBazli;
            }
        }

        if ($anahtarKelimeler !== []) {
            $kelimeBazli = $this->sorgula(function ($q) use ($anahtarKelimeler, $ulkeKodu) {
                $q->when($ulkeKodu, fn ($q2) => $q2->where('country_code', $ulkeKodu));

                $q->whereHas('konu', function ($sub) use ($anahtarKelimeler) {
                    $sub->where(function ($inner) use ($anahtarKelimeler) {
                        foreach ($anahtarKelimeler as $kelime) {
                            $kelime = trim($kelime);
                            if ($kelime === '') {
                                continue;
                            }

                            $inner->orWhere('baslik', 'like', "%{$kelime}%")
                                ->orWhere('kisa_aciklama', 'like', "%{$kelime}%");
                        }
                    });
                });
            });

            if ($kelimeBazli->isNotEmpty()) {
                return $kelimeBazli;
            }
        }

        /*
         * Hiçbir KONU eşleşmedi. Ülke biliniyorsa yayında içeriği olan
         * kategorileri öner — `RehberDogalDilArama::temsilciklerleBul()`'ün
         * karşılığı: hiç eşleşme yoksa bile sessiz kalınmaz.
         */
        return $ulkeKodu !== null ? $this->kategorilerleBul($ulkeKodu) : collect();
    }

    /**
     * @return Collection<int, array{baslik: string, altbaslik: string, url: string}>
     */
    private function kategorilerleBul(string $ulkeKodu): Collection
    {
        return YasamKategorisi::query()
            ->aktif()
            ->whereHas('konular', fn ($k) => $k->where('is_active', true)
                ->whereHas('icerikler', fn ($q) => $q->where('status', YasamKonuIcerigi::STATUS_YAYIN)->where('country_code', $ulkeKodu)))
            ->orderBy('sort_order')
            ->limit(self::SONUC_LIMIT)
            ->get()
            ->map(fn (YasamKategorisi $kategori): array => [
                'baslik' => $kategori->ad,
                'altbaslik' => $this->kategoriAltBaslik(),
                'url' => route('yasam-rehberi.konular', [strtolower($ulkeKodu), $kategori->slug]),
            ]);
    }

    /**
     * Ayrı bir metot olarak: satır içi sabit dize PHPStan'da bir literal
     * string tipine daralıyor (Collection'ın TValue'su kovaryant değil),
     * `ara()`'nın döndürdüğü diğer dallarla (dinamik `string`) çakışıyordu.
     * `RehberDogalDilArama::temsilcilikAltBaslik()` ile aynı çözüm.
     */
    private function kategoriAltBaslik(): string
    {
        return 'Yaşam Rehberi kategorisi';
    }

    /**
     * @param  \Closure(Builder<YasamKonuIcerigi>): mixed  $filtre
     * @return Collection<int, array{baslik: string, altbaslik: string, url: string}>
     */
    private function sorgula(\Closure $filtre): Collection
    {
        $query = YasamKonuIcerigi::query()
            ->yayinda()
            ->whereHas('konu', fn ($k) => $k->where('is_active', true))
            ->whereHas('konu.kategori', fn ($k) => $k->where('is_active', true));

        $filtre($query);

        return $query->with(['konu.kategori'])
            ->limit(self::SONUC_LIMIT)
            ->get()
            ->map(fn (YasamKonuIcerigi $icerik): array => [
                'baslik' => $icerik->konu->baslik,
                'altbaslik' => $icerik->konu->kategori->ad,
                'url' => route('yasam-rehberi.icerik', [
                    strtolower($icerik->country_code),
                    $icerik->konu->kategori->slug,
                    $icerik->konu->slug,
                ]),
            ]);
    }

    private function dogrulaUlke(?string $kod): ?string
    {
        if ($kod === null || trim($kod) === '') {
            return null;
        }

        $kod = strtoupper(trim($kod));

        return $this->aktifUlkeKodlari()->contains($kod) ? $kod : null;
    }

    private function dogrulaKategori(?string $slug): ?string
    {
        if ($slug === null || trim($slug) === '') {
            return null;
        }

        return $this->aktifKategoriSluglari()->contains($slug) ? $slug : null;
    }

    /** @return Collection<int, string> */
    private function aktifUlkeKodlari(): Collection
    {
        return Country::query()->where('is_active', true)->pluck('code');
    }

    /** @return Collection<int, string> */
    private function aktifKategoriSluglari(): Collection
    {
        return YasamKategorisi::query()->where('is_active', true)->pluck('slug');
    }
}
