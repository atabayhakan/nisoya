<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_property_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('rooms', 10)->nullable();               // '1+0'..'5+', oda-arkadaşı ilanlarında boş olabilir
            $table->unsignedSmallInteger('area_m2')->nullable();   // brüt m²
            $table->smallInteger('floor')->nullable();             // bodrum için negatif olabilir
            $table->boolean('furnished')->default(false);
            $table->decimal('deposit', 10, 2)->nullable();         // para birimi ilanın currency alanını izler
            $table->date('available_from')->nullable();
            $table->unsignedTinyInteger('max_guests')->nullable(); // kısa dönem: konuk kapasitesi
            $table->unsignedSmallInteger('min_stay_nights')->nullable(); // kısa dönem: min. konaklama
            $table->json('badges')->nullable();                    // "yeni gelenlere uygun" rozet anahtarları
            $table->timestamps();

            // Filtre sorguları (fiyat/ülke listings'te; buradakiler detay filtreleri)
            $table->index('rooms');
            $table->index('area_m2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_property_details');
    }
};
