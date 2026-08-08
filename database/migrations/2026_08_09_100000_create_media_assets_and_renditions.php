<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Medya boru hattı — ana kopya + türev.
 *
 * Bkz. docs/plans/2026-08-09-medya-boru-hatti-design.md
 *
 * Bu göç MEVCUT HİÇBİR ŞEYE DOKUNMAZ: eski tablolar, diskteki dosyalar ve
 * `listing_images` boru hattı olduğu gibi kalır. Yeni sistem yanına kurulur,
 * yüzeyler tek tek geçirilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();

            // Yüklenen dosyanın kendisi. public diske YAZILMAZ — servis edilmez,
            // yalnız türetme kaynağıdır (tasarım kuralı: hiçbir yüzey ana
            // kopyayı göstermez).
            $table->string('yol');
            $table->string('ad')->nullable();          // kullanıcının gördüğü ad
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('en')->nullable();
            $table->unsignedInteger('boy')->nullable();
            $table->unsignedBigInteger('bayt')->nullable();

            /*
             * İÇERİK ÖZETİ — aynı dosyanın iki kez ana kopya olmasını engeller.
             * Bugün kütüphanede aynı görselin kopyaları birikiyor; benzersiz
             * indeks bunu yapısal olarak bitirir.
             */
            $table->string('ozet', 64)->unique();

            /*
             * ODAK NOKTASI (yüzde). Tüm türevler bu noktayı merkeze almaya
             * çalışarak kırpılır — bir kez ayarlanır, masaüstü de mobil de uyar.
             * Varsayılan merkez: öngörülebilirlik bilinçli tercih (yanlış bir
             * "akıllı" kırpma, tahmin edilebilir merkezden daha sinir bozucudur).
             */
            $table->unsignedTinyInteger('odak_x')->default(50);
            $table->unsignedTinyInteger('odak_y')->default(50);

            $table->foreignId('yukleyen_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('media_renditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();

            // config/media_slots.php anahtarı — hangi amaç için üretildiği.
            $table->string('slot', 60);

            $table->string('yol');
            $table->unsignedInteger('en')->nullable();
            $table->unsignedInteger('boy')->nullable();
            $table->unsignedBigInteger('bayt')->nullable();
            $table->string('bicim', 10)->default('webp');

            /*
             * Kalite kaydediliyor çünkü `azami_kb`'yi tutturmak için kademeli
             * düşürülebiliyor (80 → 70 → 60). Sonradan "bu görsel neden yumuşak"
             * sorusunun cevabı burada durur.
             */
            $table->unsignedTinyInteger('kalite')->nullable();

            $table->timestamps();

            // Bir ana kopyanın bir slot için TEK türevi olur; yeniden türetme
            // eskisinin üzerine yazar.
            $table->unique(['media_asset_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_renditions');
        Schema::dropIfExists('media_assets');
    }
};
