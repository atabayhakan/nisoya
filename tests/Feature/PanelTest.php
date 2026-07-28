<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\DealStatus;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Deal;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Kullanıcı paneli (/panel) — 2026-07-28 yeniden tasarımı.
 *
 * Panel eskiden `Route::view` idi: sayfaya hiç veri geçmiyordu, 14 kart
 * Blade'de sabit bir diziydi ve 3 okunmamış mesajı olan kullanıcı ile dün
 * kaydolmuş kullanıcı birebir aynı ekranı görüyordu.
 *
 * Bu dosya üç şeyi mühürler: (1) sayfanın DÜRÜSTLÜĞÜ — sıfır basılmaz,
 * uydurma sayı yok; (2) SORGU BÜTÇESİ — panel +6'dan fazla sorgu eklemez;
 * (3) EYLEM DOĞRULUĞU — kullanıcıya kendi gönderdiği teklif "seni bekliyor"
 * diye gösterilmez.
 */
class PanelTest extends TestCase
{
    use RefreshDatabase;

    /** Panelin ekleyebileceği sorgu tavanı (soğuk önbellekte toplam). */
    private const SORGU_TAVANI = 20;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function uye(array $ozellikler = []): User
    {
        return User::factory()->create(array_merge(['email_verified_at' => now()], $ozellikler));
    }

    /** @return array<int, string> */
    private function panelKaynaklari(): array
    {
        return array_merge(
            [resource_path('views/panel/dashboard.blade.php')],
            array_map(fn ($f) => $f->getPathname(), File::files(resource_path('views/panel/partials'))),
        );
    }

    // ------------------------------------------------ Dürüstlük bekçileri

    public function test_panel_kaynaklarinda_elle_yazilmis_sayi_dizisi_yok(): void
    {
        foreach ($this->panelKaynaklari() as $dosya) {
            $this->assertDoesNotMatchRegularExpression(
                '/=\s*\[\s*\d+\s*,\s*\d+\s*,\s*\d+/',
                File::get($dosya),
                basename($dosya).' içine elle yazılmış sayı dizisi girmiş — panoda gösterilen '.
                'her sayı gerçek sorgudan gelmeli.'
            );
        }
    }

    public function test_panel_kaynaklarinda_hardcode_renk_yok(): void
    {
        // Vitrin emerald-*'ı mavi rampaya eşliyor; tek bir hex sessizce
        // yanlış renk üretir ve yalnız o temada görünür.
        foreach ($this->panelKaynaklari() as $dosya) {
            $this->assertDoesNotMatchRegularExpression(
                '/#[0-9a-fA-F]{6}\b/',
                File::get($dosya),
                basename($dosya).' içinde hardcode hex renk var — Vitrin temasında yanlış renk verir.'
            );
        }
    }

    public function test_hicbir_panel_partiali_kendi_sorgusunu_atmaz(): void
    {
        // Sözleşme: tüm sinyaller PanelController'da toplanır. Partial'a bir
        // sorgu sızarsa sayfa sessizce N+1'e kayar.
        foreach (File::files(resource_path('views/panel/partials')) as $dosya) {
            $icerik = File::get($dosya->getPathname());

            foreach (['::query(', '->count()', '::where('] as $desen) {
                $this->assertStringNotContainsString(
                    $desen,
                    $icerik,
                    $dosya->getFilename()." içinde sorgu var ({$desen}) — panel partial'ları veri çekmez."
                );
            }
        }
    }

    // ------------------------------------------------ Sıfır basılmaz

    public function test_yeni_kullanici_sifir_rakam_gormez_baslangic_gorur(): void
    {
        $yanit = $this->actingAs($this->uye())->get('/panel')->assertOk();

        $yanit->assertDontSee('Bekleyenler');
        $yanit->assertDontSee('Görüntülenme');
        $yanit->assertDontSee('Aktif ilan');
        $yanit->assertSee('Nereden başlamak istersin?');
        $yanit->assertSee('Bir şey arıyorum');
        $yanit->assertSee('Bir şey sunuyorum');
    }

    public function test_eski_yanlis_not_ve_yinelenen_buton_kaldirildi(): void
    {
        $yanit = $this->actingAs($this->uye())->get('/panel')->assertOk();

        // Bu not aylardır canlı olan bölümler için "sonra devreye girecek"
        // diyordu — kullanıcıya sitenin bitmediğini söylüyordu.
        $yanit->assertDontSee('sonraki adımlarda devreye girecek');
        $yanit->assertDontSee('+ Yeni İlan Ver');
    }

    public function test_mevcut_sozlesmeler_korunur(): void
    {
        $yanit = $this->actingAs($this->uye())->get('/panel')->assertOk();

        $yanit->assertSee('Merhaba');
        $yanit->assertSee('Çıkış Yap');
        $yanit->assertSee(route('logout'), false);
    }

    // ------------------------------------------------ Gerçek sinyaller

    public function test_ilani_olan_kullanici_gercek_rakamlari_gorur(): void
    {
        $uye = $this->uye();
        Listing::factory()->count(2)->create(['user_id' => $uye->id, 'status' => 'aktif', 'views_count' => 7]);

        $yanit = $this->actingAs($uye)->get('/panel')->assertOk();

        $yanit->assertSee('Aktif ilan');
        $yanit->assertSee('Görüntülenme');
        $yanit->assertSee('14'); // 2 ilan × 7 görüntülenme, gerçek toplam
    }

