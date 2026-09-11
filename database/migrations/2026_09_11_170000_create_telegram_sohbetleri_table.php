<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kâhya'nın Telegram grubunda cevapladığı soruların günlüğü.
 *
 * Her satır TUTULMAZ diye değil, TÜMÜ tutulmaz diye var — bekçi (bkz.
 * App\Support\HassasKonuBekcisi) vize/hukuk/para gibi hassas bir konu
 * yakaladığında `needs_review` işaretlenir ve sahibe panelde görünür.
 * Amaç tam bir sohbet arşivi değil, "yanlış giden oldu mu" denetimi.
 *
 * Metin kolonları TEXT: MySQL'de VARCHAR sınırı serbest/AI-üretimi metinde
 * iki kez veri kaybına yol açmıştı (bkz. proje hafızası "MySQL Strict/
 * VARCHAR Tuzağı") — SQLite dayatmaz, MySQL strict mode dayatır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_sohbetleri', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_chat_id', 64)->index();
            $table->string('telegram_kullanici_id', 64)->nullable();
            $table->string('telegram_kullanici_adi', 190)->nullable();
            $table->text('soru_metni');
            $table->text('cevap_metni')->nullable();
            // vize_hukuk | para | null — bkz. App\Support\HassasKonuBekcisi.
            $table->string('kategori', 40)->nullable();
            $table->boolean('needs_review')->default(false)->index();
            // Şimdilik hep 'telegram'; ileride whatsapp eklenirse şema
            // değişmeden aynı ekrana oturur (bkz. tasarım notu).
            $table->string('kanal', 20)->default('telegram');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_sohbetleri');
    }
};
