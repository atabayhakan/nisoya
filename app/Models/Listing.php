<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\PriceUnit;
use App\Notifications\ListingStatusNotification;
use App\Support\Para;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property ListingType $type
 * @property PriceUnit|null $price_unit casts() enum'a çeviriyor; docblock'suz
 *                                      kalınca statik analiz kolonu düz `string` sanıyor ve enum
 *                                      metotlarına (suffix() vb.) erişimi hata sayıyor.
 * @property Carbon|null $featured_until
 * @property Carbon|null $unpublished_at
 * @property-read User|null $user
 */
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'category_id',
        'type',
        'title',
        'slug',
        'description',
        'price',
        'currency',
        'price_unit',
        'country_code',
        'city',
        'latitude',
        'longitude',
        'is_remote',
        'stock',
        'width_cm',
        'height_cm',
        'status',
        'unpublished_at',
        'is_demo',
        'is_featured',
        'featured_until',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'type' => ListingType::class,
            'price_unit' => PriceUnit::class,
            'status' => ListingStatus::class,
            'is_demo' => 'boolean',
            'price' => 'decimal:2',
            'latitude' => 'float',
            'longitude' => 'float',
            'is_remote' => 'boolean',
            'is_featured' => 'boolean',
            'featured_until' => 'datetime',
            'unpublished_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        /*
         * `unpublished_at` YALNIZ "Pasif" iken anlamlıdır — durum başka bir
         * şeye dönerken damga silinir.
         *
         * NEDEN: damga "bu ilanı sahibi kaldırdı" demek ve arşivin herkese
         * açık olmasının tek dayanağı o. Silinmezse şu dizi deliği geri açardı:
         * üye kaldırır (damga basılır) → yönetici panelden ilanı Aktif yapar
         * (damga kalır) → yönetici sonra Pasif'e çeker → ilan "üye kaldırdı"
         * gibi görünür ve herkese açık arşive düşer.
         *
         * Üyenin kendi geri-yayınlama yolu damgayı zaten temizliyor; bu kanca
         * aynı kuralı YÖNETİM tarafındaki her yol için de garanti eder —
         * Filament'in durum alanı da dâhil.
         */
        static::saving(function (self $listing) {
            if ($listing->isDirty('status') && $listing->status !== ListingStatus::Pasif) {
                $listing->unpublished_at = null;
            }
        });

        // İlan durumu (moderasyon) değişince sahibini bilgilendir.
        static::updated(function (self $listing) {
            if ($listing->wasChanged('status')) {
                $listing->user?->notify(new ListingStatusNotification(
                    $listing->title,
                    $listing->status->getLabel(),
                    route('listings.show', [$listing->id, $listing->slug]),
                ));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /** @return HasMany<ListingImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    /** @return HasOne<ListingImage, $this> */
    public function coverImage(): HasOne
    {
        return $this->hasOne(ListingImage::class)->where('is_cover', true);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'listing_tag');
    }

    /**
     * Emlak ilanının 1:1 detay kaydı (tip=emlak dışında null).
     *
     * @return HasOne<ListingPropertyDetail, $this>
     */
    public function propertyDetail(): HasOne
    {
        return $this->hasOne(ListingPropertyDetail::class);
    }

    /**
     * Vasıta ilanının 1:1 detay kaydı (tip=vasita dışında null).
     *
     * @return HasOne<ListingVehicleDetail, $this>
     */
    public function vehicleDetail(): HasOne
    {
        return $this->hasOne(ListingVehicleDetail::class);
    }

    /**
     * Takvimde "dolu" işaretlenen tarih aralıkları (emlak kısa dönem + kiralık araç).
     *
     * @return HasMany<ListingUnavailableRange, $this>
     */
    public function unavailableRanges(): HasMany
    {
        return $this->hasMany(ListingUnavailableRange::class)->orderBy('starts_on');
    }

    /** Verilen tarih aralığı bu ilanın takviminde tamamen boş mu? */
    public function isAvailableBetween(string $from, string $to): bool
    {
        return ! $this->unavailableRanges()->overlapping($from, $to)->exists();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function featureRequests(): HasMany
    {
        return $this->hasMany(FeatureRequest::class);
    }

    /** İlan şu an gerçekten öne çıkan mı (süre dolmamış). */
    public function isCurrentlyFeatured(): bool
    {
        return $this->is_featured && (is_null($this->featured_until) || $this->featured_until->isFuture());
    }

    /**
     * Fiyat, Türkçe biçimde — para birimi HARİÇ (çağıran taraf ekler).
     *
     * Fiyatsız ilan `null` döner; sayfalar orada "Görüşülür" basar. Biçim
     * kuralı ve neden tek yerde durduğu {@see Para} içinde.
     */
    public function bicimliFiyat(): ?string
    {
        return Para::bicimle($this->price);
    }

    /**
     * Pasif ilanı SAHİBİ mi kaldırdı (yoksa yönetim/sistem mi)?
     *
     * "Geri yayınla" düğmesinin tek kapısı bu. Ayrım `unpublished_at`
     * kolonunda taşınıyor; NEDEN gerektiğini migration açıklıyor — özeti:
     * hesap askıya alınınca ilanlar da Pasif oluyor ve o ilanları sahibinin
     * kendi düğmesiyle diriltebilmesi moderasyon kararını atlatmak olurdu.
     */
    public function isOwnerUnpublished(): bool
    {
        return $this->status === ListingStatus::Pasif && $this->unpublished_at !== null;
    }

    /**
     * İlan HERKESE AÇIK ARŞİVDE mi — yayından kalkmış ama hâlâ görülebilir.
     *
     * ---------------------------------------------------------------------
     * TANIM DARALTILDI (aynı gün, iki iş birleşirken)
     *
     * İlk yazışta bu metot `status === Pasif` diyordu ve PR #115/#116 ile
     * öyle canlıya çıktı. O tanım SESSİZ BİR MODERASYON DELİĞİ taşıyor:
     * `UserObserver::suspendActiveListings()` askıya alınan hesabın ilanlarını
     * Pasif'e çekiyor — yani ASKIYA ALINMIŞ bir hesabın ilanları, herkese açık
     * profilde "Geçmiş" sekmesinde görünür ve tek tek açılabilir hâle geliyordu.
     * Yönetimin sustuduğu içerik, arşiv kapısından geri sızıyordu.
     *
     * Artık arşiv YALNIZ ÜYENİN KENDİ KALDIRDIĞI ilanı kapsar. Yönetim/sistem
     * kaldırdıysa sayfa yine 404 verir ve hiçbir listede görünmez.
     *
     * "Kim kaldırdı" ayrımı olmadan bu iki hâl ayırt edilemezdi; taşıyıcısı
     * `unpublished_at` (bkz. {@see isOwnerUnpublished} ve migration).
     */
    public function arsivdeMi(): bool
    {
        return $this->isOwnerUnpublished();
    }

    /**
     * İlanda AI moderasyonunun işaretlediği, admin'in henüz onaylamadığı
     * görsel var mı?
     *
     * YAYINA ALMANIN ÖN KOŞULU. `ProcessListingImage::moderate()` işaretlediği
     * görselin ilanını yalnızca ilan O ANDA AKTİFSE incelemeye alır; taslak
     * ya da yayından kaldırılmış bir ilana yüklenen işaretli görsel ilanın
     * durumunu değiştirmez. O ilan sonradan yayına alınınca işaretli görsel
     * moderasyona hiç uğramadan siteye çıkardı — bu metot o boşluğu kapatır
     * (yayına alma Aktif yerine "Onay bekliyor"a düşer).
     */
    public function hasFlaggedImage(): bool
    {
        return $this->images()->where('is_flagged', true)->exists();
    }

    /**
     * Herkese açık arşivde görünebilecek ilanlar (sorgu tarafı karşılığı).
     *
     * `arsivdeMi()` tek bir modeli sorar; listeler ve SAYILAR bu scope'u
     * kullanmak zorunda. İkisi ayrı yazılırsa biri güncellenip diğeri
     * unutulur ve delik oradan geri açılır.
     */
    public function scopeOwnerUnpublished($query)
    {
        return $query->where('status', ListingStatus::Pasif->value)
            ->whereNotNull('unpublished_at');
    }

    /** Yalnızca yayında olan (aktif) ilanlar. */
    public function scopeActive($query)
    {
        return $query->where('status', ListingStatus::Aktif->value);
    }

    /**
     * ÖRNEK (demo) olmayan ilanlar — "gerçek arz".
     *
     * Bu süzgeç zaten Kâhya teşhisinde, ana sayfa sayaçlarında ve Nisoya
     * Dosyası'nda elle yazılıydı; herkese açık LİSTELERDE yoktu. Sonuç
     * ölçüldü (2026-08-08): sitemap'e giren 29 ilanın 28'i örnekti ve
     * /ilanlar?ulke=DE "12 ilan bulundu" derken 12'si de örnekti. Rozet
     * kartın üstündeydi, ama SAYI onu okumuyordu.
     *
     * Bir örnek ilanı göstermek dürüst olabilir (rozetli, mesaj kapalı);
     * onu SAYMAK ya da arama motoruna gerçek arz diye sunmak değildir.
     * Kural tek cümle: örnek ilan sayılmaz, indekslenmez, gerçeklerin
     * arasına karışmaz — yalnız kendi etiketli rafında görünür.
     *
     * Scope olarak duruyor ki kural tek isimle çağrılsın; `is_demo`
     * karşılaştırmasını her çağrı yerinde yeniden yazmak, tam da yukarıdaki
     * deliğin açılma biçimiydi.
     */
    public function scopeGercek($query)
    {
        return $query->where('is_demo', false);
    }

    /**
     * Öne çıkanları ÜSTTE sıralar — ama yalnızca SÜRESİ GEÇMEMİŞ olanları.
     * Ham is_featured'a göre sıralamak, süresi dolan ilanları günlük
     * expire komutuna kadar ~24 saate dek haksız yere üstte tutuyordu
     * (bkz. isCurrentlyFeatured / denetim). SQLite + MySQL uyumlu.
     */
    public function scopeOrderByFeatured($query)
    {
        return $query->orderByRaw(
            '(is_featured = 1 and (featured_until is null or featured_until > ?)) desc',
            [now()]
        );
    }

    /** Activity log: status + featured değişikliklerini logla. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'is_featured', 'featured_until', 'is_remote', 'stock', 'price'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "İlan {$eventName}");
    }
}
