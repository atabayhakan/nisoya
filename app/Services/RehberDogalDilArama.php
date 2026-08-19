<?php

namespace App\Services;

use App\Models\Country;
use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Rehber'de doğal dil ipuçlarını (ülke kodu, işlem türü, anahtar kelimeler)
 * GERÇEK sonuçlara çevirir.
 *
 * ---------------------------------------------------------------------------
 * AI ÇAĞIRMAZ — BİLEREK
 *
 * Yorumlama (soruyu ülke/işlem türüne ayırma) tek bir yerde,
 * `NisoyaAiYonlendirici`'de yapılır — anasayfa çubuğu HEM rehber HEM ilan
 * niyetini aynı çağrıda çıkarır. Bu sınıf o çağrının SONUCUNU alır, GERÇEK
 * verilere karşı doğrular ve sorgular. İki ayrı AI isteği yerine bir tane —
 * "her istek gerçek para" (bkz. docs/03-buyume-fikirleri.md).
 *
 * ---------------------------------------------------------------------------
 * UYDURMAZ, K7'Yİ BOZMAZ
 *
 * Model geçersiz/var olmayan bir ülke kodu ya da işlem türü slug'ı
 * döndürürse burada sessizce atılır (DogalDilArama'nın aynı disiplini).
 * Yalnız `yayinda()` kayıtlar döner — taslak içerik hiçbir yoldan sızmaz.
 * Hiçbir sonuç AI tarafından ÜRETİLMEZ; hepsi var olan, insan doğrulamalı
 * Rehber sayfalarına işaret eder.
 */
class RehberDogalDilArama
{
    private const SONUC_LIMIT = 5;

    /**
     * @param  list<string>  $anahtarKelimeler
     * @return Collection<int, array{baslik: string, altbaslik: string, url: string}>
     */
    public function ara(?string $ulkeKodu, ?string $islemTuruSlug, array $anahtarKelimeler, ?string $varsayilanUlkeKodu = null): Collection
    {
        $ulkeKodu = $this->dogrulaUlke($ulkeKodu) ?? $this->dogrulaUlke($varsayilanUlkeKodu);
        $islemTuruSlug = $this->dogrulaIslemTuru($islemTuruSlug);

        if ($ulkeKodu !== null && $islemTuruSlug !== null) {
            $dogrudan = $this->sorgula(fn ($q) => $q
                ->whereHas('temsilcilik', fn ($t) => $t->where('is_active', true)->where('country_code', $ulkeKodu))
                ->whereHas('islemTuru', fn ($t) => $t->where('slug', $islemTuruSlug)->where('is_active', true)));

            if ($dogrudan->isNotEmpty()) {
                return $dogrudan;
            }
        }

        if ($islemTuruSlug !== null) {
            $turBazli = $this->sorgula(fn ($q) => $q
                ->whereHas('islemTuru', fn ($t) => $t->where('slug', $islemTuruSlug)->where('is_active', true))
                ->when($ulkeKodu, fn ($q2) => $q2->whereHas('temsilcilik', fn ($t) => $t->where('is_active', true)->where('country_code', $ulkeKodu))));

            if ($turBazli->isNotEmpty()) {
                return $turBazli;
            }
        }

        if ($anahtarKelimeler !== []) {
            $kelimeBazli = $this->sorgula(function ($q) use ($anahtarKelimeler, $ulkeKodu) {
                $q->when($ulkeKodu, fn ($q2) => $q2->whereHas('temsilcilik', fn ($t) => $t->where('is_active', true)->where('country_code', $ulkeKodu)));

                $q->where(function ($sub) use ($anahtarKelimeler) {
                    foreach ($anahtarKelimeler as $kelime) {
                        $kelime = trim($kelime);
                        if ($kelime === '') {
                            continue;
                        }

                        $sub->orWhereHas('islemTuru', fn ($t) => $t->where('ad', 'like', "%{$kelime}%"))
                            ->orWhereHas('temsilcilik', fn ($t) => $t->where('ad', 'like', "%{$kelime}%")
                                ->orWhere('sehir', 'like', "%{$kelime}%"));
                    }
                });
            });

            if ($kelimeBazli->isNotEmpty()) {
                return $kelimeBazli;
            }
        }

        /*
         * Hiçbir İŞLEM kaydı eşleşmedi. Ülke biliniyorsa temsilciliğin
         * KENDİSİNİ öner — "elçilik nerede", "hangi şehirde" gibi sorular
         * bir işlem türüne değil doğrudan temsilciliğe karşılık gelir.
         * Özellikle henüz yapılandırılmış işlemi olmayan, yalnız
         * yönlendirme notu taşıyan temsilcilikler için (ör. Bişkek) TEK
         * anlamlı sonuç budur — üstteki aramalar orada hep boş döner
         * çünkü hiç yayında TemsilcilikIslemi kaydı yoktur.
         */
        return $ulkeKodu !== null ? $this->temsilciklerleBul($ulkeKodu) : collect();
    }

    /**
     * @return Collection<int, array{baslik: string, altbaslik: string, url: string}>
     */
    private function temsilciklerleBul(string $ulkeKodu): Collection
    {
        return Temsilcilik::query()
            ->aktif()
            ->where('country_code', $ulkeKodu)
            ->orderBy('sort_order')
            ->limit(self::SONUC_LIMIT)
            ->get()
            ->map(fn (Temsilcilik $t): array => [
                'baslik' => $t->ad,
                'altbaslik' => $this->temsilcilikAltBaslik($t),
                'url' => route('rehber.temsilcilik', [strtolower($t->country_code), $t->slug]),
            ]);
    }

    private function temsilcilikAltBaslik(Temsilcilik $temsilcilik): string
    {
        return $temsilcilik->sehir.' — temsilcilik bilgileri';
    }

    /**
     * @param  \Closure(Builder<TemsilcilikIslemi>): mixed  $filtre
     * @return Collection<int, array{baslik: string, altbaslik: string, url: string}>
     */
    private function sorgula(\Closure $filtre): Collection
    {
        $query = TemsilcilikIslemi::query()
            ->yayinda()
            ->whereHas('islemTuru', fn ($t) => $t->where('is_active', true))
            ->whereHas('temsilcilik', fn ($t) => $t->where('is_active', true));

        $filtre($query);

        return $query->with(['islemTuru', 'temsilcilik'])
            ->limit(self::SONUC_LIMIT)
            ->get()
            ->map(fn (TemsilcilikIslemi $islem): array => [
                'baslik' => $islem->islemTuru->ad,
                'altbaslik' => $this->altBaslik($islem->temsilcilik),
                'url' => route('rehber.islem', [
                    strtolower($islem->temsilcilik->country_code),
                    $islem->temsilcilik->slug,
                    $islem->islemTuru->slug,
                ]),
            ]);
    }

    private function altBaslik(Temsilcilik $temsilcilik): string
    {
        return $temsilcilik->ad.' — '.$temsilcilik->sehir;
    }

    private function dogrulaUlke(?string $kod): ?string
    {
        if ($kod === null || trim($kod) === '') {
            return null;
        }

        $kod = strtoupper(trim($kod));

        return $this->aktifUlkeKodlari()->contains($kod) ? $kod : null;
    }

    private function dogrulaIslemTuru(?string $slug): ?string
    {
        if ($slug === null || trim($slug) === '') {
            return null;
        }

        return $this->aktifIslemTuruSluglari()->contains($slug) ? $slug : null;
    }

    /** @return Collection<int, string> */
    private function aktifUlkeKodlari(): Collection
    {
        return Country::query()->where('is_active', true)->pluck('code');
    }

    /** @return Collection<int, string> */
    private function aktifIslemTuruSluglari(): Collection
    {
        return IslemTuru::query()->where('is_active', true)->pluck('slug');
    }
}
