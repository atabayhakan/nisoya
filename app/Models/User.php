<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laragear\WebAuthn\WebAuthnAuthentication;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail, WebAuthnAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, LogsActivity, Notifiable, WebAuthnAuthentication;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'avatar_path',
        'avatar_cropped_path',
        'avatar_focal_x',
        'avatar_focal_y',
        'avatar_crop_x',
        'avatar_crop_y',
        'avatar_crop_size',
        'bio',
        'skills',
        'is_searchable',
        'job_category_id',
        'account_type',
        'country_code',
        'city',
        'preferred_currency',
        'role',
        'is_verified',
        'status',
        'last_seen_at',
        'referral_code',
        'referred_by',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'is_searchable' => 'boolean',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'account_type' => AccountType::class,
            'skills' => 'array',
            'avatar_focal_x' => 'integer',
            'avatar_focal_y' => 'integer',
            'avatar_crop_x' => 'integer',
            'avatar_crop_y' => 'integer',
            'avatar_crop_size' => 'integer',
        ];
    }

    /** Avatar görselinin kırpma odağı (bkz. x-avatar bileşeni, "sürükleyerek hizala"). */
    public function avatarObjectPosition(): string
    {
        return $this->avatar_focal_x.'% '.$this->avatar_focal_y.'%';
    }

    /**
     * Gösterilecek avatar dosyası: varsa sunucuda üretilmiş KARE kırpım
     * (yakınlaştırmalı hizalama), yoksa orijinal (eski odak-noktası sistemi
     * ile object-position uygulanır — bkz. x-avatar bileşeni). Kırpım henüz
     * yapılmamış eski avatarlar böylece davranış değiştirmeden görünmeye
     * devam eder.
     */
    public function avatarDisplayPath(): ?string
    {
        return $this->avatar_cropped_path ?? $this->avatar_path;
    }

    /** Eski (kırpımsız) avatarlarda object-position gerekli mi? */
    public function avatarUsesLegacyFocal(): bool
    {
        return $this->avatar_cropped_path === null;
    }

    protected static function booted(): void
    {
        // Her yeni kullanıcıya benzersiz bir davet kodu üret.
        static::creating(function (User $user) {
            if (empty($user->referral_code)) {
                $user->referral_code = static::generateReferralCode();
            }
        });
    }

    /** Çakışmayan, okunabilir bir davet kodu üretir. */
    public static function generateReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (static::query()->where('referral_code', $code)->exists());

        return $code;
    }

    /** Bu kullanıcının paylaşacağı davet bağlantısı. */
    public function referralUrl(): string
    {
        return url('/kayit').'?ref='.$this->referral_code;
    }

    /** Filament yönetim paneline erişim kontrolü. */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->status === UserStatus::Silinmis) {
            return false;
        }

        return $this->role?->canAccessAdminPanel() ?? false;
    }

    /** Activity log: yalnızca önemli alan değişikliklerini logla. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'role', 'is_verified', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Kullanıcı {$eventName}");
    }

    // İlişkiler

    /** @return HasMany<Listing, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /** Üyenin oluşturduğu davetiye etkinlikleri. @return HasMany<Event, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /** Kullanıcının kendi ödeme linkleri/QR kodları (Nisoya para akışını görmez). */
    public function paymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class);
    }

    /** Kullanıcının profil portfolyosundaki geçmiş iş örnekleri. */
    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class)->orderBy('sort_order');
    }

    /** Kullanıcının "Gurbet Günlüğü" hikaye gönderileri (onay bekleyen/onaylı/reddedilen). */
    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    /**
     * Kurumsal hesabın şirket profili (varsa).
     *
     * @return HasOne<Company, $this>
     */
    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    /**
     * Kullanıcının iş ilanlarına yaptığı başvurular (aday olarak).
     *
     * @return HasMany<JobApplication, $this>
     */
    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /** Kurumsal (işveren) hesap mı? */
    public function isEmployer(): bool
    {
        return $this->account_type === AccountType::Kurumsal;
    }

    /**
     * Yetenek Havuzu'ndaki uzmanlık alanı (varsa).
     *
     * @return BelongsTo<JobCategory, $this>
     */
    public function jobCategory(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class);
    }

    /**
     * Kullanıcının yer imlerine eklediği iş ilanları (aday olarak).
     *
     * @return HasMany<JobBookmark, $this>
     */
    public function jobBookmarks(): HasMany
    {
        return $this->hasMany(JobBookmark::class);
    }

    /**
     * Kullanıcının (işveren olarak) iş ilanları için yaptığı öne çıkarma talepleri.
     *
     * @return HasMany<JobFeatureRequest, $this>
     */
    public function jobFeatureRequests(): HasMany
    {
        return $this->hasMany(JobFeatureRequest::class);
    }

    /**
     * Kullanıcının yaptığı şirket değerlendirmeleri (aday olarak).
     *
     * @return HasMany<CompanyReview, $this>
     */
    public function companyReviewsGiven(): HasMany
    {
        return $this->hasMany(CompanyReview::class, 'reviewer_id');
    }

    /**
     * Kullanıcının kayıtlı iş ilanı aramaları (uyarılar).
     *
     * @return HasMany<JobSavedSearch, $this>
     */
    public function jobSavedSearches(): HasMany
    {
        return $this->hasMany(JobSavedSearch::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function favoriteListings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'favorites')->withTimestamps();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /** Kullanıcının aldığı değerlendirmeler. */
    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    /** Kullanıcının yaptığı değerlendirmeler. */
    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    /** Bu kullanıcıyı davet eden kişi. */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /** Bu kullanıcının davet ettiği kişiler. */
    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }
}
