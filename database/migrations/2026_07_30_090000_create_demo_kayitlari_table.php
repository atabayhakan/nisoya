<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demo (örnek) verinin DEFTERİ ve İŞARETİ.
 *
 * ---------------------------------------------------------------------------
 * NEDEN DEFTER — "where is_demo = true" NEDEN YETMEZ
 *
 * Geri alınamayan bir demo makinesi zarardır: sahte veri üretmek kolay,
 * SONRADAN EKSİKSİZ SİLMEK zordur. Bir tek yetim satır ya da diskte kalan bir
 * dosya, "temizledim" cümlesini yalan yapar.
 *
 * Bayrakla silme ("her tabloda is_demo = true olanları sil") iki yerde çöker:
 *   1. Bayrak koyulmamış bir tablo unutulur ve orada sonsuza kadar demo veri
 *      kalır — üstelik unutulduğu FARK EDİLMEZ.
 *   2. Silme sırası bilinmez. Bu şemada `listings` silinince
 *      `conversations.listing_id` NULL'lanır ama SOHBET KALIR
 *      (2026_06_30_100009:13); aynısı `reviews.listing_id` ve
 *      `deals.listing_id` için de geçerli. Yani yanlış sırada silmek tam
 *      olarak yetim üretir.
 *
 * Defter bu iki sorunu da çözer: ne ürettiysek satır satır yazılır, geri
 * alırken TERS SIRADA ve Eloquent üzerinden silinir.
 *
 * ---------------------------------------------------------------------------
 * `dosyalar` KOLONU NEDEN VAR — DB cascade dosyayı silmez
 *
 * `listing_images.listing_id` cascadeOnDelete'tir; yani bir ilan silinince
 * görsel SATIRLARI veritabanı seviyesinde gider ve Eloquent BAYPAS EDİLİR.
 * `ListingImageObserver::deleting` çalışmaz, dosyalar diskte kalır. Bu depo
 * bunu her çağrı yerinde elle telafi ediyor (ListingController::destroy,
 * ProfileSettingsController) ama Listing için bir gözlemci YOK.
 *
 * Avatarlarda durum daha kötü: `UserObserver`'ın deleting kancası hiç yok ve
 * avatarla hiç ilgilenmiyor. `$user->delete()` avatar dosyalarını diskte
 * bırakır.
 *
 * Bu yüzden defter DOSYA YOLLARINI DA taşır ve temizleyici onlara ayrıca
 * bakar — gözlemcilere güvenmez.
 *
 * ---------------------------------------------------------------------------
 * `is_demo` NEDEN AYRICA GEREKLİ
 *
 * Defter geri almak içindir; bayrak ise OKUMA tarafı için:
 *   · ilan kartında "ÖRNEK" rozetini basmak (her kart için deftere sorulamaz),
 *   · Kâhya teşhisinin demo kayıtları HER ZAMAN dışlaması.
 *
 * İkincisi kritik: `KahyaTeshisi::gercekEnvanter()` sahibin kendi kendini
 * kandırmasını engellemek için var ("12 ilan iyi görünür, 12 ilan / 1 satıcı
 * gerçeği söyler"). Demo ilanlar o sayıya karışsaydı Faz A/B, Faz C/D/E'nin
 * bütün değerini yok ederdi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_kayitlari', function (Blueprint $table) {
            $table->id();

            // Parti = tek bir üretim çalışması. Geri alma birimi budur.
            $table->string('parti', 40);

            $table->string('model_turu');
            $table->unsignedBigInteger('model_id');

            /*
             * Bu kayıt için diske YAZILMIŞ dosya yolları.
             *
             * Gözlemcilere güvenilmiyor: DB cascade Eloquent'i baypas eder ve
             * avatar için zaten hiç gözlemci yok.
             */
            $table->json('dosyalar')->nullable();

            $table->timestamps();

            $table->index('parti');
            $table->index(['model_turu', 'model_id']);

            /*
             * Aynı kaydın iki kez deftere girmesi, silme sırasında ikinci
             * denemede "yok" hatası demektir. Veritabanı engellesin.
             */
            $table->unique(['parti', 'model_turu', 'model_id'], 'demo_kayitlari_benzersiz');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->index()->after('status');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->index()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });

        Schema::dropIfExists('demo_kayitlari');
    }
};
