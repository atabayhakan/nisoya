<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'read_at',
        'recalled_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'recalled_at' => 'datetime',
        ];
    }

    /** Mesaj gönderen tarafından geri alınmış mı? */
    public function isRecalled(): bool
    {
        return $this->recalled_at !== null;
    }

    /** Arayüzde gösterilecek gövde — geri alınmışsa gerçek içerik gizlenir. */
    public function displayBody(): string
    {
        return $this->isRecalled() ? '' : $this->body;
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
