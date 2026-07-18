<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GPS/coğrafi filtreleme indeksleri.
 *
 * Bu migration eskiden 2026_07_02_100600 timestamp'iyle, kolonları ekleyen
 * 2026_07_02_100600_add_reverse_geocode_columns_to_listing_images ile AYNI
 * timestamp'e sahipti. Aynı timestamp'te Laravel dosyaları ALFABETİK sıralar;
 * "add_gps_indexes" < "add_reverse_geocode_columns" olduğu için indeksler,
 * dayandıkları reverse_* kolonları HENÜZ oluşmadan çalışıyordu → MySQL'de
 * ERROR 1072 (var olmayan kolona index). SQLite ->index'i esnek işlediği için
 * yerelde/testte hiç yakalanmıyordu; yalnızca taze MySQL kurulumu (DR/staging)
 * patlıyordu.
 *
 * Düzeltme: timestamp 100700'e alındı → artık kolon migration'ından (100600)
 * SONRA çalışır. Ayrıca idempotent (index varsa atla): mevcut prod bu dosyayı
 * "yeni ad" olarak tekrar çalıştırsa bile guard sayesinde no-op olur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            if (! Schema::hasIndex('listing_images', 'idx_listing_images_gps')) {
                $table->index(['gps_lat', 'gps_lng'], 'idx_listing_images_gps');
            }
            if (! Schema::hasIndex('listing_images', 'idx_listing_images_country')) {
                $table->index('reverse_country_code', 'idx_listing_images_country');
            }
            if (! Schema::hasIndex('listing_images', 'idx_listing_images_city')) {
                $table->index('reverse_city', 'idx_listing_images_city');
            }
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            if (Schema::hasIndex('listing_images', 'idx_listing_images_gps')) {
                $table->dropIndex('idx_listing_images_gps');
            }
            if (Schema::hasIndex('listing_images', 'idx_listing_images_country')) {
                $table->dropIndex('idx_listing_images_country');
            }
            if (Schema::hasIndex('listing_images', 'idx_listing_images_city')) {
                $table->dropIndex('idx_listing_images_city');
            }
        });
    }
};
