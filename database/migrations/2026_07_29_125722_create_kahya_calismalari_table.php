<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kâhya çalışma defteri.
 *
 * NEDEN VAR — SESSİZ ÖLÜM KORUMASI: günlük rapor bir zamanlanmış komuttur ve
 * zamanlanmış komutlar sessizce ölür: cron kapanır, kuyruk durur, komut
 * istisna atar, e-posta spam'e düşer. Bunların HEPSİNDE sahibin gördüğü şey
 * aynıdır — gelen kutusunda rapor yok.
 *
 * "Rapor gelmedi" ile "rapor geldi, her şey yolundaydı" ayırt edilemezse
 * sistem işe yaramaz: sahip birkaç sessiz günün ardından "demek sorun yok"
 * diye düşünür, oysa komut haftalardır çalışmıyordur.
 *
 * Bu tablo her koşuyu kaydeder — GÖNDERİM BAŞARISIZ OLSA BİLE. Böylece
 * "son rapor N gün önce" sorusu yanıtlanabilir hâle gelir.
 *
 * `gonderildi` ile `hata` AYRI tutulur: rapor üretilip gönderilememiş
 * olabilir (SMTP kapalı). O durumda içerik yine saklanır ve panelden
 * okunabilir — e-posta tek dağıtım kanalı değildir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kahya_calismalari', function (Blueprint $table) {
            $table->id();
            $table->string('tur', 40)->default('gunluk_rapor');
            $table->boolean('gonderildi')->default(false);
            $table->string('alici')->nullable();

            // Raporun kendisi: e-posta gitmese de panelden okunabilsin.
            $table->json('ozet')->nullable();

            // Hata mesajı KISA tutulur: buraya yığın izi yazmak, LogOzeti'nde
            // bilerek kaçındığımız veri sızıntısını arka kapıdan geri getirirdi.
            $table->string('hata')->nullable();

            $table->unsignedInteger('sure_ms')->nullable();
            $table->timestamps();

            // "Son başarılı koşu ne zaman" sorgusu bu indeksten yararlanır.
            $table->index(['tur', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kahya_calismalari');
    }
};
