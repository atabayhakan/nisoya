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
        'video_url',
        'tagline',
        'about',
        'website',
        'sector',
        'company_size',
        'founded_year',
        'country_code',
        'city',
        'address',
        'social_linkedin',
        'social_instagram',
        'social_whatsapp',
        'social_twitter',
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

    public function averageRating(): float
    {
        // Yalnızca yayında olan yorumlar — moderatörün gizlediği (gizli)
        // yorumlar ortalamayı kirletmemeli (herkese açık sayfayla tutarlı).
        return round((float) $this->reviews()->where('status', 'yayinda')->avg('rating') ?: 0.0, 1);
    }

    /** @return HasMany<CompanyReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(CompanyReview::class);
    }

    /** @return HasMany<CompanyGalleryImage, $this> */
    public function galleryImages(): HasMany
    {
        return $this->hasMany(CompanyGalleryImage::class)->orderBy('sort_order');
    }

    /** Logo URL'i (yoksa null). */
    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
