<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bekleyen hamleler — dış-eylem halkasının onay kuyruğu (F2, tasarım §2.2).
 *
 * Tasarım frekansının TEK onay kapısı burasıdır: içeride Kâhya serbesttir
 * (yedek + geri-al güvencesi), ama sistemden DIŞARI çıkan her sonuç —
 * e-posta, tanıtım mesajı, kamuya açık içerik — önce buraya kart olarak
 * düşer. Sahip Onayla/Reddet der; F2'de onay yalnız KAYDEDİLİR (gerçek
 * gönderim altyapısı F4'te gelir), ama akışın kası şimdiden çalışır ve
 * Kâhya kararları sonraki turlarında görür.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bekleyen_hamleler', function (Blueprint $table) {
            $table->id();
            // Hamle bir görevin adımı olabilir ama olmak zorunda değil;
            // görev silinirse kart öksüz kalmasın, bağı çözülsün.
            $table->foreignId('kahya_gorevi_id')->nullable()
                ->constrained('kahya_gorevleri')->nullOnDelete();
            $table->string('baslik', 150);
            // Kâhya'nın "neden bu hamle" cümlesi — sahip karar verirken okur.
            $table->string('gerekce', 500);
            // Hamlenin kendisi: taslak mesaj metni, önerilen içerik vb.
            $table->text('icerik');
            // oneri (genel) · eposta · sosyal — F4'te gönderim türü seçer.
            $table->string('tur', 30)->default('oneri');
            $table->string('durum', 20)->default('beklemede')->index();
            $table->string('karar_notu', 500)->nullable();
            $table->timestamp('karar_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bekleyen_hamleler');
    }
};
