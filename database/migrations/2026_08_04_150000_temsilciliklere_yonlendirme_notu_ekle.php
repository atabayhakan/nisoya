<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Temsilciliğe "yönlendirme notu" alanı.
 *
 * NEDEN: temsilcilik sayfasının boş hâli "Bu temsilcilik için işlem rehberleri
 * hazırlanıyor" diyordu. Bu iki farklı durumu aynı cümleyle anlatıyor:
 *
 *   (a) Biz henüz yazmadık — geçici, doğru cümle.
 *   (b) Temsilcilik işlem bilgisini kendi sitesinde HİÇ yayınlamıyor —
 *       kalıcı bir gerçek ve "hazırlanıyor" demek YALAN olur.
 *
 * (b) Bişkek Büyükelçiliği'nde ölçüldü: /Mission/InfoNotes indeksi tamamen
 * boş, 262 duyuruda tek bilgi notu yok. Ziyaretçiye "hazırlanıyor" demek onu
 * beklemeye iter; oysa yapması gereken şey merkezî konsolosluk.gov.tr'ye
 * gitmek. Bu alan doluysa sayfa bekleme değil YÖNLENDİRME gösterir.
 *
 * Keşif fazının uyarısı: küçük büyükelçiliklerde boş indeks muhtemelen
 * kural, istisna değil — bu alan Bişkek'e özel bir yama değil, tekrar
 * kullanılacak bir kavram.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temsilcilikler', function (Blueprint $table) {
            $table->text('yonlendirme_notu')->nullable()->after('resmi_url');
        });
    }

    public function down(): void
    {
        Schema::table('temsilcilikler', function (Blueprint $table) {
            $table->dropColumn('yonlendirme_notu');
        });
    }
};
