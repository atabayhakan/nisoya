<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Emlak ilanının 1:1 detay kaydı. İlanın kendisi (başlık, fiyat, konum,
 * görseller, favori/mesaj/değerlendirme) Listing'te yaşar; buradakiler
 * yalnızca emlak dikeyine özgü, filtrelenebilir alanlardır.
 */
class ListingPropertyDetail extends Model
{
    /** Oda sayısı seçenekleri (Türkçe emlak geleneği: oda+salon). */
    public const ROOM_OPTIONS = ['1+0', '1+1', '2+1', '3+1', '4+1', '5+'];

    /**
     * "Yeni gelenlere uygun" rozetleri — diasporaya özgü kiralama engellerini
     * kaldıran özellikler. Anahtar DB'de (badges json), etiket arayüzde.
     */
    public const BADGES = [
        'adres_kaydi' => 'Adres kaydı (Anmeldung) yapılabilir',
        'kefilsiz' => 'Kefil istenmez',
        'kredi_gecmisi_yok' => 'Kredi geçmişi (SCHUFA vb.) istenmez',
        'faturalar_dahil' => 'Faturalar dahil',
        'evcil_hayvan' => 'Evcil hayvan kabul',
        'ogrenci_dostu' => 'Öğrenci dostu',
    ];

    protected $fillable = [
        'listing_id',
        'rooms',
        'area_m2',
        'floor',
        'furnished',
        'deposit',
        'available_from',
        'max_guests',
        'min_stay_nights',
        'badges',
    ];

    protected function casts(): array
    {
        return [
            'area_m2' => 'integer',
            'floor' => 'integer',
            'furnished' => 'boolean',
            'deposit' => 'decimal:2',
            'available_from' => 'date',
            'max_guests' => 'integer',
            'min_stay_nights' => 'integer',
            'badges' => 'array',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /** Geçerli rozet anahtarlarıyla kesişim (bilinmeyen anahtarlar elenir). */
    public function badgeLabels(): array
    {
        return array_values(array_intersect_key(self::BADGES, array_flip($this->badges ?? [])));
    }
}
