<?php

namespace App\Policies;

use App\Models\FootballPlayerRequest;
use App\Models\User;

class FootballPlayerRequestPolicy
{
    public function update(User $user, FootballPlayerRequest $request): bool
    {
        return (int) $user->id === (int) $request->user_id || ($user->role?->canAccessAdminPanel() ?? false);
    }

    public function delete(User $user, FootballPlayerRequest $request): bool
    {
        return (int) $user->id === (int) $request->user_id || ($user->role?->canAccessAdminPanel() ?? false);
    }
}
