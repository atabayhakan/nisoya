<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kâhya'nın LLM/API harcama defteri (F0 — tasarım kararı: "sayaç ilk günden").
 *
 * Ajan döngüsü mesaj başına maliyeti 3-10x artırabilir; bu defter artışı
 * İLK GÜNDEN görünür kılar. Para birimi tutmuyoruz — model fiyatları oynak,
 * token sayısı ise sağlayıcının faturasıyla her zaman karşılaştırılabilir
 * ham gerçek. Panel toplamları token üzerinden gösterir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kahya_harcamalar', function (Blueprint $table) {
            $table->id();
            // Hangi özellik harcadı: sohbet, gunluk-rapor, ders-cikar (F5)...
            $table->string('kaynak', 40)->index();
            $table->string('saglayici', 40);
            $table->string('model', 120);
            $table->unsignedInteger('girdi_token')->default(0);
            $table->unsignedInteger('cikti_token')->default(0);
            // Önbellek okuma/yazma ayrı tutulur: girdi_token'ın kaçta kaçının
            // ucuz (cache) olduğunu bilmeden maliyet yorumu yanıltır.
            $table->unsignedInteger('onbellek_okuma_token')->default(0);
            $table->unsignedInteger('onbellek_yazma_token')->default(0);
            $table->timestamps();

            // Panel "bu ay ne harcadım" sorgusunun indeksi.
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kahya_harcamalar');
    }
};
