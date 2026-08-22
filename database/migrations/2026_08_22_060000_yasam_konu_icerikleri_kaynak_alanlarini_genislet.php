<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ÖLÇÜLEN HATA (2026-08-22, canlı deploy): F1 seeder'ı canlıda 0 içerik
 * üretti — 5 konu oluştu ama 25 içeriğin HİÇBİRİ. Sebep tam olarak Ülke
 * Rehberi'nde daha önce yaşanan hata: `kaynak_aciklama` string(300)'dü,
 * ajanların ürettiği kaynak açıklamaları (birden çok kaynağın çapraz
 * doğrulama notlarıyla) 3000+ karaktere çıkıyordu; bir hücrede de
 * `kaynak_url` alanına tek URL yerine URL+uzun açıklama karışık yazılmış,
 * 1484 karaktere ulaşmıştı. **SQLite (yerel) VARCHAR uzunluğunu
 * DAYATMAZ** — yerel seed ve 39 test yeşil kaldı, hata yalnız üretim
 * MySQL'inde (strict mode) INSERT reddedilince ortaya çıktı.
 *
 * Çözüm: her iki alanı da TEXT'e genişlet. Rakam tahmin edilmiyor —
 * kaynak açıklaması doğası gereği uzun olabilir (birden fazla kaynağın
 * çapraz doğrulama notu), keyfi bir üst sınır koymanın anlamı yok.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yasam_konu_icerikleri', function (Blueprint $table) {
            $table->text('kaynak_url')->nullable()->change();
            $table->text('kaynak_aciklama')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('yasam_konu_icerikleri', function (Blueprint $table) {
            $table->string('kaynak_url', 300)->nullable()->change();
            $table->string('kaynak_aciklama', 300)->nullable()->change();
        });
    }
};
