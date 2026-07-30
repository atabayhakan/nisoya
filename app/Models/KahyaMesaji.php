<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Sahip ile Kâhya arasındaki tek bir mesaj.
 *
 * NEDEN SAKLANIYOR: sahip birden çok projeyle çalışıyor ve kendi ifadesiyle
 * "hafızasında bazı şeyler kalmıyor". Kâhya'nın asıl değeri geçen sefer ne
 * konuşulduğunu ve neye karar verildiğini HATIRLAMASI. Sohbet kapanınca
 * kaybolan bir ajan, her seferinde sıfırdan tanışılan bir ajandır.
 */
class KahyaMesaji extends Model
{
    public const ROL_SAHIP = 'sahip';

    public const ROL_KAHYA = 'kahya';

    protected $table = 'kahya_mesajlari';

    protected $fillable = ['rol', 'metin', 'kahya_eylemi_id', 'user_id', 'ek_yolu', 'ek_ad', 'ek_tipi'];

    public function eylem(): BelongsTo
    {
        return $this->belongsTo(KahyaEylemKaydi::class, 'kahya_eylemi_id');
    }

    public function ekVarMi(): bool
    {
        return $this->ek_yolu !== null;
    }

    public function ekResimMi(): bool
    {
        return str_starts_with((string) $this->ek_tipi, 'image/');
    }

    public function ekUrl(): ?string
    {
        return $this->ek_yolu ? Storage::disk('public')->url($this->ek_yolu) : null;
    }
}
