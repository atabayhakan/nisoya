<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'logo_path',
        'tagline',
        'about',
        'website',
        'sector',
        'company_size',
        'founded_year',
        'country_code',
        'city',
        'social_linkedin',
        'social_instagram',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'founded_year' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /** @return HasMany<JobListing, $this> */
    public function jobListings(): HasMany
    {
        return $this->hasMany(JobListing::class);
    }

    /** @return HasMany<CompanyReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(CompanyReview::class);
    }

    /** Logo URL'i (yoksa null). */
    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
