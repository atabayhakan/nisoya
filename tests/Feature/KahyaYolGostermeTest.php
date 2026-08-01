<?php

namespace Tests\Feature;

use App\Ai\Kahya\KahyaAjani;
use App\Enums\UserRole;
use App\Filament\Pages\KahyaSohbet;
use App\Models\KahyaMesaji;
use App\Models\User;
use App\Services\Kahya\PanelHaritasi;
use App\Services\Kahya\Sohbet\KahyaSohbeti;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Ai;
use Laravel\Ai\Responses\Data\ToolCall;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Yol gösterme: "X nerede?" cevabı söylemekle kalmaz, GÖSTERİR — menü vurgusu
 * + "Aç" düğmesi. Sınanan sözleşme modelin zekâsı değil, kapının kendisi:
 * hedef ancak panel-yonlendir aracı GERÇEK bir harita adresiyle çağrıldıysa
 * doğar; uydurma adres ne vurgulanır ne düğmeye bağlanır.
 */
class KahyaYolGostermeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    /** @param  list<ToolCall|string>  $yanitlar */
    private function sahteAjan(array $yanitlar): void
    {
        Ai::fakeAgent(KahyaAjani::class, $yanitlar);
    }

    // ------------------------------------------------------- Panel haritası

    public function test_harita_gercek_adresi_bulur(): void
    {
        $hedef = app(PanelHaritasi::class)->bul('/yonetim/tags');

        $this->assertNotNull($hedef);
        $this->assertSame('Etiketler', $hedef->etiket);
        // Kanonik adres YOL biçiminde: alan adından bağımsız, her sunumda çalışır.
        $this->assertSame('/yonetim/tags', $hedef->adres);
    }

    public function test_harita_sondaki_cizgiyi_ve_tam_adresi_tolere_eder(): void
    {
        $harita = app(PanelHaritasi::class);

        $this->assertNotNull($harita->bul('/yonetim/tags/'));
        // Modelin haritadan kopyaladığı tam adres de (alan adıyla) bulunur —
        // yönergedeki harita Filament getUrl() çıktısını, yani mutlak adresi taşır.
        $this->assertNotNull($harita->bul(url('/yonetim/tags')));
    }

    public function test_harita_uydurma_adresi_reddeder(): void
    {
        $harita = app(PanelHaritasi::class);

        $this->assertNull($harita->bul('/yonetim/hayali-ekran'));
        $this->assertNull($harita->bul(''));
        $this->assertNull($harita->bul('https://kotu.site'));
    }

    public function test_harita_yabanci_alan_adini_kanonik_adresle_degistirir(): void
    {
        // Yol kısmı gerçek olsa bile alan adı modelinki değil HARİTANINKİ olur —
        // arayüze modelin yazdığı adres asla olduğu gibi geçmez.
        $hedef = app(PanelHaritasi::class)->bul('https://kotu.site/yonetim/tags');

        $this->assertNotNull($hedef);
        $this->assertStringNotContainsString('kotu.site', $hedef->adres);
    }

    // ------------------------------------------------------------- Sohbet

    public function test_yol_sorusu_hedefi_yanita_ve_mesaja_isler(): void
    {
        $this->sahteAjan([
            new ToolCall('t1', 'panel-yonlendir', ['adres' => '/yonetim/tags']),
            'Etiketler, İçerik grubunda.',
        ]);

        $yanit = app(KahyaSohbeti::class)->sor('etiketler nerede?', $this->admin());

        $this->assertNotNull($yanit->hedef);
        $this->assertSame('Etiketler', $yanit->hedef->etiket);

        // Düğme kalıcı: hedef mesaj kaydına da yazıldı.
        $mesaj = KahyaMesaji::query()->where('rol', KahyaMesaji::ROL_KAHYA)->firstOrFail();
        $this->assertSame($yanit->hedef->adres, $mesaj->hedef_url);
        $this->assertSame('Etiketler', $mesaj->hedef_etiket);
        // Yol gösterme bir "iş" değil: denetim defterine eylem kaydı düşmez.
        $this->assertNull($yanit->eylem);
    }

    public function test_uydurma_adres_hedef_uretmez(): void
    {
        $this->sahteAjan([
            new ToolCall('t1', 'panel-yonlendir', ['adres' => '/yonetim/hayali-ekran']),
            'Onu bulamadım.',
        ]);

        $yanit = app(KahyaSohbeti::class)->sor('hayali ekran nerede?', $this->admin());

        $this->assertNull($yanit->hedef);
        $this->assertNull(KahyaMesaji::query()->where('rol', KahyaMesaji::ROL_KAHYA)->firstOrFail()->hedef_url);
    }

    public function test_hedef_bir_sonraki_tura_sizmaz(): void
    {
        $this->sahteAjan([
            new ToolCall('t1', 'panel-yonlendir', ['adres' => '/yonetim/tags']),
            'Etiketler burada.',
        ]);

        $sohbet = app(KahyaSohbeti::class);
        $admin = $this->admin();

        $this->assertNotNull($sohbet->sor('etiketler nerede?', $admin)->hedef);

        // Aynı servis örneğiyle ikinci tur: araç çağrılmadı → hedef YOK.
        $this->sahteAjan(['Merhaba!']);
        $this->assertNull($sohbet->sor('selam', $admin)->hedef);
    }

    // ------------------------------------------------------------- Arayüz

    public function test_arayuz_vurgu_olayini_yayinlar_ve_ac_dugmesini_basar(): void
    {
        $this->sahteAjan([
            new ToolCall('t1', 'panel-yonlendir', ['adres' => '/yonetim/tags']),
            'Etiketler, İçerik grubunda.',
        ]);

        Livewire::actingAs($this->admin())
            ->test(KahyaSohbet::class)
            ->set('mesaj', 'etiketler nerede?')
            ->call('gonder')
            ->assertSuccessful()
            ->assertDispatched('kahya-yonlendir', etiket: 'Etiketler')
            ->assertSee('Etiketler sayfasını aç');
    }

    public function test_hedefsiz_cevap_olay_yayinlamaz(): void
    {
        $this->sahteAjan(['Şu an 0 aktif ilan var.']);

        Livewire::actingAs($this->admin())
            ->test(KahyaSohbet::class)
            ->set('mesaj', 'kaç ilan var?')
            ->call('gonder')
            ->assertSuccessful()
            ->assertNotDispatched('kahya-yonlendir');
    }

    // ------------------------------------------------------------ Markdown

    public function test_kahya_cevabindaki_kalin_metin_html_olarak_basilir(): void
    {
        $this->sahteAjan(['**Ülke Rehberi** hazır, *yarın* bak.']);

        Livewire::actingAs($this->admin())
            ->test(KahyaSohbet::class)
            ->set('mesaj', 'rehber ne durumda?')
            ->call('gonder')
            ->assertSuccessful()
            ->assertSeeHtml('<strong>Ülke Rehberi</strong>')
            ->assertSeeHtml('<em>yarın</em>');
    }

    public function test_kahya_cevabindaki_html_kacirilir(): void
    {
        $this->sahteAjan(['<script>alert(1)</script> gördün mü?']);

        Livewire::actingAs($this->admin())
            ->test(KahyaSohbet::class)
            ->set('mesaj', 'test')
            ->call('gonder')
            ->assertSuccessful()
            ->assertDontSeeHtml('<script>alert(1)</script>');
    }

    public function test_sahip_mesajindaki_markdown_yorumlanmaz(): void
    {
        $this->sahteAjan(['Tamam.']);

        Livewire::actingAs($this->admin())
            ->test(KahyaSohbet::class)
            ->set('mesaj', 'şunu **aynen** sakla')
            ->call('gonder')
            ->assertSuccessful()
            ->assertSee('şunu **aynen** sakla')
            ->assertDontSeeHtml('<strong>aynen</strong>');
    }
}
