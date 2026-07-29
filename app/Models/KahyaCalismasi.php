<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Kâhya'nın her koşusunun kaydı — bkz. migration docblock'undaki
 * "sessiz ölüm koruması" gerekçesi.
 *
 * @property string $tur
 * @property bool $gonderildi
 * @property ?string $alici
 * @property ?array<string, mixed> $ozet
 * @property ?string $hata
 * @property ?int $sure_ms
 */
class KahyaCalismasi extends Model
{
    protected $table = 'kahya_calismalari';

    protected $fillable = ['tur', 'gonderildi', 'alici', 'ozet', 'hata', 'sure_ms'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'gonderildi' => 'boolean',
            'ozet' => 'array',
        ];
    }

    /**
     * Son koşunun üzerinden geçen saat — `null` ise HİÇ koşmamış.
     *
     * `null` ile `0` arasındaki fark önemlidir: 0 "az önce koştu", null
     * "hiç koşmadı" demektir ve ikincisi bir arızadır.
     */
    public static function sonKosuYasiSaat(string $tur = 'gunluk_rapor'): ?int
    {
        $son = static::query()->where('tur', $tur)->latest('created_at')->first();

        return $son?->created_at?->diffInHours(now()) !== null
            ? (int) $son->created_at->diffInHours(now())
            : null;
    }

    /** @param  Builder<self>  $query */
    public function scopeGunlukRapor(Builder $query): void
    {
        $query->where('tur', 'gunluk_rapor');
    }
}
