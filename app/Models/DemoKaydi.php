<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Üretilmiş tek bir demo kaydının defter satırı — bkz. migration docblock'u.
 *
 * @property string $parti
 * @property string $model_turu
 * @property int $model_id
 * @property ?array<int, string> $dosyalar
 */
class DemoKaydi extends Model
{
    protected $table = 'demo_kayitlari';

    protected $fillable = ['parti', 'model_turu', 'model_id', 'dosyalar'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['dosyalar' => 'array'];
    }

    /** @param  Builder<self>  $query */
    public function scopeParti(Builder $query, string $parti): void
    {
        $query->where('parti', $parti);
    }
}
