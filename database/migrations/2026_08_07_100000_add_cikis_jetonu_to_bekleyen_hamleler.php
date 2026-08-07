<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `cikis_jetonu` — her erişim postasının kendi "listeden çık" anahtarı.
 *
 * ---------------------------------------------------------------------------
 * NEDEN KOLON, NEDEN ADRESİ URL'E KOYMUYORUZ
 *
 * Çıkış bağlantısı postanın içinde gider ve alıcı tıklayınca adresi kalıcı
 * engel listesine yazar. En kolay yol adresi URL'e gömmekti
 * (`/cikis?e=info@dernek.example`) — YAPILMADI: URL'ler sunucu loglarına,
 * yönlendirme zincirlerine ve Referer başlıklarına düşer; oraya üçüncü bir
 * tarafın e-posta adresini koymak veri sızdırmaktır.
 *
 * Onun yerine hamle kartının kendisine rastgele bir jeton yazılıyor. Jeton
 * → kart → alıcı zinciri yalnız sunucuda kuruluyor. Yan fayda: çıkışın HANGİ
 * mesajdan geldiği de belli oluyor (kartın kendisi iz).
 *
 * Jeton gönderim ANINDA üretilir (bkz. HamleGonderici) — gönderilmemiş kartın
 * jetonu olmaz, yani sızdırılacak bir bağlantı da yoktur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bekleyen_hamleler', function (Blueprint $table) {
            $table->string('cikis_jetonu', 64)->nullable()->unique()->after('gonderim_hata');
        });
    }

    public function down(): void
    {
        Schema::table('bekleyen_hamleler', function (Blueprint $table) {
            $table->dropUnique(['cikis_jetonu']);
            $table->dropColumn('cikis_jetonu');
        });
    }
};
