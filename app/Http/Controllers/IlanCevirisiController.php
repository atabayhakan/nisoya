<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Services\IlanCevirmeni;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * İlanı bulunduğu ülkenin diline çevirir.
 *
 * Kapılar: yetki (kendi ilanı) → uygunluk (haritada dil var + açıklama
 * yeterince uzun + özellik açık) → hız sınırı. Uygunluk kontrolü servisin
 * içinde; aynı kontrol düğmeyi göstermek için de kullanılıyor ve iki yere
 * yazılırsa biri güncellenip diğeri unutulur.
 */
class IlanCevirisiController extends Controller
{
    public function __construct(private readonly IlanCevirmeni $cevirmen) {}

    public function store(Listing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $geri = redirect()->route('panel.listings.edit', $listing);

        if (! $this->cevirmen->uygunMu($listing)) {
            return $geri->with('status', 'Bu ilan çevrilemiyor — ülkesi için yerel dil tanımlı değil ya da açıklaması çok kısa.');
        }

        $ceviri = $this->cevirmen->cevir($listing);

        if ($ceviri === null) {
            return $geri->with('status', 'Çeviri yapılamadı. Biraz sonra yeniden deneyebilirsin.');
        }

        return $geri->with('status', $this->cevirmen->dilAdi($ceviri->locale).' çeviri eklendi — ilan sayfanda görünecek.');
    }
}
