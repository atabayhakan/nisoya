<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\TasarimAyarlari;
use App\Models\User;
use App\Support\Settings;
use App\Support\TemaJetonlari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Görünüm ve Tema" sayfası — doğruluk mühürleri (2026-08-06).
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Beş ajanlı denetim, sayfanın en büyük sorununun görünüm değil DOĞRULUK
 * olduğunu gösterdi: panel, kodun yapmadığı şeyleri anlatıyordu.
 *
 *   "Mat gece siyahı zemin"  → zemine hiç dokunulmuyordu
 *   "0.5px zarif hatlar"     → öyle bir kenarlık yoktu
 *   "Modern (14px)"          → gerçek değer 12px'ti
 *   "Başlık Yazı Tipi"       → sitenin TÜM fontunu değiştiriyordu
 *   "Varsayılana Sıfırla"    → fabrika ayarına dönmüyordu
 *
 * Bunların ortak yanı: hiçbiri hata vermiyordu. Sahip yanlış bilgiye göre
 * karar veriyor, karşılığını bulamıyor ve sistemin bozuk olduğunu sanıyordu.
 * Bu dosya o sessiz sapmaları mühürler.
 */
class GorunumSayfasiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    // -----------------------------------------------------------------
    // ÖNİZLEME = GERÇEK
    // -----------------------------------------------------------------

    public function test_kose_olcegi_tek_kaynaktan_gelir(): void
    {
        /*
         * Panelin önizlemesi kendi köşe tablosunu tutuyordu ve motorunkiyle
         * ÇOKTAN AYRIŞMIŞTI: modern 14px↔12px, pill 24px↔18px. Yani "anlık
         * önizleme" rozetli kutu yanlış köşe gösteriyordu. İşin ironisi,
         * hemen yanında tam bunu yasaklayan bir yorum duruyordu.
         */
        $this->assertSame('.75rem', TemaJetonlari::koseOlcegi('modern')['xl']);
        $this->assertSame('18px', TemaJetonlari::koseOlcegi('pill')['xl']);

        // Motor da aynı metottan okumalı — sayfa kaynağında doğrula.
        $motor = file_get_contents(resource_path('views/components/tasarim-theme.blade.php'));
        $this->assertStringContainsString('TemaJetonlari::koseOlcegi', (string) $motor);
        $this->assertStringNotContainsString("'pill' => ['lg'", (string) $motor, 'Ölçek motorda tekrar tanımlanmamalı.');
    }

    public function test_kose_etiketlerinde_piksel_yazmaz(): void
    {
        // "Modern (14px)" hiçbir katmanla eşleşmiyordu; her etiket başka bir
        // katmandan sayı almıştı. Sayı vermeyen ad, yanlış sayı veren addan iyi.
        foreach (TemaJetonlari::koseSecenekleri() as $etiket) {
            $this->assertDoesNotMatchRegularExpression('/\d+px/', $etiket, "Etiketde piksel olmamalı: {$etiket}");
        }
    }

    // -----------------------------------------------------------------
    // FABRİKA VARSAYILANI
    // -----------------------------------------------------------------

    public function test_sifirlama_gercek_fabrika_ayarina_doner(): void
    {
        /*
         * "Varsayılana Sıfırla" `secPreset('eski')` çağırıyordu; o paket
         * border_radius='soft' ve cam KAPALI yazıyor, oysa fabrika varsayılanı
         * modern + cam AÇIK. Kurtarma refleksi olması gereken düğme durumu
         * daha da kaydırıyordu.
         */
        Settings::setMany([
            'gorunum.primary_color' => '#7c3aed',
            'gorunum.marka_rengi' => 'violet',
            'gorunum.border_radius' => 'pill',
            'gorunum.glassmorphism' => '0',
        ]);
        Cache::flush();

        $this->actingAs($this->admin());
        Livewire::test(TasarimAyarlari::class)->call('sifirla');

        $this->assertSame('#059669', Settings::get('gorunum.primary_color'));
        $this->assertSame('modern', Settings::get('gorunum.border_radius'));
        $this->assertSame('1', Settings::get('gorunum.glassmorphism'));
        // Marka ailesi de dönmeli; yoksa site MOR kalır, panel yeşil gösterir.
        $this->assertSame('emerald', Settings::get('gorunum.marka_rengi'));
    }

    public function test_sifirlama_tema_secimini_degistirmez(): void
    {
        // Sıfırlama görünüm ayarlarını toparlar; tüm siteyi başka bir tasarıma
        // geçirmek apayrı ve çok daha yıkıcı bir karardır.
        Settings::setMany(['gorunum.tema' => 'vitrin']);
        Cache::flush();

        $this->actingAs($this->admin());
        Livewire::test(TasarimAyarlari::class)->call('sifirla');

        $this->assertSame('vitrin', Settings::get('gorunum.tema'));
    }

    // -----------------------------------------------------------------
    // RENK TEK KONTROLDÜR
    // -----------------------------------------------------------------

    public function test_panelden_renk_yazmak_hazir_aileyi_sifirlar(): void
    {
        /*
         * Klasik iskelet hem hazır aileyi (marka_rengi) hem serbest hex'i
         * (primary_color) basıyor ve ikincisi yalnız VARSAYILANDAN FARKLIYSA
         * devreye giriyor. İkisi bağımsız kalırsa: sahip sitedeki panelden
         * "mor" seçer, sonra buradan yeşili tıklar → site MOR kalır, panel
         * yeşil gösterir. TemaOzellestiriciController bu kuralı zaten
         * uyguluyordu; bu sayfa uygulamıyordu.
         */
        Settings::setMany(['gorunum.marka_rengi' => 'violet']);
        Cache::flush();

        $this->actingAs($this->admin());
        Livewire::test(TasarimAyarlari::class)
            ->set('primaryColor', '#2563eb')
            ->call('kaydetCustom');

        $this->assertSame('#2563eb', Settings::get('gorunum.primary_color'));
        $this->assertSame('emerald', Settings::get('gorunum.marka_rengi'));
    }

    public function test_ozel_renk_en_koyu_tonu_da_turetir(): void
    {
        /*
         * Rampa 50→900 türetiliyor, 950 atlanıyordu — ama emerald-950 sitede
         * onlarca yerde kullanılıyor. Sahip mor seçtiğinde o tonlarda YEŞİL
         * kalıntı kalıyordu; "tüm tonlar tek hex'ten türer" iddiası doğru
         * değildi.
         */
        Settings::setMany(['gorunum.primary_color' => '#7c3aed', 'gorunum.tema' => 'klasik']);
        Cache::flush();

        $this->get('/')->assertOk()->assertSee('--color-emerald-950:', false);
    }

    // -----------------------------------------------------------------
    // PAKET AÇIKLAMALARI VAAT DEĞİL ÖZET
    // -----------------------------------------------------------------

    public function test_paket_ozetleri_kodun_yapmadigini_vaat_etmez(): void
    {
        /*
         * Ölçülmüş yalanlar (hepsi ajan denetiminde bulundu ve kodda
         * doğrulandı): obsidian zemine dokunmuyor, nordic'te 0.5px kenarlık
         * yok ve fontu Zümrüt ile aynı, hiçbir yerde italik basılmıyor.
         */
        $yasakli = ['gece siyahı', 'neon', '0.5px', 'İskandinav', 'italik', 'lüks lüks', 'hazıl'];

        foreach (TasarimAyarlari::PRESETLER as $anahtar => $paket) {
            foreach ($yasakli as $ifade) {
                $this->assertStringNotContainsString(
                    $ifade,
                    $paket['ozet'],
                    "'{$anahtar}' paketinin özeti karşılığı olmayan bir vaat içeriyor: {$ifade}"
                );
            }
        }
    }

    public function test_her_paket_gercekten_bes_ayari_yazar(): void
    {
        // Özet bu beş değerin okunuşudur; biri eksilirse özet sessizce
        // eksik anlatmaya başlar.
        foreach (TasarimAyarlari::PRESETLER as $anahtar => $paket) {
            foreach (['tasarim_modu', 'primary_color', 'font_family', 'border_radius', 'glassmorphism'] as $alan) {
                $this->assertArrayHasKey("gorunum.{$alan}", $paket['ayarlar'], "{$anahtar} paketi {$alan} yazmalı.");
            }
        }
    }

    // -----------------------------------------------------------------
    // SAYFANIN KENDİSİ
    // -----------------------------------------------------------------

    public function test_sayfa_hangi_temanin_yayinda_oldugunu_metinle_soyler(): void
    {
        // Eskiden bu olgu hiçbir yerde metin değildi; sahip iki kart
        // ızgarasını tarayıp yeşil halka aramak zorundaydı.
        Settings::setMany(['gorunum.tema' => 'klasik', 'gorunum.tasarim_modu' => 'nordic']);
        Cache::flush();

        $this->actingAs($this->admin())
            ->get('/yonetim/tasarim-ayarlari')
            ->assertOk()
            ->assertSee('Şu an yayında')
            ->assertSee('Nordik');
    }

    public function test_menu_adi_ile_sayfa_basligi_ayni(): void
    {
        // Sayfanın ÜÇ ayrı adı vardı; sahip birinde okuduğunu diğerinde
        // bulamıyordu. Panelin geri kalanında böyle bir sapma yok.
        $this->assertSame(TasarimAyarlari::getNavigationLabel(), (new TasarimAyarlari)->getTitle());
    }

    public function test_tema_degistirme_onay_ister(): void
    {
        // Sitenin tamamını tüm ziyaretçiler için değiştiren düğme onaysızdı.
        $icerik = (string) file_get_contents(resource_path('views/filament/pages/tasarim-ayarlari.blade.php'));

        $this->assertStringContainsString('wire:confirm', $icerik);
        $this->assertMatchesRegularExpression('/wire:click="secTema/', $icerik);
    }
}
