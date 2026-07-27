<?php

namespace App\Models;

use App\Enums\ContactCategory;
use App\Enums\ContactMessageStatus;
use App\Enums\ContactPriority;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Destek bileti (eski adıyla "iletişim mesajı").
 *
 * @method static Builder<ContactMessage> acik()
 *
 * Public form sözleşmesi DEĞİŞMEDİ — misafir hâlâ hesapsız gönderiyor
 * (bkz. ContactMessageController). Destek alanları (öncelik/atama/kapanış/
 * yanıtlar) yalnız panel tarafında kullanılır ve hepsi varsayılanlıdır,
 * yani mevcut kayıtlar geçerliliğini korur.
 *
 * @property ContactCategory $category
 * @property ContactMessageStatus $status
 * @property ContactPriority $priority
 */
class ContactMessage extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'category',
        'priority',
        'message',
        'status',
        'assigned_to',
        'admin_note',
        'first_replied_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => ContactCategory::class,
            'status' => ContactMessageStatus::class,
            'priority' => ContactPriority::class,
            'first_replied_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** Mesajı gönderdiğinde oturum açmışsa ilişkili üye (opsiyonel). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Bileti üstlenen yönetici/moderatör. */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Panelden gönderilen yanıtlar (en yeni sonda).
     *
     * @return HasMany<ContactMessageReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ContactMessageReply::class)->oldest();
    }

    /** Hâlâ ilgilenilmesi gereken biletler (yeni + okundu). */
    public function scopeAcik(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ContactMessageStatus::Yeni->value,
            ContactMessageStatus::Okundu->value,
        ]);
    }

    /** İlk yanıta kadar geçen süre — yalnız yanıtlanmış biletlerde. */
    public function ilkYanitSuresi(): ?string
    {
        if (! $this->first_replied_at) {
            return null;
        }

        return $this->created_at->diffForHumans($this->first_replied_at, short: false, parts: 2);
    }
}
