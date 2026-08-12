<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Services\TemsiliGorselUretici;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Hizmet ilanına temsilî kapak görseli üretir.
 *
 * AYRI CONTROLLER: ListingController zaten 500 satırın üstünde ve bu akışın
 * onunla tek ortak yanı model. Buraya bakan biri "temsilî görsel nasıl
 * doğuyor" sorusunun tamamını tek dosyada görsün.
 *
 * Kapılar üst üste: yetki (kendi ilanı) → uygunluk (hizmet + görseli yok +
 * özellik açık) → hız sınırı (üretim para harcar). Uygunluk kontrolü
 * servisin içinde, çünkü aynı kontrol düğmeyi göstermek için de kullanılıyor
 * ve iki yere yazılırsa biri güncellenip diğeri unutulur.
 */
class TemsiliGorselController extends Controller
{
    public function __construct(private readonly TemsiliGorselUretici $uretici) {}

    public function store(Listing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $geri = redirect()->route('panel.listings.edit', $listing);

        if (! $this->uretici->uygunMu($listing)) {
            // Sessiz dönmek yerine sebebi söyle: kullanıcı düğmeye bastıysa
            // bir şey bekliyordur.
            return $geri->with('status', 'Bu ilana temsilî görsel eklenemez — yalnız görseli olmayan hizmet ilanları için.');
        }

        if ($this->uretici->uret($listing) === null) {
            return $geri->with('status', 'Temsilî görsel üretilemedi. Biraz sonra yeniden deneyebilir ya da kendi fotoğrafını yükleyebilirsin.');
        }

        return $geri->with('status', 'Temsilî görsel eklendi. Beğenmediysen silip kendi fotoğrafını yükleyebilirsin.');
    }
}
