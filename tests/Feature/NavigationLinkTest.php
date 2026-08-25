<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\IslemTuru;
use App\Models\NavigationLink;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\NavigationLinkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NavigationLinkTest extends TestCase
{
    use RefreshDatabase;

    private function yayindaTemsilcilik(string $ulkeKodu, string $sehir, string $slug): void
    {
        $temsilcilik = Temsilcilik::query()->create([
            'country_code' => $ulkeKodu, 'ad' => $sehir.' Büyükelçiliği', 'slug' => $slug,
            'tur' => Temsilcilik::TUR_BUYUKELCILIK, 'sehir' => $sehir, 'is_active' => true,
        ]);
        $tur = IslemTuru::query()->create(['ad' => 'Vekaletname', 'slug' => 'vekaletname-'.$slug, 'is_active' => true]);
        TemsilcilikIslemi::query()->create([
            'temsilcilik_id' => $temsilcilik->id, 'islem_turu_id' => $tur->id,
            'evraklar' => [], 'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
            'status' => TemsilcilikIslemi::STATUS_YAYIN,
        ]);
    }

    public function test_seeder_creates_expected_links_in_order(): void
    {
        $this->seed(NavigationLinkSeeder::class);

        $labels = NavigationLink::query()->orderBy('sort_order')->pluck('label')->all();

        $this->assertSame(['İlanlar', 'Yetenek Havuzu', 'İş İlanları', 'Emlak', 'Vasıta', 'Anılar & Davetiyeler', 'Harita', 'Nasıl Çalışır?'], $labels);
    }

    public function test_seeder_does_not_duplicate_or_overwrite_admin_edits(): void
    {
        $this->seed(NavigationLinkSeeder::class);

        NavigationLink::where('label', 'İlanlar')->first()->update(['is_active' => false]);

        $this->seed(NavigationLinkSeeder::class);

        $this->assertSame(8, NavigationLink::count());
        $this->assertDatabaseHas('navigation_links', ['label' => 'İlanlar', 'is_active' => false]);
    }

    public function test_active_cached_excludes_inactive_and_respects_order(): void
    {
        NavigationLink::create(['label' => 'B', 'url' => '/b', 'sort_order' => 2, 'is_active' => true]);
        NavigationLink::create(['label' => 'A', 'url' => '/a', 'sort_order' => 1, 'is_active' => true]);
        NavigationLink::create(['label' => 'Gizli', 'url' => '/gizli', 'sort_order' => 0, 'is_active' => false]);

        $labels = NavigationLink::activeCached()->pluck('label')->all();

        $this->assertSame(['A', 'B'], $labels);
    }

    public function test_header_renders_links_from_database(): void
    {
        NavigationLink::create(['label' => 'ÖzelMenüLinki', 'url' => '/ozel', 'sort_order' => 1, 'is_active' => true]);

        $this->get('/')->assertOk()->assertSee('ÖzelMenüLinki');
    }

    public function test_header_groups_kesfet_links_into_mega_menu_and_keeps_singles_direct(): void
    {
        $this->seed(NavigationLinkSeeder::class);

        $this->get('/')->assertOk()
            ->assertSee('Keşfet')
            ->assertSee('Anılar & Davetiyeler')
            ->assertSee('Etkinlik anılarını keşfet')
            ->assertSee('Harita');
    }

    /**
     * Gerçek olay (2026-08-25, canlıda ölçüldü): "Konsolosluk Rehberi" kartı
     * elle sabit `/de` olarak eklenmişti — Kırgızistan'daki bir üye bile
     * Almanya'ya düşüyordu. Sentinel artık üyenin KENDİ ülkesine çözülür.
     */
    public function test_rehber_giris_sentinel_uyenin_kendi_ulkesine_cozulur(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
        $this->yayindaTemsilcilik('KG', 'Bişkek', 'biskek');
        $this->yayindaTemsilcilik('DE', 'Berlin', 'berlin');
        NavigationLink::create([
            'label' => 'Konsolosluk Rehberi', 'url' => NavigationLink::REHBER_GIRIS_SENTINEL,
            'group_key' => NavigationLink::GROUP_KESFET, 'sort_order' => 1, 'is_active' => true,
        ]);

        $uye = User::factory()->create(['country_code' => 'KG', 'email_verified_at' => now()]);

        $html = $this->actingAs($uye)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(route('rehber.ulke', 'kg'), $html);
        $this->assertStringNotContainsString('"/de"', $html);
        // Ham sentinel HİÇBİR YERDE (mega menü kartı + Cmd+K komut paleti
        // ikisi de aynı çözümlenmiş $navLinks'ten besleniyor) sızmamalı.
        $this->assertStringNotContainsString(NavigationLink::REHBER_GIRIS_SENTINEL, $html);
    }

    /** Hazır ülke yoksa (girisNoktasiUlkeKodu null) kart kırık bir söz vermez, hiç basılmaz. */
    public function test_rehber_giris_sentinel_hazir_ulke_yoksa_kart_gizlenir(): void
    {
        // RehberYuzeyi::varsayilanUlkeKodu() 10 dk cache'li ve testler arası
        // paylaşılan cache mağazasını kullanır (RefreshDatabase yalnız DB'yi
        // sıfırlar) — önceki testin dolu sonucuna yapışmasın diye temizlenir.
        Cache::forget('rehber.varsayilan-ulke');
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
        NavigationLink::create([
            'label' => 'Konsolosluk Rehberi', 'url' => NavigationLink::REHBER_GIRIS_SENTINEL,
            'group_key' => NavigationLink::GROUP_KESFET, 'sort_order' => 1, 'is_active' => true,
        ]);

        $this->get('/')->assertOk()->assertDontSee('Konsolosluk Rehberi');
    }

    /** Sentinel OLMAYAN linkler her zamanki gibi olduğu gibi geçer (regresyon). */
    public function test_sentinel_olmayan_kesfet_linki_degismeden_gecer(): void
    {
        NavigationLink::create([
            'label' => 'İlanlar', 'url' => '/ilanlar',
            'group_key' => NavigationLink::GROUP_KESFET, 'sort_order' => 1, 'is_active' => true,
        ]);

        $this->get('/')->assertOk()->assertSee('"/ilanlar"', false);
    }

    public function test_admin_can_manage_navigation_links(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $link = NavigationLink::create(['label' => 'Test', 'url' => '/test', 'sort_order' => 1]);

        $this->actingAs($admin)->get('/yonetim/navigation-links')->assertOk();
        $this->actingAs($admin)->get('/yonetim/navigation-links/create')->assertOk();
        $this->actingAs($admin)->get("/yonetim/navigation-links/{$link->id}/edit")->assertOk();
    }

    public function test_member_cannot_access_navigation_link_admin(): void
    {
        $member = User::factory()->create([
            'role' => UserRole::Uye,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($member)->get('/yonetim/navigation-links')->assertRedirect(route('dashboard'));
    }
}
