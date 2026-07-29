<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected $fillable = ['rol', 'metin', 'kahya_eylemi_id', 'user_id'];

    public function eylem(): BelongsTo
    {
        return $this->belongsTo(KahyaEylemKaydi::class, 'kahya_eylemi_id');
    }
}
