<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Filament\Pages\YatirimciMemosu;
use App\Filament\Pages\Yedekleme;
use App\Models\Conversation;
use App\Models\DosyaAnlikGoruntusu;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use App\Reports\NisoyaDosyasi;
use App\Services\Rehber\PanelDegisimi;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El Kitabı M2 — yatırımcı memosu, kanıt defteri, panel değişim sinyali.
 *
 * En kritik bekçi `memo_ozellik_saymaz`: bu belgenin değeri ne YAZDIĞINDA
 * değil, ne YAZMADIĞINDA. Özellik listesi yatırımcıya traction değil,
 * traction yokluğunun itirafı olarak okunur — belge oraya kaymamalı.
 */
class ElKitabiM2Test extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    /** İki tarafın da yazdığı bir konuşma — "karşılıklı ilk temas". */
    private function karsilikliKonusma(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $konusma = Conversation::create(['user_one_id' => $a->id, 'user_two_id' => $b->id]);

        Message::create(['conversation_id' => $konusma->id, 'sender_id' => $a->id, 'body' => 'merhaba']);
        Message::create(['conversation_id' => $konusma->id, 'sender_id' => $b->id, 'body' => 'merhaba']);
    }

    // ------------------------------------------------------------- metrikler

    public function test_karsilikli_temas_tek_tarafli_mesaji_saymaz(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $konusma = Conversation::create(['user_one_id' => $a->id, 'user_two_id' => $b->id]);
        Message::create(['conversation_id' => $konusma->id, 'sender_id' => $a->id, 'body' => 'merhaba']);

        // Tek taraflı mesaj TEMAS DEĞİLDİR. Aynı tanım değerlendirme
        // kapısında da kullanılıyor; iki ayrı "temas" tanımı olmamalı.
        $this->assertSame(0, app(NisoyaDosyasi::class)->huni()['karsilikli']);

        $this->karsilikliKonusma();

        $this->assertSame(1, app(NisoyaDosyasi::class)->huni()['karsilikli']);
    }

    public function test_kuzey_yildizi_sekiz_hafta_dondurur(): void
    {
        $haftalar = app(NisoyaDosyasi::class)->kuzeyYildizi();

        // Sıfır hafta SIFIR görünmeli — eksik hafta atlanırsa grafik
        // olduğundan iyi görünür.
        $this->assertCount(8, $haftalar);
        $this->assertSame(0, $haftalar[0]['adet']);
    }

    // ------------------------------------------------------------------ memo

    public function test_memo_acilir(): void
    {
        Livewire::actingAs($this->admin())
            ->test(YatirimciMemosu::class)
            ->assertOk()
            ->assertSee('Arz: bugünkü gerçek rakam');
    }

    public function test_memo_ozellik_saymaz(): void
    {
        $yanit = Livewire::actingAs($this->admin())->test(YatirimciMemosu::class);

        // Belge "şunları da yaptık" listesine kaymamalı. Özellikler ancak
        // sermaye verimliliği çerçevesinde geçer.
        $yanit->assertSee('Özellik listesi bu belgede yok');
        $yanit->assertDontSee('Açık modüller');
    }

    public function test_memo_darbogazi_gizlemez(): void
    {
        Listing::factory()->count(2)->for(User::factory())->create([
            'status' => ListingStatus::Aktif, 'is_demo' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(YatirimciMemosu::class)
            ->assertSee('Darboğaz burada');
    }

    public function test_yazilmamis_anlati_bolumu_basilmaz(): void
    {
        // Doldurulmamış bir başlık, olmayan bir bölümden kötüdür.
        $yanit = Livewire::actingAs($this->admin())->test(YatirimciMemosu::class);

        $yanit->assertDontSee('1 · Problem ve bugünkü ikame');
        $yanit->assertSee('Bu bölümler henüz yazılmadı');

        Settings::setMany(['memo.problem' => 'Yurt dışındaki Türkler bugün WhatsApp gruplarında arıyor.']);

        Livewire::actingAs($this->admin())
            ->test(YatirimciMemosu::class)
            ->assertSee('1 · Problem ve bugünkü ikame')
            ->assertSee('WhatsApp gruplarında');
    }

    // --------------------------------------------------------- kanıt defteri

    public function test_anlik_goruntu_deftere_yazilir(): void
    {
        Listing::factory()->count(4)->for(User::factory())->create([
            'status' => ListingStatus::Aktif, 'is_demo' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(YatirimciMemosu::class)
            ->call('anligiKaydet');

        $kayit = DosyaAnlikGoruntusu::first();

        $this->assertNotNull($kayit);
        $this->assertSame(4, $kayit->veri['envanter']['ilan']);
        $this->assertArrayHasKey('huni', $kayit->veri);
    }

    public function test_defter_yalniz_eklenir(): void
    {
        // Geçmiş rakamı düzeltmek, geçmişi kaybetmektir.
        $this->assertNull(DosyaAnlikGoruntusu::UPDATED_AT);
    }

    // ------------------------------------------------------ panel değişimi

    public function test_ilk_olcumde_her_ekran_yeni_sayilmaz(): void
    {
        // Kıyas tabanı yokken "hepsi yeni" denseydi ilk raporda panelin
        // tamamı yenilik diye listelenirdi — sinyal ilk gün gürültü olurdu.
        $this->assertSame([], app(PanelDegisimi::class)->yeniEkranlar());
    }

    public function test_rehberde_sayfasi_olmayan_ekranlar_bulunur(): void
    {
        $kapsanmayan = app(PanelDegisimi::class)->kapsanmayanEkranlar();

        // Rehber 11 sayfa; panel çok daha büyük. Kapsanmayan liste dolu
        // olmalı — bu, yazılacak sayfaların kaynağı.
        $this->assertNotEmpty($kapsanmayan);

        // Rehber sayfası OLAN ekran listede olmamalı.
        $this->assertNotContains(Yedekleme::class, $kapsanmayan);
    }
}
