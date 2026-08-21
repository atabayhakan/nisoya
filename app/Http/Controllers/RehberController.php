<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Models\YasamKonuIcerigi;
use Illuminate\View\View;

/**
 * Ülke-Adaptif Rehber — herkese açık yüzey (F1).
 *
 * URL şeması (tasarım §3): /{ulke} → /{ulke}/{temsilcilik} → /{ulke}/{temsilcilik}/{islem}
 * Ülke segmenti countries.code'un küçük harflisidir (/de = DE); temsilcilik
 * ve işlem segmentleri slug'dır. Rotalar catch-all /{slug}'dan ÖNCE kayıtlı
 * ve {ulke} [a-z]{2} desenine kısıtlı — 2 harfli CMS slug'ı zaten yasak (K5).
 *
 * YALNIZ YAYINDAKİ işlem kayıtları gösterilir: taslak içerik doğrulanmamış
 * içeriktir ve yanlış evrak listesi güven markasını tersine çevirir (K7).
 */
class RehberController extends Controller
{
    public function ulke(string $ulke): View
    {
        $country = $this->aktifUlke($ulke);

        $temsilcilikler = Temsilcilik::query()
            ->aktif()
            ->where('country_code', $country->code)
            ->withCount(['islemler as yayinda_islem_sayisi' => fn ($q) => $q->yayinda()])
            ->orderBy('sort_order')
            ->orderBy('ad')
            ->get();

        // Yaşam Rehberi bloğu (2026-08-21, tasarım §5): ayrı veri modeli,
        // yalnız o ülkede yayında içerik VARSA gösterilir — boş bir link
        // "hazırlanıyor" yalanı olurdu (K6).
        $yasamRehberiVar = YasamKonuIcerigi::query()->yayinda()->where('country_code', $country->code)->exists();

        return view('rehber.ulke', [
            'country' => $country,
            'temsilcilikler' => $temsilcilikler,
            'yasamRehberiVar' => $yasamRehberiVar,
        ]);
    }

    public function temsilcilik(string $ulke, string $temsilcilik): View
    {
        $country = $this->aktifUlke($ulke);
        $kayit = $this->temsilcilikBul($country, $temsilcilik);

        $islemler = $kayit->islemler()
            ->yayinda()
            ->with('islemTuru')
            ->get()
            ->filter(fn (TemsilcilikIslemi $i) => $i->islemTuru?->is_active)
            ->sortBy(fn (TemsilcilikIslemi $i) => [$i->islemTuru->sort_order, $i->islemTuru->ad])
            ->values();

        return view('rehber.temsilcilik', [
            'country' => $country,
            'temsilcilik' => $kayit,
            'islemler' => $islemler,
        ]);
    }

    public function islem(string $ulke, string $temsilcilik, string $islem): View
    {
        $country = $this->aktifUlke($ulke);
        $kayit = $this->temsilcilikBul($country, $temsilcilik);

        $islemKaydi = $kayit->islemler()
            ->yayinda()
            ->whereHas('islemTuru', fn ($q) => $q->where('is_active', true)->where('slug', $islem))
            ->with('islemTuru')
            ->firstOrFail();

        return view('rehber.islem', [
            'country' => $country,
            'temsilcilik' => $kayit,
            'islemKaydi' => $islemKaydi,
        ]);
    }

    private function aktifUlke(string $ulke): Country
    {
        return Country::query()
            ->where('code', strtoupper($ulke))
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function temsilcilikBul(Country $country, string $slug): Temsilcilik
    {
        return Temsilcilik::query()
            ->aktif()
            ->where('country_code', $country->code)
            ->where('slug', $slug)
            ->firstOrFail();
    }
}
