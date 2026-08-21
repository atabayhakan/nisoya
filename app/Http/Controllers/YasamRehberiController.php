<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\YasamKategorisi;
use App\Models\YasamKonuIcerigi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/**
 * `/{ulke}/yasam[/{kategori}[/{konu}]]` — Ülke Rehberi'nin `/{ulke}/{temsilcilik}/{islem}`
 * üçlüsüyle aynı iskelet (aktif ülke → 404 kapısı, yalnız yayındaki içerik
 * görünür), ama iki seviye: kategori → konu. Boş kategori/konu sessizce
 * listeden düşer (bkz. tasarım K6) — "hazırlanıyor" yalanı söylenmez.
 */
class YasamRehberiController extends Controller
{
    public function kategoriler(string $ulke): View
    {
        $country = $this->aktifUlke($ulke);

        // whereHas kapanışları status'u DOĞRUDAN sütun karşılaştırmasıyla
        // kontrol ediyor (yayinda() scope'u değil) — Larastan iç içe ilişki
        // yollarında ("konular.icerikler") kapanış parametresinin tipini
        // scope'un ait olduğu modele bağlayamıyor; düz where() belirsizlik
        // bırakmıyor.
        $kategoriler = YasamKategorisi::query()
            ->aktif()
            ->whereHas('konular.icerikler', function (Builder $q) use ($country) {
                $q->where('status', YasamKonuIcerigi::STATUS_YAYIN)->where('country_code', $country->code);
            })
            ->withCount(['konular as yayinda_konu_sayisi' => function ($q) use ($country) {
                $q->whereHas('icerikler', function (Builder $qq) use ($country) {
                    $qq->where('status', YasamKonuIcerigi::STATUS_YAYIN)->where('country_code', $country->code);
                });
            }])
            ->orderBy('sort_order')
            ->orderBy('ad')
            ->get();

        return view('yasam-rehberi.kategoriler', compact('country', 'kategoriler'));
    }

    public function konular(string $ulke, string $kategoriSlug): View
    {
        $country = $this->aktifUlke($ulke);
        $kategori = $this->aktifKategori($kategoriSlug);

        $konular = $kategori->konular()
            ->aktif()
            ->whereHas('icerikler', function (Builder $q) use ($country) {
                $q->where('status', YasamKonuIcerigi::STATUS_YAYIN)->where('country_code', $country->code);
            })
            ->orderBy('sort_order')
            ->orderBy('baslik')
            ->get();

        if ($konular->isEmpty()) {
            abort(404);
        }

        return view('yasam-rehberi.konular', compact('country', 'kategori', 'konular'));
    }

    public function icerik(string $ulke, string $kategoriSlug, string $konuSlug): View
    {
        $country = $this->aktifUlke($ulke);
        $kategori = $this->aktifKategori($kategoriSlug);

        $konu = $kategori->konular()
            ->aktif()
            ->where('slug', $konuSlug)
            ->firstOrFail();

        $icerik = YasamKonuIcerigi::query()
            ->yayinda()
            ->where('yasam_konusu_id', $konu->id)
            ->where('country_code', $country->code)
            ->firstOrFail();

        return view('yasam-rehberi.icerik', compact('country', 'kategori', 'konu', 'icerik'));
    }

    private function aktifUlke(string $ulke): Country
    {
        return Country::where('code', strtoupper($ulke))->where('is_active', true)->firstOrFail();
    }

    private function aktifKategori(string $slug): YasamKategorisi
    {
        return YasamKategorisi::aktif()->where('slug', $slug)->firstOrFail();
    }
}
