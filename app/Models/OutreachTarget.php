<?php

namespace App\Models;

use App\Services\Growth\DetectionResult;
use App\Support\Growth\RegionPolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Büyüme Ajanı'nın keşfettiği bir aday işletme. Keşif katmanı doldurur, tespit
 * motoru bandını/güvenini işler, RegionPolicy gönderim durumunu belirler. Sonraki
 * fazda (taslak+onay+gönderim) bu kayıtlar erişim kampanyalarının kaynağıdır.
 *
 * @property string $detection_band
 * @property string $marketing_status
 * @property bool $needs_review
 */
class OutreachTarget extends Model
{
    protected $fillable = [
        'name', 'category', 'owner_name', 'country', 'city', 'sector', 'website',
        'contact_email', 'source', 'external_id', 'detection_band',
        'detection_confidence', 'detection_method', 'detection_signals',
        'detection_reasoning', 'needs_review', 'marketing_status', 'status',
    ];

    protected $casts = [
        'detection_signals' => 'array',
        'detection_confidence' => 'integer',
        'needs_review' => 'boolean',
    ];

    /** Gönderime uygun (Türk + izinli bölge) adaylar. */
    public function scopeSendable($query)
    {
        return $query
            ->where('detection_band', DetectionResult::BAND_TURKISH)
            ->where('marketing_status', RegionPolicy::ALLOWED);
    }

    /** İnsan onayı bekleyen (sınırda ya da düşük güvenli) adaylar. */
    public function scopeNeedsReview($query)
    {
        return $query->where('needs_review', true);
    }
}
