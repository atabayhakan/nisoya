<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    // Not: Admin, AppServiceProvider'daki Gate::before ile bu policy'ye hiç
    // uğramadan zaten yetkilidir. Buradaki moderatör kontrolü içerik
    // moderasyonu içindir (sahip olmadığı ilanı düzenleyip/silebilsin).
    public function update(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id || $user->isModerator();
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id || $user->isModerator();
    }

    /**
     * "Yayından kaldır / geri yayınla" — update/delete'in AKSİNE moderatörü
     * KAPSAMAZ, yalnız sahibi.
     *
     * Bu bilinçli: update/delete moderatöre içerik müdahalesi için açık, ama
     * yayın durumu üyenin kendi kararı. Moderatörün bir ilanı susturmak için
     * yolu zaten var (yönetim panelinde durum alanı) ve o yol
     * `unpublished_at`'i doldurmaz — yani üye onu geri açamaz. Moderatörü bu
     * üye rotasına da soksaydık, moderatör kendi kararını üyenin düğmesiyle
     * geri alabilen tuhaf bir yola sahip olurdu.
     *
     * Admin buraya hiç uğramaz (AppServiceProvider'daki Gate::before).
     */
    public function manageVisibility(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id;
    }
}
