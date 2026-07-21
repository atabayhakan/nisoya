<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\DealStatus;
use App\Enums\ReviewStatus;
use App\Enums\TrustTier;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laragear\WebAuthn\WebAuthnAuthentication;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property UserStatus $status UserStatus::class cast (bkz. casts())
 * @property array<int, string>|null $account_recovery_codes encrypted:array cast (bkz. casts())
 */
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
        'account_recovery_codes',
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
            // 2FA sırları veritabanında şifreli saklanır (DB sızıntısı TOTP
            // secret'ını/yedek kodları ifşa etmesin). recovery_codes JSON
            // string olarak yazılıp okunuyor (bkz. TwoFactorController); 'encrypted'
            // düz string cast'i bu JSON'u olduğu gibi şifreleyip çözer.
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
            // Hesap kurtarma kodları: bcrypt hash dizisi, dinlenirken şifreli.
            'account_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
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
     *
     * avatar_path NULL ise HER ZAMAN NULL döner — admin panelinden fotoğraf
     * kaldırıldığında (avatar_cropped_path o akışta dokunulmadan kalır)
     * "yetim" kırpım dosyasının site genelinde görünmeye devam etmesini
     * engeller (2026-07-17 karşıt inceleme raporu).
     */
    public function avatarDisplayPath(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

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

    /** Tam yetkili yönetici mi? (Gate::before ile süper-kullanıcı; rol/AI/kod
     *  alanları yalnızca buna açık.) */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** İçerik moderatörü mü? (İçerik moderasyonu yapabilir; kullanıcı yönetimi,
     *  AI anahtarı ve ham kod enjeksiyonu YAPAMAZ — bkz. isAdmin.) */
    public function isModerator(): bool
    {
        return $this->role === UserRole::Moderator;
    }

    /** 2FA etkin ve onaylı mı? (Giriş akışı buna göre challenge'a yönlendirir.) */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /**
     * Tek kullanımlık 2FA yedek kodlarından birini tüket. Kod geçerliyse
     * listeden çıkarılıp kaydedilir ve true döner; aksi halde false.
     * (Eski TwoFactorController::useRecoveryCode ölü koduyla aynı mantık —
     * artık login challenge akışından kullanılıyor.)
     */
    public function consumeRecoveryCode(string $code): bool
    {
        $codes = json_decode($this->two_factor_recovery_codes ?? '[]', true) ?: [];
        $index = array_search($code, $codes, true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $this->update(['two_factor_recovery_codes' => json_encode(array_values($codes))]);

        return true;
    }

    /**
     * Hesap kurtarma kodları üretir (eskiler geçersiz olur) ve DÜZ-METİN kodları
     * döndürür — bunlar yalnızca bir kez gösterilir. Saklananlar bcrypt hash'idir;
     * kolon ayrıca şifrelidir (bkz. $casts). Parola + e-posta birlikte
     * kaybedildiğinde e-postaya ihtiyaç duymadan parola sıfırlamayı sağlar
     * (bkz. AccountRecoveryController, Kurtarma Kiti sayfası).
     *
     * @return array<int,string> düz-metin kodlar (bir daha erişilemez)
     */
    public function generateAccountRecoveryCodes(int $count = 8): array
    {
        $plain = [];
        $hashed = [];

        for ($i = 0; $i < $count; $i++) {
            $code = Str::upper(Str::random(5)).'-'.Str::upper(Str::random(5));
            $plain[] = $code;
            $hashed[] = Hash::make($code);
        }

        $this->forceFill(['account_recovery_codes' => $hashed])->save();

        return $plain;
    }

    /**
     * Tek kullanımlık hesap kurtarma kodunu tüketir. Eşleşirse listeden çıkarılıp
     * kaydedilir ve true döner; aksi halde false. Girdi büyük harfe normalize edilir.
     */
    public function consumeAccountRecoveryCode(string $code): bool
    {
        $code = Str::upper(trim($code));
        $hashes = $this->account_recovery_codes ?? [];

        foreach ($hashes as $index => $hash) {
            if (Hash::check($code, $hash)) {
                unset($hashes[$index]);
                $this->forceFill(['account_recovery_codes' => array_values($hashes)])->save();

                return true;
            }
        }

        return false;
    }

    /** Kalan (kullanılmamış) hesap kurtarma kodu sayısı. */
    public function accountRecoveryCodesRemaining(): int
    {
        return count($this->account_recovery_codes ?? []);
    }

    /** İstek başına bir kez hesaplanan güven profili önbelleği (bkz. trustProfile). */
    protected ?array $trustProfileCache = null;

    /**
     * Satıcının HESAPLANMIŞ güven profili — tamamen objektif, taklit edilemez
     * sinyallerden türetilir (admin'in keyfî `is_verified` boolean'ından farklı,
     * bkz. TrustTier). Tek bir toplu sorguyla değerlendirme sayısı/ortalamasını
     * çeker ve istek başına önbelleğe alır (profil/ilan detay tek-kayıt
     * sayfalarında kullanılır, kart listelerinde değil → N+1 riski yok).
     *
     * @return array{tier: TrustTier, review_count: int, avg: float, age_days: int, email_verified: bool, profile_complete: bool}
     */
    public function trustProfile(): array
    {
        if ($this->trustProfileCache !== null) {
            return $this->trustProfileCache;
        }

        $reviewCount = $this->reviewsReceived()
            ->where('status', ReviewStatus::Yayinda->value)
            ->count();
        $avg = $reviewCount > 0
            ? round((float) $this->reviewsReceived()->where('status', ReviewStatus::Yayinda->value)->avg('rating'), 1)
            : 0.0;
        $ageDays = (int) abs($this->created_at?->diffInDays(now()) ?? 0);
        $emailVerified = $this->email_verified_at !== null;
        $profileComplete = filled($this->avatar_path) && filled($this->bio) && filled($this->country_code);

        // Tamamlanmış anlaşma sayısı (K-C) — gerçek işlem sinyali, kademeleri
        // güçlendirir. Alıcı ya da satıcı olarak fark etmez.
        $completedDeals = Deal::query()
            ->where('status', DealStatus::Tamamlandi->value)
            ->where(fn ($q) => $q->where('seller_id', $this->id)->orWhere('buyer_id', $this->id))
            ->count();

        $tier = match (true) {
            $ageDays >= 60 && $avg >= 4.0 && ($reviewCount >= 5 || $completedDeals >= 5) => TrustTier::Guvenilir,
            $emailVerified && $profileComplete && ($reviewCount >= 1 || $completedDeals >= 1 || $ageDays >= 30) => TrustTier::Uye,
            default => TrustTier::Yeni,
        };

        return $this->trustProfileCache = [
            'tier' => $tier,
            'review_count' => $reviewCount,
            'avg' => $avg,
            'age_days' => $ageDays,
            'email_verified' => $emailVerified,
            'profile_complete' => $profileComplete,
            'completed_deals' => $completedDeals,
        ];
    }

    /** Satıcının hesaplanmış güven kademesi. */
    public function trustTier(): TrustTier
    {
        return $this->trustProfile()['tier'];
    }

    /** Satıcı "yeni/değerlendirilmemiş" mi? (Ödeme uyarısı buna göre sertleşir.) */
    public function isNewSeller(): bool
    {
        return $this->trustTier()->isNew();
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
