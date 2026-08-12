<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İlanın yerel dildeki karşılığı.
 *
 * @property-read Listing|null $listing
 */
class ListingTranslation extends Model
{
    protected $fillable = [
        'listing_id',
        'locale',
        'title',
        'description',
        'source_hash',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Çeviri hâlâ kaynak metni mi anlatıyor?
     *
     * Satıcı başlığı ya da açıklamayı değiştirdiyse çeviri artık başka bir
     * ilanı anlatıyordur. Bu kontrol GÖSTERİM ANINDA yapılıyor; kaydı silmek
     * yerine gizlemeyi seçtik ki satıcı metni geri aldığında çeviri
     * kendiliğinden geçerli olsun.
     */
    public function guncelMi(Listing $listing): bool
    {
        return $this->source_hash === self::kaynakOzeti($listing);
    }

    /** Başlık + açıklamanın özeti — tazelik karşılaştırmasının tek kaynağı. */
    public static function kaynakOzeti(Listing $listing): string
    {
        return md5(trim((string) $listing->title)."\n".trim((string) $listing->description));
    }
}
