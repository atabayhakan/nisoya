<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanıtım belgesinin anlık görüntüleri.
 *
 * NEDEN: yatırımcıya verilen her belge o günün rakamlarını taşır. Üç ay sonra
 * "geçen çeyrekte ne demiştik" sorusunun cevabı olmazsa iki şey olur:
 *   · Rakamlar arasındaki fark açıklanamaz ve güven kaybı doğurur.
 *   · Kendi ilerlemeni ölçemezsin — büyüme, iki ölçüm arasındaki farktır.
 *
 * Belge her üretildiğinde buraya bir satır düşer. Tablo YALNIZ EKLENİR
 * (güncelleme/silme yok): geçmişi düzeltmek, geçmişi kaybetmekten kötüdür.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dosya_anlik_goruntuleri', function (Blueprint $table) {
            $table->id();

            // Hangi belge: 'genel-bakis' | 'yatirimci-memosu'
            $table->string('tur', 40)->index();

            // O anki tüm metrikler. Şema zamanla değişebilir; JSON bilerek
            // esnek — eski satırlar yeni alanlar eklenince BOZULMAZ.
            $table->json('veri');

            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dosya_anlik_goruntuleri');
    }
};
