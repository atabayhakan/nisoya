<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Yüklenen dosyanın kendisi — ANA KOPYA.
 *
 * Bkz. docs/plans/2026-08-09-medya-boru-hatti-design.md
 *
 * KURAL: sitede görünen hiçbir şey ana kopya DEĞİLDİR. Her yüzey bir türevi
 * ({@see MediaRendition}) gösterir; ana kopya yalnız üretim kaynağıdır ve
 * public diske yazılmaz.
 *
 * Bunun karşılığı somut: slot oranı değişirse, yeni bir cihaz boyutu
 * gerekirse ya da odak noktası kaydırılırsa YENİDEN YÜKLEME GEREKMEZ —
 * `media:yeniden-turet` ana kopyadan yeniden üretir. Ana kopya atılsaydı
 * küçültülmüş dosyadan geri dönülemezdi.
 */
class MediaAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'yol', 'ad', 'mime', 'en', 'boy', 'bayt', 'ozet', 'odak_x', 'odak_y', 'yukleyen_id',
    ];

    /*
     * ODAK VARSAYILANI MODELDE DE TANIMLI — yalnız veritabanında değil.
     *
     * DB varsayılanı ancak satır okunduğunda görünür; `create()` dönen model
     * örneği alanı NULL taşır ve hemen ardından türetme yapılırsa "int bekleniyor,
     * null geldi" ile patlar (ilk koşuda tam bu oldu). Varsayılanı modele de
     * yazmak, kaydı yeniden okumaya gerek bırakmaz.
     */
    protected $attributes = [
        'odak_x' => 50,
        'odak_y' => 50,
    ];

    protected function casts(): array
    {
        return [
            'en' => 'integer',
            'boy' => 'integer',
            'bayt' => 'integer',
            'odak_x' => 'integer',
            'odak_y' => 'integer',
        ];
    }

    /** @return HasMany<MediaRendition, $this> */
    public function renditions(): HasMany
    {
        return $this->hasMany(MediaRendition::class);
    }

    /** @return BelongsTo<User, $this> */
    public function yukleyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'yukleyen_id');
    }

    /** Bir slot için üretilmiş türev; yoksa null. */
    public function rendition(string $slot): ?MediaRendition
    {
        return $this->renditions->firstWhere('slot', $slot)
            ?? $this->renditions()->where('slot', $slot)->first();
    }

    /**
     * Slotun istediğinden küçük mü? (retinada yumuşak görünme uyarısı için)
     *
     * Büyütme YAPILMAZ — `scaleDown` deseni: küçük bir görseli şişirmek
     * bulanıklığı gizlemez, dosyayı büyütür.
     */
    public function slotIcinKucukMu(string $slot): bool
    {
        $spec = config("media_slots.{$slot}");

        if (! is_array($spec) || $this->en === null) {
            return false;
        }

        return $this->en < (int) ($spec['en'] ?? 0);
    }
}
