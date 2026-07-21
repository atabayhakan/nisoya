<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kullanılmayan (ölü) 'listing_alerts' tablosunu kaldırır. Modül hiçbir
     * zaman uçtan uca çalışmadı: matchesListing() hiçbir yerden çağrılmıyordu,
     * tabloyu dolduran/tetikleyen observer/job/UI yoktu. Tablo boş ve
     * referanssız (bkz. 2026-07-22 kod denetimi). Model, test ve create
     * migration'ı ile birlikte kaldırıldı.
     */
    public function up(): void
    {
        Schema::dropIfExists('listing_alerts');
    }

    public function down(): void
    {
        // Geri alma yok: ölü modül kaldırıldı, tablo yeniden oluşturulmaz.
    }
};
