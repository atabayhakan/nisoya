<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Görsel küçültücünün İLAN FORMLARINA BAĞLI olduğunu zorlar.
 *
 * ---------------------------------------------------------------------------
 * BU TESTİN SINIRI — okumadan güvenme
 *
 * Bu test yalnız KABLONUN yerinde olduğunu kanıtlar: bileşen app.js'te kayıtlı
 * mı, formlarda `x-data`/`@change` var mı. JavaScript'in gerçekten koştuğunu
 * KANITLAMAZ — bu depoda tam da bu yanılgıdan bir hata canlıya gitti
 * (x-data içindeki çift tırnak bileşeni öldürdü, 2000+ test yeşil kaldı).
 *
 * Davranışın kendisi tarayıcıda ölçüldü (2026-08-12):
 *   11.17 MB / 4032x3024  →  1.37 MB / 2048x1536  (~500 ms, %88 azalma)
 *   4 KB'lık küçük dosyaya DOKUNULMADI (EXIF korunsun diye bilinçli).
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Sunucu sınırı görsel başına 4 MB. Telefonla çekilen 12 MP fotoğraf bunu
 * rahatlıkla aşıyor ve ilan HİÇ OLUŞMUYORDU. Küçültme kaldırılırsa o duvar
 * geri gelir; bu test kaldırılmasını zorlaştırır.
 */
class GorselKucultucuBagliMiTest extends TestCase
{
    use RefreshDatabase;

    private function uye(): User
    {
        $this->seed([CountrySeeder::class, CurrencySeeder::class, CategorySeeder::class]);

        return User::factory()->create(['country_code' => 'DE']);
    }

    public function test_bilesen_app_js_icinde_kayitli(): void
    {
        $js = File::get(resource_path('js/app.js'));

        $this->assertStringContainsString("Alpine.data('gorselKucultucu'", $js,
            'Küçültücü bileşeni app.js\'ten kaldırılmış — formlardaki x-data ölü kalır.');
        $this->assertStringContainsString('createImageBitmap', $js);
        $this->assertStringContainsString('toBlob', $js);
    }

    public function test_yeni_ilan_formu_kucultucuye_bagli(): void
    {
        $html = $this->actingAs($this->uye())
            ->get(route('panel.listings.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('gorselKucultucu', $html,
            'Yeni ilan formunda küçültücü yok — telefon fotoğrafı yine 4 MB duvarına çarpar.');
        $this->assertStringContainsString('secildi($event)', $html);
    }

    public function test_duzenleme_formu_da_kucultucuye_bagli(): void
    {
        /*
         * Düzenleme ekranı UNUTULMAYA AÇIK: aynı 4 MB duvarı orada da var ve
         * kullanıcı görseli çoğu zaman ilanı kaydettikten SONRA ekliyor.
         */
        $user = $this->uye();
        $ilan = Listing::factory()->create(['user_id' => $user->id]);

        $html = $this->actingAs($user)
            ->get(route('panel.listings.edit', $ilan))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('gorselKucultucu', $html,
            'Düzenleme formunda küçültücü yok.');
        $this->assertStringContainsString('secildi($event)', $html);
    }
}
