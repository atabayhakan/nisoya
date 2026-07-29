<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kâhya'nın KONUŞABİLİR ve EYLEM YAPABİLİR hâline geçişi.
 *
 * ---------------------------------------------------------------------------
 * BU BİR KURAL DEĞİŞİKLİĞİ — ve bilerek yapıldı
 *
 * Kâhya bugüne kadar hiçbir ortamda yazamıyordu; bu söz `SaltOkunurBekci` ile
 * veritabanı katmanında zorlanıyordu. Sahip 2026-07-30'da kararı değiştirdi:
 * "şunu yap dediğimde gidip yapsın".
 *
 * Değişen şey yazma YASAĞI, yazma SERBESTLİĞİ değil. Yapay zekâ hâlâ SQL
 * yazamaz, model kaydetmez, tabloya dokunamaz. Yalnızca ÖNCEDEN TANIMLANMIŞ
 * bir eylem listesinden seçim yapar ve parametrelerini doldurur.
 *
 * Nedeni tek cümleyle: bir dil modeli için "Japonya'yı ülkelere ekle" ile
 * "bütün ilanları sil" aynı şekilli cümledir. Güvenliği cümleyi anlamak
 * kuramaz; yalnızca yapılabilecek işlerin listesi kurar.
 *
 * ---------------------------------------------------------------------------
 * `geri_alma` KOLONU — sözün kanıtı
 *
 * Her eylem, UYGULANMADAN ÖNCE kendisini geri alacak veriyi buraya yazar.
 * "Ülke ekle" için silinecek kimlik; "ayar doldur" için önceki değer; "ilan
 * onayla" için önceki durum. Geri alınamayan bir eylem, katalogda yeri olmayan
 * bir eylemdir.
 *
 * Bu kolon `demo_kayitlari.dosyalar` ile aynı düşüncenin ürünü: bir işi
 * yapabilmek onu geri alabilmekten daha kolaydır, o yüzden geri alma yolunu
 * işi yaparken yazarsın — sonradan değil.
 *
 * ---------------------------------------------------------------------------
 * `durum` NEDEN VAR
 *
 * Yüksek riskli eylemler önce `beklemede` yazılır ve UYGULANMAZ; sahip
 * panelde tam olarak ne değişeceğini görüp onaylayınca `uygulandi` olur.
 * Düşük riskliler doğrudan `uygulandi` doğar ve "şunu yaptım, geri al"
 * denir. Onay kapısının kendisi veritabanında duruyor ki arayüz çökse bile
 * bekleyen bir eylem kaybolmasın.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kahya_eylemleri', function (Blueprint $table) {
            $table->id();

            $table->string('eylem', 80);            // katalogdaki ad, ör. 'ulke-ekle'
            $table->string('durum', 20)->default('beklemede');
            $table->string('risk', 10)->default('dusuk');

            $table->json('parametreler');
            $table->text('onizleme');               // insan-okur: "ne olacak"

            /*
             * Geri alma izi. NULL olması "geri alınamaz" demek DEĞİL — eylem
             * henüz uygulanmadı demek. Uygulanmış bir eylemde bu alan doluysa
             * geri alınabilir; boşsa eylem sınıfı geri almayı desteklemiyordur
             * ve katalog böyle bir eylemi kabul etmez.
             */
            $table->json('geri_alma')->nullable();

            $table->text('sonuc')->nullable();      // uygulandıktan sonra ne oldu
            $table->text('hata')->nullable();

            // Kim istedi. `nullOnDelete`: kullanıcı silinse bile eylem kaydı
            // denetim izi olarak kalmalı.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('uygulandi_at')->nullable();
            $table->timestamp('geri_alindi_at')->nullable();
            $table->timestamps();

            $table->index(['durum', 'created_at']);
            $table->index('eylem');
        });

        /*
         * Konuşma hafızası.
         *
         * Sahip birden çok projeyle çalışıyor ve kendi ifadesiyle "hafızasında
         * bazı şeyler kalmıyor". Kâhya'nın değeri tam da burada: geçen sefer ne
         * konuşulduğunu ve neye karar verildiğini O hatırlar.
         */
        Schema::create('kahya_mesajlari', function (Blueprint $table) {
            $table->id();

            $table->string('rol', 20);              // 'sahip' | 'kahya'
            $table->text('metin');

            // Bu mesaj bir eyleme yol açtıysa bağı kurulur — "neyi neden
            // yaptın" sorusunun cevabı sohbetin içinde kalsın.
            $table->foreignId('kahya_eylemi_id')->nullable()->constrained('kahya_eylemleri')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kahya_mesajlari');
        Schema::dropIfExists('kahya_eylemleri');
    }
};
