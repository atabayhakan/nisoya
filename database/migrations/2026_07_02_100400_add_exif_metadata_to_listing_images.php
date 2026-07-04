<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            // Yükleme anında orijinal EXIF metadata'sı (GPS, kamera, vb.)
            // gizlilik için temizlenir ama audit amaçlı DB'de saklanır.
            // Sadece admin paneli tarafından görülebilir; kullanıcıya gösterilmez.
            $table->json('exif_metadata')->nullable()->after('size_bytes');

            // GPS vardı mı? (moderasyon kararları için hızlı filtre)
            $table->boolean('had_gps')->default(false)->after('exif_metadata');
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropColumn(['exif_metadata', 'had_gps']);
        });
    }
};
