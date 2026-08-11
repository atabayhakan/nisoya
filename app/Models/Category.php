<?php

namespace App\Models;

use App\Enums\CategoryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @property-read int|null $gercek_ilan_sayisi `withCount` ile iliştirilen
 *                                             sayım (acil menüsü). Kolon
 *                                             değil, o yüzden docblock'suz
 *                                             kalınca statik analiz null sanar.
 */
class Category extends Model
{
    /** Header'daki "Acil" hızlı-erişim butonunun listelediği kategori grubu. */
    public const EMERGENCY_SLUG = 'acil-yardim';

    /*
     * ANAHTARDA SÜRÜM VAR (v2, 2026-08-12). Cache `rememberForever`, yani
     * dizinin ŞEKLİ değiştiğinde eski kayıt kendiliğinden düşmez: deploy
     * sonrası yeni kod, eski anahtardaki eksik alanlı diziyi okumaya devam
     * ederdi. Payload'a `gercek_ilan_sayisi` eklendiği için anahtar
     * yükseltildi. Şekli bir daha değiştiren, sürümü de yükseltsin.
     */
    public const EMERGENCY_CACHE_KEY = 'emergency_categories_v2';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'icon',
        'type',
        'sort_order',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::EMERGENCY_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::EMERGENCY_CACHE_KEY));
    }

    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }
}
