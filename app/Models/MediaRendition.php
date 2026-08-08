<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Bir ana kopyadan BELİRLİ BİR SLOT için üretilmiş dosya.
 *
 * Yüzeylerin gösterdiği şey budur. Ana kopya değişmediği sürece türev
 * silinip yeniden üretilebilir — bu yüzden türevde saklanan hiçbir bilgi
 * "kaynak" niteliği taşımaz.
 */
class MediaRendition extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_asset_id', 'slot', 'yol', 'en', 'boy', 'bayt', 'bicim', 'kalite',
    ];

    protected function casts(): array
    {
        return [
            'en' => 'integer',
            'boy' => 'integer',
            'bayt' => 'integer',
            'kalite' => 'integer',
        ];
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    /**
     * Slotun ağırlık hedefini tutuyor mu?
     *
     * Tutmuyorsa dosya yine de yazılmıştır (sessizce reddetmek, sessizce
     * devasa dosya bırakmak kadar yanlış olurdu) — panel uyarır, sahip karar
     * verir. Bu yüzden "geçersiz" değil "hedefi aşıyor" bilgisi.
     */
    public function hedefiTutuyorMu(): bool
    {
        $azami = (int) config("media_slots.{$this->slot}.azami_kb", 0);

        return $azami <= 0 || (int) $this->bayt <= $azami * 1024;
    }

    /** Herkese açık adres — yüzeyler YALNIZ bunu kullanır. */
    public function url(): string
    {
        return Storage::disk('public')->url($this->yol);
    }
}
