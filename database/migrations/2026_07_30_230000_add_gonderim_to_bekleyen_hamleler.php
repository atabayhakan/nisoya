<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F4 "dış eller": onaylanan e-posta hamleleri artık gerçekten gönderilebilir.
 *
 * `alici_eposta` hamle kartına taşınır (karar veren sahip KİME gideceğini
 * kartta görmeli); `gonderildi_at` günlük ısıtma tavanının saydığı alandır;
 * `gonderim_hata` sessiz başarısızlığı imkânsız kılar — gönderilemeyen
 * onaylı hamle, kartın üstünde nedeniyle durur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bekleyen_hamleler', function (Blueprint $table) {
            $table->string('alici_eposta', 190)->nullable()->after('tur');
            $table->timestamp('gonderildi_at')->nullable()->after('karar_at');
            $table->string('gonderim_hata', 300)->nullable()->after('gonderildi_at');
        });

        /*
         * Suppression (engel) listesi: bir adres buraya girdiyse ona bir
         * daha HİÇBİR hamle gönderilmez — ret/şikâyet/bounce kalıcıdır.
         * Teslim edilebilirliğin ilk kuralı (docs/06 §3): istemeyene
         * ikinci kez yazan alan adı, spam klasörüne taşınır.
         */
        Schema::create('kahya_gonderim_engelleri', function (Blueprint $table) {
            $table->id();
            $table->string('eposta', 190)->unique();
            $table->string('neden', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kahya_gonderim_engelleri');

        Schema::table('bekleyen_hamleler', function (Blueprint $table) {
            $table->dropColumn(['alici_eposta', 'gonderildi_at', 'gonderim_hata']);
        });
    }
};
