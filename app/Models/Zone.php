<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Sitenin önceden tanımlanmış noktalarına (anasayfa, ilan listesi, footer vb.)
 * admin panelinden içerik bloğu veya reklam eklenebilmesini sağlar. `key` alanı
 * kodda `<x-zone zone-key="...">` çağrısına karşılık gelir; yeni bir noktaya ilk
 * kez alan eklemek kod değişikliği gerektirir, ama var olan noktalarda içerik/
 * açma-kapama tamamen admin panelinden yönetilir. Bkz. ZoneSeeder.
 */
class Zone extends Model
{
    public const CACHE_KEY = 'zones_by_key';

    protected $fillable = [
        'key',
        'name',
        'location_note',
        'blocks',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Eloquent modelleri doğrudan cache'lenirse Laravel 13'te ikinci okumada
     * __PHP_Incomplete_Class'a döner (serializable_classes => false). Bu yüzden
     * ham attribute dizileri cache'lenir, model burada rehydrate edilir.
     *
     * @return Collection<string, self>
     */
    public static function allByKey(): Collection
    {
        $rows = Cache::rememberForever(self::CACHE_KEY, fn () => self::query()->get()->map->getAttributes()->all());

        return collect($rows)->map(function (array $attributes) {
            $zone = new self;
            $zone->exists = true;
            $zone->setRawAttributes($attributes, true);

            return $zone;
        })->keyBy('key');
    }

    public static function byKey(string $key): ?self
    {
        return self::allByKey()->get($key);
    }
}
