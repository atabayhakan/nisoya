<?php

namespace App\Models;

use App\Support\HighlightIcon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Ana sayfa "değer önerileri" bento grid'indeki büyük (2x2) ve küçük (1x1)
 * kartların dönen mesajları (bkz. docs/plans/2026-07-17-anasayfa-vurgu-slider-design.md).
 * Sadece anasayfa yüklenirken sorgulanır (Zone/NavigationLink'in aksine her
 * sayfada çalışan layout composer'a bağlı değil) — trafik hacmi düşük
 * olduğundan Cache::rememberForever + rehydrate deseni burada gerekmiyor.
 */
class HomeHighlight extends Model
{
    public const SLOT_BIG = 'buyuk';

    public const SLOT_SMALL = 'kucuk';

    protected $fillable = ['slot', 'title', 'text', 'icon', 'media', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'media' => 'array',
        ];
    }

    /** Medya (resim/video) varsa kartta ikon yerine bu gösterilir. */
    public function hasMedia(): bool
    {
        return ! empty($this->media);
    }

    /** Belirli bir konum (büyük/küçük) için aktif mesajlar, sırasına göre. */
    public static function forSlot(string $slot): Collection
    {
        return self::query()
            ->where('slot', $slot)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function heroicon(): string
    {
        return HighlightIcon::heroicon($this->icon);
    }
}
