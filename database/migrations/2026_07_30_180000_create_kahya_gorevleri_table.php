<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kâhya'nın görev defteri (F2 — Kâhya 2.0 tasarımı §2.3).
 *
 * "Gerçek kullanıcı bul" gibi haftalarca süren misyonların evi. Sohbet
 * turu dakikalar, hafıza kayıtları cümleler ölçeğindedir; görev defteri
 * HAFTALAR ölçeğidir: hedef + adım planı + ilerleme, günler arasında
 * kaybolmadan taşınır. Günlük rapora "görevlerde durum" bölümü buradan
 * beslenir — misyonlar sessizce ölmez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kahya_gorevleri', function (Blueprint $table) {
            $table->id();
            $table->string('baslik', 150);
            // Misyonun kendisi — "ne bitince bu görev bitti sayılır" cümlesi.
            $table->string('hedef', 500);
            $table->string('durum', 20)->default('acik')->index();
            // [{metin, durum: bekliyor|yapildi|atlandi}] — Kâhya'nın planı.
            $table->json('adimlar')->nullable();
            // [{t, not}] — ekleme sıralı ilerleme günlüğü; en yenisi sonda.
            $table->json('ilerleme_notlari')->nullable();
            $table->timestamp('son_islem_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kahya_gorevleri');
    }
};
