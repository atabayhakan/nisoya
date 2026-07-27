<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bir destek biletine panelden gönderilen yanıt.
 *
 * `admin_note` (iç not) ile KARIŞTIRILMAMALI: iç not yalnız yönetim
 * tarafında görünür, bu ise misafire gerçekten e-posta olarak gider.
 *
 * sent_at/failed_at: gönderim kuyrukta yapıldığı için "gönderdim" demek
 * yetmez — worker durmuş ya da SMTP reddetmiş olabilir. Başarısızlık
 * bilette görünür olsun diye ayrı alanlarda tutulur.
 */
class ContactMessageReply extends Model
{
    protected $fillable = [
        'contact_message_id',
        'user_id',
        'body',
        'sent_at',
        'failed_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function contactMessage(): BelongsTo
    {
        return $this->belongsTo(ContactMessage::class);
    }

    /** Yanıtı gönderen yönetici (silinmişse null). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function basarisizMi(): bool
    {
        return $this->failed_at !== null;
    }
}
