<?php

namespace App\Services;

use App\Models\SssSorusu;
use Illuminate\Support\Collection;

/**
 * Nisoya AI arama — SSS tarafı sorgu katmanı. Rehber/Yaşam'ın aksine ÜLKE
 * YOK, KATEGORİ YOK: SSS soruları platform-geneli (bkz. gerçek 5 soru —
 * ücretsiz mi, ödeme nasıl, güven, ilan görünürlüğü — hiçbiri ülkeye özgü
 * değil). Tek boyut: anahtar kelime, soru+cevap metnine karşı.
 *
 * AI ÇAĞIRMAZ — yorumlama `NisoyaAiYonlendirici`'de. K7: yalnız
 * `is_active` kayıtlar döner.
 */
class SssDogalDilArama
{
    private const SONUC_LIMIT = 5;

    /**
     * @param  list<string>  $anahtarKelimeler
     * @return Collection<int, array{baslik: string, altbaslik: string, url: string}>
     */
    public function ara(array $anahtarKelimeler): Collection
    {
        if ($anahtarKelimeler === []) {
            return collect();
        }

        return SssSorusu::query()
            ->aktif()
            ->where(function ($q) use ($anahtarKelimeler) {
                foreach ($anahtarKelimeler as $kelime) {
                    $kelime = trim($kelime);
                    if ($kelime === '') {
                        continue;
                    }

                    $q->orWhere('soru', 'like', "%{$kelime}%")
                        ->orWhere('cevap', 'like', "%{$kelime}%");
                }
            })
            ->orderBy('sort_order')
            ->limit(self::SONUC_LIMIT)
            ->get()
            ->map(fn (SssSorusu $soru): array => [
                'baslik' => $soru->soru,
                'altbaslik' => $this->altBaslik(),
                'url' => $this->sonucUrl($soru),
            ]);
    }

    /** Ayrı metot: bkz. YasamDogalDilArama::kategoriAltBaslik() docblock'u. */
    private function altBaslik(): string
    {
        return 'Sıkça Sorulan Sorular';
    }

    /**
     * Ayrı metot: `route(...).'#soru-'.$id` birleştirmesi PHPStan'da
     * `non-falsy-string`e daralıyor (düz `string` değil) — aynı Collection
     * kovaryant-olmama sorunu, `altBaslik()` ile aynı çözüm.
     */
    private function sonucUrl(SssSorusu $soru): string
    {
        return route('pages.dynamic', 'sss').'#soru-'.$soru->id;
    }
}
