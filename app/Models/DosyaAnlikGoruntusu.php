<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tanıtım belgesinin bir anlık görüntüsü — "o gün ne demiştik".
 *
 * `updated_at` YOK ve bu bilinçli: kayıt üretildikten sonra DEĞİŞMEZ. Geçmiş
 * rakamı düzeltmek, geçmişi kaybetmektir; fark varsa yeni bir satır düşer.
 *
 * @property array<string, mixed> $veri
 */
class DosyaAnlikGoruntusu extends Model
{
    public const TUR_GENEL_BAKIS = 'genel-bakis';

    public const TUR_YATIRIMCI = 'yatirimci-memosu';

    protected $table = 'dosya_anlik_goruntuleri';

    public const UPDATED_AT = null;

    protected $fillable = ['tur', 'veri'];

    protected function casts(): array
    {
        return ['veri' => 'array'];
    }
}
