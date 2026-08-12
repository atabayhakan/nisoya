<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İlanın yerel dildeki karşılığı (Almanca, Felemenkçe, Fransızca…).
 *
 * NEDEN AYRI TABLO, JSON KOLON DEĞİL: Bir ilanın zamanla birden fazla dili
 * olabilir (Belçika'da hem nl hem fr), her dilin kendi tazelik durumu var ve
 * "hangi dilde kaç çeviri var" sorusu SQL'le sorulabilmeli.
 *
 * `source_hash` BU TABLONUN EN ÖNEMLİ SÜTUNU. Satıcı ilanı düzenlediğinde
 * çeviri sessizce YANLIŞ hâle gelir — eski fiyatı, kaldırılmış bir detayı
 * anlatmaya devam eder. Kaynak metnin özeti tutulup her gösterimde
 * karşılaştırılıyor; tutmuyorsa çeviri BASILMAZ. Bayat çeviri, çeviri
 * olmamasından kötüdür.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->text('description');
            // Kaynak metnin özeti — tazelik kapısı.
            $table->string('source_hash', 32);
            $table->timestamps();

            // Bir ilan + bir dil = tek satır.
            $table->unique(['listing_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_translations');
    }
};
