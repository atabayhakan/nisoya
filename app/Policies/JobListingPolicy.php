<?php

namespace App\Policies;

use App\Models\JobListing;
use App\Models\User;

class JobListingPolicy
{
    /** İlan, kullanıcının şirketine mi ait? */
    protected function owns(User $user, JobListing $job): bool
    {
        return $user->company !== null && $job->company_id === $user->company->id;
    }

    public function update(User $user, JobListing $job): bool
    {
        return $this->owns($user, $job);
    }

    public function delete(User $user, JobListing $job): bool
    {
        return $this->owns($user, $job);
    }

    /** İlana gelen başvuruları görme/yönetme (sadece ilan sahibi işveren). */
    public function manageApplications(User $user, JobListing $job): bool
    {
        return $this->owns($user, $job);
    }
}
