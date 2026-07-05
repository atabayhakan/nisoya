<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobBookmark extends Model
{
    protected $fillable = [
        'user_id',
        'job_listing_id',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<JobListing, $this> */
    public function jobListing(): BelongsTo
    {
        return $this->belongsTo(JobListing::class);
    }
}
