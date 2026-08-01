<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Bu bilgi güncel mi?" geri bildirimi — rehberin bağışıklık sistemi.
 *
 * Konsolosluk bilgisi merkezi bir bildirimle değişmez; en hızlı sinyal
 * işlemi geçen hafta bizzat yapan kullanıcıdır. Kayıt anonimdir (ham IP
 * tutulmaz, yalnız kötüye-kullanım izi için hash), yanıt verilmez —
 * bu bir destek kanalı değil, veri tazeliği sinyalidir.
 */
class RehberGeriBildirimi extends Model
{
    public const TURLER = [
        'guncel_degil' => 'Bilgi güncel değil',
        'hata' => 'Hata var',
        'oneri' => 'Önerim var',
    ];

    protected $table = 'rehber_geri_bildirimleri';

    protected $fillable = ['temsilcilik_islemi_id', 'tur', 'metin', 'ip_hash', 'incelendi'];

    protected function casts(): array
    {
        return ['incelendi' => 'boolean'];
    }

    /** @return BelongsTo<TemsilcilikIslemi, $this> */
    public function islem(): BelongsTo
    {
        return $this->belongsTo(TemsilcilikIslemi::class, 'temsilcilik_islemi_id');
    }

    public function turEtiketi(): string
    {
        return self::TURLER[$this->tur] ?? $this->tur;
    }
}
