<?php

namespace App\Models;

use App\Enums\ContactCategory;
use App\Enums\ContactMessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ContactCategory $category
 * @property ContactMessageStatus $status
 */
class ContactMessage extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'category',
        'message',
        'status',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'category' => ContactCategory::class,
            'status' => ContactMessageStatus::class,
        ];
    }

    /** Mesajı gönderdiğinde oturum açmışsa ilişkili üye (opsiyonel). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
