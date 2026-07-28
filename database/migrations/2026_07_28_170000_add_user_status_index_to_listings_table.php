<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * listings(user_id, status) bileşik indeksi (2026-07-28).
 *
 * Panel her açılışta "benim ilanlarım"ın durum dağılımını tek agregat
 * sorgusuyla çıkarıyor (aktif/beklemede/pasif + toplam görüntülenme +
 * öne çıkarması bitmek üzere). Bugün listings tablosunda user_id ve status
 * için ayrı ayrı indeks var ama BİLEŞİK indeks yok; bu sorgu kullanıcının
 * satırlarını bulduktan sonra durumları tek tek okuyor.
 *
 * Bu, panelin eklediği tek tarama maliyetini indeksli hale getirir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'listings_user_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex('listings_user_status_index');
        });
    }
};
