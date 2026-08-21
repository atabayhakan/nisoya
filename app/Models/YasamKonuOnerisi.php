<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bir üyenin yayındaki bir Yaşam Rehberi içeriğine gönderdiği düzeltme/ek
 * bilgi önerisi. Serbest wiki düzenlemesi DEĞİL: hiçbir öneri otomatik
 * uygulanmaz, `durum` yalnız panelden değiştirilir (bkz. tasarım K5).
 */
class YasamKonuOnerisi extends Model
{
    protected $table = 'yasam_konu_onerileri';

    public const DURUM_BEKLIYOR = 'bekliyor';

    public const DURUM_ONAYLANDI = 'onaylandi';

    public const DURUM_REDDEDILDI = 'reddedildi';

    protected $fillable = ['yasam_konu_icerigi_id', 'user_id', 'onerilen_metin', 'kaynak_url', 'durum'];

    /** @return BelongsTo<YasamKonuIcerigi, $this> */
    public function icerik(): BelongsTo
    {
        return $this->belongsTo(YasamKonuIcerigi::class, 'yasam_konu_icerigi_id');
    }

    /** @return BelongsTo<User, $this> */
    public function kullanici(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @param Builder<YasamKonuOnerisi> $query */
    public function scopeBekleyen(Builder $query): void
    {
        $query->where('durum', self::DURUM_BEKLIYOR);
    }
}
