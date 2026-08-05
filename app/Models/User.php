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
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property UserStatus $status UserStatus::class cast (bkz. casts())
 * @property array<int, string>|null $account_recovery_codes encrypted:array cast (bkz. casts())
 */
class User extends Authenticatable implements FilamentUser, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, LogsActivity, Notifiable, PasskeyAuthenticatable;

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
        'is_demo',
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
            'is_demo' => 'boolean',
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

    /**
     * Filament yönetim paneline erişim kontrolü.
     *
     * -----------------------------------------------------------------------
     * NEDEN "AKTİF DEĞİLSE HAYIR" (2026-08-05)
     *
     * Burada eskiden yalnız `Silinmis` engelleniyordu; ASKIYA ALINMIŞ bir
     * yönetici veya moderatör /yonetim'e girmeye devam ediyordu.
     *
     * Site tarafında bu boşluk yoktu — `EnsureUserIsActive` middleware'i
     * askıdaki kullanıcıyı oturumdan atıyor. Ama o middleware `web` grubunda
     * ve Filament paneli KENDİ yığınını tanımlıyor; oraya hiç uğramıyor.
     * Yani "askıya al" düğmesi, tam da en çok işe yarayacağı yerde — yetkili
     * bir hesabı durdurmak istediğinde — çalışmıyordu.
     *
     * Düzeltme middleware'e değil buraya kondu: `canAccessPanel()` hem
     * Filament'in Authenticate middleware'inden hem de panelin kendi
     * kontrollerinden geçen TEK kapı. Yeni bir panel eklense de kural gelir.
     *
     * Beyaz liste (yalnız Aktif) kullanılıyor: yarın yeni bir UserStatus
     * eklenirse varsayılan davranış "içeri alma" olsun — kara liste olsaydı
     * yeni durum sessizce erişim kazanırdı.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->status !== UserStatus::Aktif) {
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
        $hashes = $this->hesapKurtarmaKodlari();

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
        return count($this->hesapKurtarmaKodlari());
    }

    /**
     * Kurtarma kodları OKUNABİLİR mi? (false → alan dolu ama çözülemiyor)
     *
     * Sayı 0 olması iki apayrı durumu anlatır: "hiç kod üretmedin" ve "kodların
     * var ama okunamıyor". İkincisi acil bir uyarı gerektirir, birincisi
     * gerektirmez — ekranda ayırt edilebilsin diye ayrı bir soru.
     */
    public function hesapKurtarmaKodlariOkunabilirMi(): bool
    {
        return $this->hesapKurtarmaKodlariCoz() !== null;
    }

    /**
     * Kurtarma kodlarını GÜVENLE okur.
     *
     * -----------------------------------------------------------------------
     * NEDEN try/catch — 2026-08-05'te canlıda /yonetim/kurtarma-kiti 500 verdi
     *
     * `account_recovery_codes` alanı `encrypted:array` cast'i taşır. Çözülemeyen
     * bir değer (APP_KEY değişmiş, elle yazılmış ya da bozulmuş satır) OKUMA
     * anında DecryptException fırlatır ve sayfayı komple düşürür.
     *
     * Bu, tüm sayfalar arasında en kötü yerdeki hata: Kurtarma Kiti tam da
     * işler bozulduğunda açılması gereken ekran. Kilitlenmeye karşı güvence
     * olarak tasarlanmış bir sayfanın tek bozuk satır yüzünden erişilemez
     * olması, güvencenin kendisini yok eder.
     *
     * Hata YUTULMUYOR: boş liste dönülür, `hesapKurtarmaKodlariOkunabilirMi()`
     * false der ve ekran "kodların okunamıyor, yeniden üret" uyarısı basar.
     * Sessizce 0 göstermek, sahibin olmayan bir güvenceye güvenmesine yol
     * açardı — çökmekten daha tehlikeli olan tek şey budur.
     *
     * @return array<int, string>
     */
    private function hesapKurtarmaKodlari(): array
    {
        return $this->hesapKurtarmaKodlariCoz() ?? [];
    }

    /**
     * Ham (şifreli) değeri çözer. null = ÇÖZÜLEMEDİ, [] = kod yok.
     *
     * Şifre çözme ELDEN yapılıyor, `$this->account_recovery_codes` sihirli
     * erişimiyle değil. Sebep: sihirli erişimde statik analiz istisnanın
     * atılabileceğini göremez ve catch bloğunu "ölü kod" sanar. Burada
     * `Crypt::decrypt()` doğrudan çağrıldığı için niyet hem okuyucuya hem
     * araca açık.
     *
     * `getAttributes()` kullanılır, `getRawOriginal()` değil: ilki modele
     * AZ ÖNCE yazılmış (henüz kaydedilmemiş) değeri de görür.
     *
     * Boş liste ile çözülemeyen değeri ayırmak ŞART: sekiz kodun tamamı
     * kullanılmışsa alan geçerli ama boştur — bu bir arıza değildir ve
     * kırmızı uyarı çıkarmamalıdır.
     *
     * @return array<int, string>|null
     */
    private function hesapKurtarmaKodlariCoz(): ?array
    {
        $ham = $this->getAttributes()['account_recovery_codes'] ?? null;

        if ($ham === null || $ham === '') {
            return [];
        }

        try {
            $json = Crypt::decrypt((string) $ham, false);
        } catch (DecryptException) {
            return null;
        }

        $liste = json_decode((string) $json, true);

        return is_array($liste) ? $liste : null;
    }

    /** İstek başına bir kez hesaplanan güven profili önbelleği (bkz. trustProfile). */
    protected ?array $trustProfileCache = null;

    /**
     * Okunmamış mesaj/bildirim sayıları — istek başına BİR KEZ.
     *
     * Bu iki sayı zaten her sayfa yüklemesinde koşuyordu: mesaj sayısı mobil
     * sekme çubuğunda, bildirim sayısı header zilinde. Panel de aynı sayılara
     * ihtiyaç duyunca üçüncü ve dördüncü kez sorgulanacaktı; tek kaynağa
     * bağlanınca panelin bu iki sinyali SIFIR ek sorguya mal oluyor.
     *
     * ÖRNEK property, static DEĞİL: static olsaydı PHP sürecinde istekler
     * arasında sıfırlanmaz, feature testinde ikinci istek ilkinin sayısını
     * okur ve sahte yeşil üretirdi. auth()->user() istek boyunca aynı örneği
     * döndürdüğü için örnek property doğru davranışı verir.
     */
    protected ?int $okunmamisMesajCache = null;

    protected ?int $okunmamisBildirimCache = null;

    /**
     * Satıcının HESAPLANMIŞ güven profili — tamamen objektif, taklit edilemez
     * sinyallerden türetilir (admin'in keyfî `is_verified` boolean'ından farklı,
     * bkz. TrustTier). Tek bir toplu sorguyla değerlendirme sayısı/ortalamasını
     * çeker ve istek başına önbelleğe alır (profil/ilan detay tek-kayıt
     * sayfalarında kullanılır, kart listelerinde değil → N+1 riski yok).
     *
     * @return array{tier: TrustTier, review_count: int, avg: float, age_days: int, email_verified: bool, profile_complete: bool, completed_deals: int, qualified_reviews: int}
     */
    /**
     * Okunmamış mesaj sayısı.
     *
     * Tanım MessageController@show ile BİREBİR aynı olmak zorundur — okundu
     * işaretlemesini yapan yer orası; tanımlar ayrışırsa rozet hiç sönmez.
     */
    public function okunmamisMesajSayisi(): int
    {
        return $this->okunmamisMesajCache ??= Message::query()
            ->where('sender_id', '!=', $this->id)
            ->whereNull('read_at')
            ->whereHas('conversation', fn ($q) => $q->where('user_one_id', $this->id)->orWhere('user_two_id', $this->id))
            ->count();
    }

    /** Okunmamış bildirim sayısı (header zili + panel aynı kaynaktan okur). */
    public function okunmamisBildirimSayisi(): int
    {
        return $this->okunmamisBildirimCache ??= $this->unreadNotifications()->count();
    }

    public function trustProfile(): array
    {
        if ($this->trustProfileCache !== null) {
            return $this->trustProfileCache;
        }

        /*
         * Sayı + ortalama + NİTELİKLİ sayısı TEK toplama sorgusunda: nitelikli
         * ölçütü (aşağıda) ayrı sorgu olarak eklenseydi ilan detayı/profil
         * sorgu bütçeleri şişerdi; birleşince eski iki sorgu bire indi.
         * Tarih eşiği SQL fonksiyonu değil PHP'den bağlanan parametre —
         * SQLite (test) ile MySQL (canlı) arasında taşınabilirlik için.
         */
        $ozet = $this->reviewsReceived()
            ->where('reviews.status', ReviewStatus::Yayinda->value)
            ->leftJoin('users as yorumcu', 'yorumcu.id', '=', 'reviews.reviewer_id')
            ->selectRaw(
                'COUNT(*) as adet, AVG(reviews.rating) as ortalama, '
                .'SUM(CASE WHEN yorumcu.email_verified_at IS NOT NULL '
                .'AND (reviews.deal_id IS NOT NULL OR yorumcu.created_at <= ?) THEN 1 ELSE 0 END) as nitelikli',
                [now()->subDays(7)],
            )
            ->toBase()
            ->first();

        $reviewCount = (int) ($ozet->adet ?? 0);
        $avg = $reviewCount > 0 ? round((float) ($ozet->ortalama ?? 0), 1) : 0.0;
        $qualifiedReviews = (int) ($ozet->nitelikli ?? 0);
        $ageDays = (int) abs($this->created_at?->diffInDays(now()) ?? 0);
        $emailVerified = $this->email_verified_at !== null;
        $profileComplete = filled($this->avatar_path) && filled($this->bio) && filled($this->country_code);

        // Tamamlanmış anlaşma sayısı (K-C) — gerçek işlem sinyali, kademeleri
        // güçlendirir. Alıcı ya da satıcı olarak fark etmez.
        $completedDeals = Deal::query()
            ->where('status', DealStatus::Tamamlandi->value)
            ->where(fn ($q) => $q->where('seller_id', $this->id)->orWhere('buyer_id', $this->id))
            ->count();

        /*
         * GÜVENİLİR rozeti için yalnız NİTELİKLİ değerlendirmeler sayılır
         * (yukarıdaki toplama sorgusundaki CASE): değerlendiren e-postası
         * doğrulanmış VE (değerlendirme tamamlanmış bir anlaşmaya bağlı YA DA
         * değerlendiren hesabı en az 7 günlük). Gerekçe (açık işler
         * envanteri, "değerlendirme kapısı"): beş taze hesapla beş yıldız
         * basıp rozet almak mümkündü. Görünen sayı/ortalama ($reviewCount/
         * $avg) bilerek DEĞİŞMEDİ — sayfada gizlenen yorum yok, yalnız
         * rozetin kanıt eşiği sertleşti.
         */
        $tier = match (true) {
            $ageDays >= 60 && $avg >= 4.0 && ($qualifiedReviews >= 5 || $completedDeals >= 5) => TrustTier::Guvenilir,
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
            'qualified_reviews' => $qualifiedReviews,
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
