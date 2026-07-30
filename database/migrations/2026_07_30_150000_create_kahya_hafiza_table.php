<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kâhya'nın kalıcı hafızası (F1 — Kâhya 2.0 tasarımı §2.3).
 *
 * Sohbet geçmişi (kahya_mesajlari) 12 mesajlık dönen penceredir ve unutur;
 * bu tablo unutmaz. Sahibin "şunu hatırla" dedikleri + (F5'te) Kâhya'nın
 * kendi çıkarımları burada yaşar ve her sohbetin yönergesine girer.
 *
 * `kaynak` ayrımı denetim içindir: sahibin yazdırdığı kayıt ile modelin
 * kendi çıkarımı panelde aynı görünmemeli — sahip saçma bir çıkarımı tek
 * bakışta ayırt edip silebilmeli.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kahya_hafiza', function (Blueprint $table) {
            $table->id();
            $table->string('tur', 20)->index();
            $table->string('metin', 500);
            $table->string('kaynak', 30)->default('sahip');
            // Pasif kayıt silinmemiş ama yönergeye girmeyen kayıttır —
            // "unut" geri alınabilir olsun diye silme yerine bu bayrak.
            $table->boolean('aktif')->default(true)->index();
            // tablo-sorgula ile kaç kez arandığı — hangi bilginin gerçekten
            // işe yaradığını gösterir (F5 ders-cikar'ın hammaddesi).
            $table->unsignedInteger('kullanim_sayisi')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kahya_hafiza');
    }
};
