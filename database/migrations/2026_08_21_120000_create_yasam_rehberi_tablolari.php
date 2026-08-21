<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Yaşam Rehberi (2026-08-21) — Ülke Rehberi'nin K2/K7 iskeletini yeniden
 * kullanır (bkz. docs/plans/2026-08-21-yasam-rehberi-tasarimi.md), ama üç
 * tablo yerine iki: "temsilcilik" gibi ayrı bir fiziksel varlık yok, doğrudan
 * `countries.code` kullanılıyor.
 *
 * `yasam_kategorileri` ve `yasam_konulari` — ÜLKEDEN BAĞIMSIZ şablonlar
 * (Ülke Rehberi'ndeki `islem_turleri` deseni): yeni ülke eklerken kategori/
 * konu seti yeniden kurulmaz, yalnız `yasam_konu_icerikleri`ne içerik girilir.
 *
 * `icerik` JSON alanı BİLEREK markdown DEĞİL — bu depoda gövde içerik hiçbir
 * yerde markdown olarak saklanmıyor (KisitliMarkdown yalnız Kâhya sohbet
 * balonunda var); `evraklar` alanındaki gibi yapılandırılmış blok listesi
 * (`[{tip, metin}]`) hem güvenli render eder hem AI ajanlarının üretmesi
 * serbest metinden daha güvenilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yasam_kategorileri', function (Blueprint $table) {
            $table->id();
            $table->string('ad', 120);
            $table->string('slug', 80)->unique();
            $table->string('ikon', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('yasam_konulari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('yasam_kategorileri')->cascadeOnDelete();
            $table->string('baslik', 160);
            $table->string('slug', 100);
            $table->string('kisa_aciklama', 300)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['kategori_id', 'slug']);
        });

        Schema::create('yasam_konu_icerikleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yasam_konusu_id')->constrained('yasam_konulari')->cascadeOnDelete();
            // countries.code'a formal FK yok — depo genelinde CHAR(2) kodlar
            // indexed ama kısıtsız (Ülke Rehberi'ndeki temsilcilikler.country_code
            // deseninin aynısı, bkz. 2026_08_01_120000_create_rehber_tablolari.php).
            $table->char('country_code', 2)->index();
            $table->json('icerik')->nullable();
            $table->string('kaynak_url', 300)->nullable();
            $table->string('kaynak_aciklama', 300)->nullable();
            $table->date('dogrulanma_tarihi')->nullable();
            $table->string('status', 20)->default('taslak');
            $table->string('yazan_tur', 20)->default('ai');
            $table->timestamps();

            $table->unique(['yasam_konusu_id', 'country_code']);
            $table->index(['status', 'dogrulanma_tarihi']);
        });

        Schema::create('yasam_konu_onerileri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yasam_konu_icerigi_id')->constrained('yasam_konu_icerikleri')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('onerilen_metin', 2000);
            $table->string('kaynak_url', 300)->nullable();
            $table->string('durum', 20)->default('bekliyor');
            $table->timestamps();

            $table->index(['durum', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yasam_konu_onerileri');
        Schema::dropIfExists('yasam_konu_icerikleri');
        Schema::dropIfExists('yasam_konulari');
        Schema::dropIfExists('yasam_kategorileri');
    }
};
