<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            // Coğrafi filtreleme + harita için (reverse geocoding, ülke/şehir sorguları)
            $table->index(['gps_lat', 'gps_lng'], 'idx_listing_images_gps');
            $table->index('reverse_country_code', 'idx_listing_images_country');
            $table->index('reverse_city', 'idx_listing_images_city');
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropIndex('idx_listing_images_gps');
            $table->dropIndex('idx_listing_images_country');
            $table->dropIndex('idx_listing_images_city');
        });
    }
};
