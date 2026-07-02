<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            // Hassas EXIF alanı içeriyor mu? (GPS, seri no, vb.)
            // Hızlı filtreleme için boolean indeks.
            $table->boolean('has_sensitive_exif')->default(false)->after('had_gps');

            // GPS'ten çıkarılan decimal koordinatlar (filtreleme + harita için)
            $table->decimal('gps_lat', 10, 7)->nullable()->after('has_sensitive_exif');
            $table->decimal('gps_lng', 10, 7)->nullable()->after('gps_lat');
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropColumn(['has_sensitive_exif', 'gps_lat', 'gps_lng']);
        });
    }
};