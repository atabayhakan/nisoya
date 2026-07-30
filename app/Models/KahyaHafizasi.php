<?php

namespace App\Models;

use App\Enums\HafizaTuru;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Kâhya'nın kalıcı hafızasının bir kaydı — gerekçe migration'da.
 *
 * @property HafizaTuru $tur
 * @property string $metin
 * @property string $kaynak
 * @property bool $aktif
 * @property int $kullanim_sayisi
 */
class KahyaHafizasi extends Model
{
    /** Sahibin sohbetten yazdırdığı kayıt. */
    public const KAYNAK_SAHIP = 'sahip';

    /** Kâhya'nın haftalık ders-cikar koşusunun çıkarımı (F5). */
    public const KAYNAK_CIKARIM = 'kahya-cikarimi';

    protected $table = 'kahya_hafiza';

    protected $fillable = ['tur', 'metin', 'kaynak', 'aktif', 'kullanim_sayisi'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tur' => HafizaTuru::class,
            'aktif' => 'boolean',
        ];
    }

    /** @param  Builder<self>  $sorgu */
    public function scopeAktif(Builder $sorgu): void
    {
        $sorgu->where('aktif', true);
    }

    /**
     * Yönergeye girecek kayıtlar: çekirdek türler (kural/gerçek) tamamı +
     * kalan yer kadar en yeni ders/not. Tavan, yönergeyi şişirmemek için —
     * taşanı model tablo-sorgula ile arar.
     *
     * @return Collection<int, self>
     */
    public static function yonergeIcin(int $tavan = 50): Collection
    {
        $cekirdek = self::query()->aktif()
            ->whereIn('tur', [HafizaTuru::Kural, HafizaTuru::Gercek])
            ->latest('id')
            ->limit($tavan)
            ->get();

        $kalanYer = $tavan - $cekirdek->count();

        if ($kalanYer <= 0) {
            return $cekirdek;
        }

        return $cekirdek->concat(
            self::query()->aktif()
                ->whereIn('tur', [HafizaTuru::Ders, HafizaTuru::Not])
                ->latest('id')
                ->limit($kalanYer)
                ->get(),
        );
    }
}
