<?php

namespace App\Policies;

use App\Models\FootballTeam;
use App\Models\User;

class FootballTeamPolicy
{
    public function update(User $user, FootballTeam $team): bool
    {
        return (int) $user->id === (int) $team->user_id || ($user->role?->canAccessAdminPanel() ?? false);
    }

    public function delete(User $user, FootballTeam $team): bool
    {
        return (int) $user->id === (int) $team->user_id || ($user->role?->canAccessAdminPanel() ?? false);
    }

    public function invite(User $user, FootballTeam $team): bool
    {
        return (int) $user->id === (int) $team->user_id || ($user->role?->canAccessAdminPanel() ?? false);
    }

    public function removeMember(User $user, FootballTeam $team, User $target): bool
    {
        // Kaptan başkasını çıkarabilir ya da kullanıcı kendisi takımdan ayrılabilir
        return (int) $user->id === (int) $team->user_id
            || (int) $user->id === (int) $target->id
            || ($user->role?->canAccessAdminPanel() ?? false);
    }
}
