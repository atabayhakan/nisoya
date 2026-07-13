<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * İlan sahibinin takvimde "dolu" işaretlediği tarih aralığı.
 * Emlak (kısa dönem) ve ileride kiralık araç ilanları ortak kullanır.
 *
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 */
class ListingUnavailableRange extends Model
{
    protected $fillable = [
        'listing_id',
        'starts_on',
        'ends_on',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Verilen tarih aralığıyla çakışma koşulu — hem scope (overlapping) hem de
     * whereDoesntHave closure'ları (generic'in Model'e düştüğü yerler) kullanır.
     */
    public static function overlapWhere(Builder $query, string $from, string $to): Builder
    {
        return $query->where('starts_on', '<=', $to)->where('ends_on', '>=', $from);
    }

    /** Verilen tarih aralığıyla çakışan kayıtlar. */
    public function scopeOverlapping(Builder $query, string $from, string $to): Builder
    {
        return self::overlapWhere($query, $from, $to);
    }
}
