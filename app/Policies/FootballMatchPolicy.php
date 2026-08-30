<?php

namespace App\Policies;

use App\Enums\FootballResultStatus;
use App\Models\FootballMatch;
use App\Models\User;

class FootballMatchPolicy
{
    public function update(User $user, FootballMatch $match): bool
    {
        if ($user->role?->canAccessAdminPanel() ?? false) {
            return true;
        }

        return (int) $user->id === (int) $match->homeTeam?->user_id
            || (int) $user->id === (int) $match->awayTeam?->user_id;
    }

    public function respond(User $user, FootballMatch $match): bool
    {
        if ($user->role?->canAccessAdminPanel() ?? false) {
            return true;
        }

        return (int) $user->id === (int) $match->awayTeam?->user_id;
    }

    public function submitScore(User $user, FootballMatch $match): bool
    {
        if ($user->role?->canAccessAdminPanel() ?? false) {
            return true;
        }

        // Maç zaten doğrulanmışsa tekrar skor girilemez
        if ($match->result_status === FootballResultStatus::Dogrulandi) {
            return false;
        }

        $isHomeCaptain = (int) $user->id === (int) $match->homeTeam?->user_id;
        $isAwayCaptain = (int) $user->id === (int) $match->awayTeam?->user_id;

        return $isHomeCaptain || $isAwayCaptain;
    }

    public function verifyScore(User $user, FootballMatch $match): bool
    {
        if ($user->role?->canAccessAdminPanel() ?? false) {
            return true;
        }

        if ($match->result_status !== FootballResultStatus::Girildi) {
            return false;
        }

        // Skoru giren kişi KENDİ skorunu onaylayamaz — rakip kaptan onaylamalıdır
        $submitterId = (int) $match->result_submitted_by_id;
        if ((int) $user->id === $submitterId) {
            return false;
        }

        $isHomeCaptain = (int) $user->id === (int) $match->homeTeam?->user_id;
        $isAwayCaptain = (int) $user->id === (int) $match->awayTeam?->user_id;

        return $isHomeCaptain || $isAwayCaptain;
    }

    public function disputeScore(User $user, FootballMatch $match): bool
    {
        return $this->verifyScore($user, $match);
    }
}
