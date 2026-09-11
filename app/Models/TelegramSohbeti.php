<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kâhya'nın Telegram grubunda cevapladığı bir sorunun günlük satırı.
 *
 * Tam bir arşiv değil — bkz. migration docblock'u. `needsReview` bekçinin
 * (App\Support\HassasKonuBekcisi) işaretlediği, sahibin gözden geçirmesi
 * gereken satırları döndürür.
 *
 * @property string $telegram_chat_id
 * @property bool $needs_review
 * @property ?string $kategori
 */
class TelegramSohbeti extends Model
{
    protected $table = 'telegram_sohbetleri';

    protected $fillable = [
        'telegram_chat_id', 'telegram_kullanici_id', 'telegram_kullanici_adi',
        'soru_metni', 'cevap_metni', 'kategori', 'needs_review', 'kanal',
    ];

    protected $casts = [
        'needs_review' => 'boolean',
    ];

    public function scopeNeedsReview($query)
    {
        return $query->where('needs_review', true);
    }
}
