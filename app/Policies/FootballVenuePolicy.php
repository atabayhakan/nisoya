<?php

namespace App\Policies;

use App\Models\FootballVenue;
use App\Models\User;

class FootballVenuePolicy
{
    public function update(User $user, FootballVenue $venue): bool
    {
        return (int) $user->id === (int) $venue->created_by_id || ($user->role?->canAccessAdminPanel() ?? false);
    }

    public function delete(User $user, FootballVenue $venue): bool
    {
        return (int) $user->id === (int) $venue->created_by_id || ($user->role?->canAccessAdminPanel() ?? false);
    }
}
