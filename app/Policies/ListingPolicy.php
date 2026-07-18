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
}
