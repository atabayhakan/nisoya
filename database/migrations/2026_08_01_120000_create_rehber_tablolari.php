<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ülke-Adaptif Rehber — F1 çekirdek veri modeli.
 *
 * Tasarım: docs/plans/2026-08-01-ulke-adaptif-rehber-tasarimi.md (§3).
 * Ürün kararı: Ülke → Temsilcilik → İşlem. URL'deki ikinci segment
 * TEMSİLCİLİK slug'ıdır (K2) — mevcut `cities` tablosu bu iş için
 * elverişsiz (slug'sız, ülke başına 2 tohum kayıt, egzonim adlar) ve
 * ona DOKUNULMAZ.
 *
 * `country_code` countries.code'a (CHAR(2) PK) formal FK KISITI OLMADAN
 * bağlanır — depodaki her coğrafi ilişkinin deseni bu (listings, users,
 * cities aynı şekilde). Desenden sapmak tek tabloyu "farklı" yapar,
 * kazandırdığı şeyse bir seeder sırası kısıtı olurdu.
 *
 * `islem_turleri` ÜLKE-BAĞIMSIZ şablondur: vekaletname Almanya'da da
 * İngiltere'de de vekaletnamedir; ülkeye özgü olan şey İÇERİK
 * (evraklar/ücret/süre) ve o `temsilcilik_islemleri`'nde yaşar. Yeni ülke
 * eklerken tür seti yeniden kurulmaz, yalnız içerik girilir.
 *
 * `dogrulanma_tarihi` süs değil sözleşme (K7): konsolosluk bilgisi hızla
 * eskir ve yanlış evrak listesi kullanıcıyı konsolosluk kapısından geri
 * çevirtir. 90 günü aşan yayındaki kayıt Kâhya'nın günlük raporuna "bayat
 * rehber" uyarısı olarak düşer (bkz. BekleyenIsler).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temsilcilikler', function (Blueprint $table) {
            $table->id();
            $table->char('country_code', 2)->index();
            $table->string('ad');                        // "Köln Başkonsolosluğu"
            $table->string('slug', 80);                  // "koeln" → /de/koeln
            $table->string('tur', 30)->default('baskonsolosluk'); // buyukelcilik|baskonsolosluk
            $table->string('sehir', 120);                // görünen şehir adı ("Köln")
            $table->string('adres', 500)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('resmi_url', 300)->nullable(); // temsilciliğin resmî sitesi
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['country_code', 'slug']);
        });

        Schema::create('islem_turleri', function (Blueprint $table) {
            $table->id();
            $table->string('ad', 120);                   // "Vekaletname"
            $table->string('slug', 80)->unique();        // "vekaletname"
            $table->string('aciklama', 500)->nullable(); // liste sayfasındaki tek cümle
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('temsilcilik_islemleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temsilcilik_id')->constrained('temsilcilikler')->cascadeOnDelete();
            $table->foreignId('islem_turu_id')->constrained('islem_turleri')->cascadeOnDelete();

            /*
             * Evraklar yapılandırılmış JSON: [{"ad": "...", "not": "..."}].
             * Serbest metin değil — "bu bilgi güncel mi?" geri bildirimi ve
             * gelecekteki karşılaştırma ekranları (noter vs konsolosluk)
             * madde düzeyinde veri ister.
             */
            $table->json('evraklar')->nullable();
            $table->string('sure_metni', 200)->nullable();   // "aynı gün" / "2-4 hafta"
            $table->string('ucret_metni', 200)->nullable();  // "48,52 €" — kur/dönem oynar, metin
            $table->string('resmi_kaynak_url', 300);         // birincil CTA; zorunlu (K7)
            $table->text('notlar')->nullable();

            // NULL = hiç doğrulanmadı. Yayında + eski tarih = "bayat" sinyali.
            $table->date('dogrulanma_tarihi')->nullable();
            $table->string('status', 20)->default('taslak'); // taslak|yayin
            $table->timestamps();

            $table->unique(['temsilcilik_id', 'islem_turu_id']);
            $table->index(['status', 'dogrulanma_tarihi']);
        });

        Schema::create('rehber_geri_bildirimleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temsilcilik_islemi_id')->constrained('temsilcilik_islemleri')->cascadeOnDelete();
            $table->string('tur', 20);                   // guncel_degil|hata|oneri
            $table->string('metin', 1000)->nullable();
            // Kimlik değil kötüye-kullanım izi: aynı IP'nin seri gönderimi
            // görülebilsin diye hash'lenmiş IP tutulur, ham IP tutulmaz.
            $table->string('ip_hash', 64)->nullable();
            $table->boolean('incelendi')->default(false);
            $table->timestamps();

            $table->index(['incelendi', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rehber_geri_bildirimleri');
        Schema::dropIfExists('temsilcilik_islemleri');
        Schema::dropIfExists('islem_turleri');
        Schema::dropIfExists('temsilcilikler');
    }
};
