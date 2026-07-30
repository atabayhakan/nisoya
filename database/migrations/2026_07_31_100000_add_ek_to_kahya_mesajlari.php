<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sohbete resim/dosya eki — sahip bir ekran görüntüsü ya da belge eklemek
 * istediğinde. Yapay zekâ eki GÖREMEZ (metin tabanlı prompt), yalnızca
 * bir dosyanın eklendiğini bilir (bkz. KahyaSohbeti::sor). Bu yüzden ekler
 * salt saklama/gösterim amaçlıdır — sahibin kendi referansı ya da rapor
 * ekran görüntüsü için.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kahya_mesajlari', function (Blueprint $table) {
            $table->string('ek_yolu')->nullable()->after('metin');
            $table->string('ek_ad')->nullable()->after('ek_yolu');
            $table->string('ek_tipi', 120)->nullable()->after('ek_ad');
        });
    }

    public function down(): void
    {
        Schema::table('kahya_mesajlari', function (Blueprint $table) {
            $table->dropColumn(['ek_yolu', 'ek_ad', 'ek_tipi']);
        });
    }
};
