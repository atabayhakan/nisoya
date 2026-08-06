<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * listings.unpublished_at — "Pasif"in İKİ ANLAMINI ayırır (2026-08-06).
 *
 * `ListingStatus::Pasif` bugüne kadar TEK bir yoldan doğuyordu:
 * `UserObserver::suspendActiveListings()`, yani hesabı askıya alınan üyenin
 * ilanlarını yönetim susturuyordu. Üyenin kendi ilanını yayından kaldırma
 * eylemi HİÇ YOKTU (bkz. aynı turda eklenen panel düğmeleri).
 *
 * Üyeye "yayından kaldır / geri yayınla" verirken tek bir durum değerine iki
 * ayrı anlam yüklemek SESSİZ BİR MODERASYON DELİĞİ açardı: yönetim panelden
 * bir ilanı Pasif'e çekince (ya da hesap askıya alınıp geri açılınca) sahibi
 * onu kendi düğmesiyle tekrar yayına alabilirdi. "Kim kaldırdı" bilgisi
 * hiçbir yerde tutulmadığı için kod bu iki hâli AYIRT EDEMİYORDU.
 *
 * Bu kolon o ayrımı taşır ve tek bir kuralı mümkün kılar:
 *   - Pasif + unpublished_at DOLU  → üye kendi kaldırdı, kendi geri açabilir.
 *   - Pasif + unpublished_at NULL  → yönetim/sistem kaldırdı, üye açamaz.
 *
 * Neden boolean değil timestamp: "ne zaman kaldırdı" ileride hem arşiv
 * ("Geçmiş" sekmesi) sıralaması hem de ölçüm için lazım olacak; boolean
 * aynı yeri kaplayıp daha az şey söylüyordu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->timestamp('unpublished_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('unpublished_at');
        });
    }
};
