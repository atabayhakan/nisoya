<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İlan görsellerinin İŞLENME DURUMU.
 *
 * ---------------------------------------------------------------------------
 * NEDEN GEREKLİ
 *
 * `ListingImage` kaydı YALNIZCA `ProcessListingImage` kuyruk işinde oluşuyor.
 * Yükleme anında ortada hiçbir kayıt yok; ilan, iş işlenene kadar görselsiz
 * görünüyor. Sahip 2026-08-12'de tam bunu yaşadı: fotoğrafı ekledi, ilanda
 * göremedi, "otomatik onay vermesi gerekiyordu" diye bildirdi. Görsel aslında
 * işlenmişti — sadece kimse ona "işleniyor" demedi.
 *
 * Asıl kusur burada: BOŞ KUTU ile ARIZA aynı görünüyor. Kullanıcı bekleyeceğini
 * mi bilmeli, tekrar mı denemeli, yoksa vazgeçmeli mi — hiçbir işaret yok.
 *
 * Üç alan, her biri tek soruya cevap veriyor:
 *   pending_images   — kaç görsel sırada? (0 ise gösterilecek bir şey yok)
 *   images_queued_at — ne zaman sıraya girdi? (bayat kalanı ayırt etmek için)
 *   images_failed    — son denemede iş düştü mü? (tekrar dene demek için)
 *
 * `images_queued_at` OLMADAN sayaç tek yönlü bir tuzak olurdu: worker sert
 * öldürülürse sayaç sıfırlanmaz ve arayüz sonsuza dek "işleniyor" der. Yaşla
 * birlikte okunduğunda bayat kayıt "yüklenemedi"ye düşer, temizlik işi
 * gerekmez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // tinyInteger: ilan başına en fazla 8 görsel (ListingRequest max:8).
            $table->unsignedTinyInteger('pending_images')->default(0)->after('is_demo');
            $table->timestamp('images_queued_at')->nullable()->after('pending_images');
            $table->boolean('images_failed')->default(false)->after('images_queued_at');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['pending_images', 'images_queued_at', 'images_failed']);
        });
    }
};
