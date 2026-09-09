<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Otomatik temsilî görsel üretiminin BİR KEZ denendiğinin damgası.
 *
 * ---------------------------------------------------------------------------
 * NEDEN GEREKLİ
 *
 * `listings:generate-representative-images` komutu her gün "2+ gün önce
 * eklendi, hâlâ görseli yok" ilanları tarayacak. Bu damga olmadan komut her
 * koşuda AYNI kalıcı-görselsiz ilanı yeniden dener: üretim API'si o ilan için
 * sistematik başarısız oluyorsa (ör. istem reddi) ya da ilan ahlaki kapıda
 * Beklemede'ye düştüyse, o ilan sonsuza dek her gün yeniden denenir — hem
 * gereksiz AI çağrısı parası hem de aynı ilanın gün gün Beklemede-Beklemede
 * bildirimiyle sahibini boğması demek.
 *
 * `tips_notified_at` (2026-08-xx, İlan İpuçları) ile AYNI desen: bir kez
 * dene, sonucu ne olursa olsun damga bas, bir daha dokunma. Sahip isterse
 * kendi fotoğrafını yükler; komut ikinci bir şans sunmaz — sunsaydı "üretim
 * sürekli deneniyor" diye kafası karışırdı.
 *
 * NEDEN AYRI SÜTUN, `images_queued_at` DEĞİL: o sütun GERÇEK yükleme
 * kuyruğuna ait (ProcessListingImage) — bu farklı bir iş (otomatik/temsilî),
 * karıştırılırsa biri diğerinin "işleniyor" durumunu yanlış okur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->timestamp('temsili_gorsel_denendi_at')->nullable()->after('images_failed');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('temsili_gorsel_denendi_at');
        });
    }
};