    public function test_bolumler_dizini_her_zaman_gorunur(): void
    {
        // Rol kapısı YOK: tüm ilanlarını silen satıcı "İlanlarım"ı bulabilmeli.
        $yanit = $this->actingAs($this->uye())->get('/panel')->assertOk();

        foreach (['İlanlarım', 'Mesajlar', 'Bildirimler', 'Favorilerim', 'Profilim'] as $bolum) {
            $yanit->assertSee($bolum);
        }
    }

    // ------------------------------------------------ Eylem doğruluğu

    public function test_kendi_gonderdigin_teklif_seni_bekliyor_diye_gosterilmez(): void
    {
        // Teklifi yapan taraf için o teklif EYLEM değil bekleyiştir;
        // filtresiz basmak sahte aciliyet üretirdi.
        [$satici, $alici, $konusma] = $this->anlasmaTaraflari();

        Deal::create([
            'conversation_id' => $konusma->id,
            'listing_id' => null,
            'seller_id' => $satici->id,
            'buyer_id' => $alici->id,
            'proposed_by' => $satici->id,
            'status' => DealStatus::Teklif->value,
            'amount' => 250,
            'currency' => 'EUR',
        ]);

        $this->actingAs($satici)->get('/panel')->assertOk()->assertDontSee('bir teklif gönderdi');
        $this->actingAs($alici)->get('/panel')->assertOk()->assertSee('bir teklif gönderdi');
    }

    public function test_degerlendirme_satiri_karsi_tarafin_profiline_gider(): void
    {
        // Kullanıcı ASLA kendi profiline yönlendirilmemeli.
        [$satici, $alici, $konusma] = $this->anlasmaTaraflari();

        Deal::create([
            'conversation_id' => $konusma->id,
            'listing_id' => null,
            'seller_id' => $satici->id,
            'buyer_id' => $alici->id,
            'proposed_by' => $alici->id,
            'status' => DealStatus::Tamamlandi->value,
            'amount' => 100,
            'currency' => 'EUR',
        ]);

        $this->actingAs($satici)->get('/panel')->assertOk()
            ->assertSee(route('profiles.show', $alici->username), false)
            ->assertDontSee(route('profiles.show', $satici->username), false);
    }

    public function test_sirketi_olmayan_kullaniciya_bedeli_yazan_satir_gosterilir(): void
    {
        $uye = $this->uye();

        $this->actingAs($uye)->get('/panel')->assertOk()
            ->assertSee('Şirket profili oluştur')
            ->assertSee('Yetenek Havuzu');
    }

    public function test_sirketi_olan_kullaniciya_gelen_basvuru_gosterilir(): void
    {
        $isveren = $this->uye();
        $sirket = Company::create(['user_id' => $isveren->id, 'name' => 'Acme', 'slug' => 'acme']);
        $ilan = $sirket->jobListings()->create([
            'title' => 'Aşçı', 'slug' => 'asci', 'description' => 'x',
            'employment_type' => 'tam_zamanli', 'status' => 'aktif', 'positions' => 1,
        ]);
        $ilan->applications()->create([
            'user_id' => $this->uye()->id,
            'status' => ApplicationStatus::Gonderildi->value,
            'notified_status' => ApplicationStatus::Gonderildi->value,
        ]);

        $this->actingAs($isveren)->get('/panel')->assertOk()
            ->assertSee('Şirket Profili')
            ->assertSee('yeni başvuru geldi');
    }

    // ------------------------------------------------ Sorgu bütçesi

    public function test_cok_rollu_kullanicida_sorgu_tavani_asilmaz(): void
    {
        $uye = $this->uye();
        Listing::factory()->count(3)->create(['user_id' => $uye->id, 'status' => 'aktif']);
        $sirket = Company::create(['user_id' => $uye->id, 'name' => 'Acme', 'slug' => 'acme-2']);
        $sirket->jobListings()->create([
            'title' => 'X', 'slug' => 'x', 'description' => 'y',
            'employment_type' => 'tam_zamanli', 'status' => 'aktif', 'positions' => 1,
        ]);
        [$satici, $alici, $konusma] = $this->anlasmaTaraflari($uye);
        Deal::create([
            'conversation_id' => $konusma->id, 'listing_id' => null,
            'seller_id' => $satici->id, 'buyer_id' => $alici->id, 'proposed_by' => $alici->id,
            'status' => DealStatus::Teklif->value, 'amount' => 10, 'currency' => 'EUR',
        ]);

        $this->actingAs($uye)->get('/panel'); // ısınma: ayar/menü önbellekleri

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($uye)->get('/panel')->assertOk();
        $sorgu = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            self::SORGU_TAVANI,
            $sorgu,
            "Panel {$sorgu} sorgu çalıştırdı (tavan ".self::SORGU_TAVANI.'). '.
            'Yeni bir sayaç eklemeden önce PanelSinyalleri docblock\'undaki azaltma defterini oku.'
        );
    }

    /** @return array{0: User, 1: User, 2: Conversation} */
    private function anlasmaTaraflari(?User $satici = null): array
    {
        $satici ??= $this->uye();
        $alici = $this->uye();
        $konusma = Conversation::findOrCreateBetween($satici->id, $alici->id, null);

        return [$satici, $alici, $konusma];
    }
}
