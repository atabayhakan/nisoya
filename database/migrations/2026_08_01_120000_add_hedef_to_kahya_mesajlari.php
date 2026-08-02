<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Yol gösterme: Kâhya "X nerede?" cevabında hedef ekranı işaretler — mesaja
 * yazılır ki cevabın altındaki "Aç" düğmesi sohbet geçmişinde KALICI olsun.
 * Adres modele değil sunucuya aittir: yalnız PanelHaritasi::bul'dan geçen
 * kanonik adres yazılır (bkz. PanelYonlendir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kahya_mesajlari', function (Blueprint $table) {
            $table->string('hedef_url')->nullable()->after('ek_tipi');
            $table->string('hedef_etiket', 120)->nullable()->after('hedef_url');
        });
    }

    public function down(): void
    {
        Schema::table('kahya_mesajlari', function (Blueprint $table) {
            $table->dropColumn(['hedef_url', 'hedef_etiket']);
        });
    }
};
