<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Zone;
use Database\Seeders\ZoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_zone_seeder_creates_expected_zones(): void
    {
        $this->seed(ZoneSeeder::class);

        $this->assertDatabaseHas('zones', ['key' => 'anasayfa_ust', 'is_active' => true]);
        $this->assertDatabaseHas('zones', ['key' => 'ilan_listesi_ust', 'is_active' => true]);
        $this->assertDatabaseHas('zones', ['key' => 'ilan_detay_yan', 'is_active' => true]);
        $this->assertDatabaseHas('zones', ['key' => 'anasayfa_alt', 'is_active' => false]);
        $this->assertDatabaseHas('zones', ['key' => 'is_ilanlari_liste_ust', 'is_active' => false]);
        $this->assertDatabaseHas('zones', ['key' => 'is_ilani_detay_alt', 'is_active' => false]);
        $this->assertDatabaseHas('zones', ['key' => 'footer_ust_serit', 'is_active' => false]);
    }

    public function test_zone_seeder_does_not_overwrite_admin_edits(): void
    {
        $this->seed(ZoneSeeder::class);

        Zone::where('key', 'anasayfa_ust')->first()->update(['is_active' => false]);

        $this->seed(ZoneSeeder::class);

        $this->assertDatabaseHas('zones', ['key' => 'anasayfa_ust', 'is_active' => false]);
    }

    public function test_inactive_zone_renders_nothing(): void
    {
        Zone::create([
            'key' => 'test_zone',
            'name' => 'Test alanı',
            'is_active' => false,
            'blocks' => [['type' => 'metin', 'data' => ['content' => '<p>Görünmemeli</p>']]],
        ]);

        $html = (string) view('components.zone', ['zoneKey' => 'test_zone'])->render();

        $this->assertStringNotContainsString('Görünmemeli', $html);
    }

    public function test_missing_zone_renders_nothing(): void
    {
        $html = (string) view('components.zone', ['zoneKey' => 'olmayan_alan'])->render();

        $this->assertSame('', trim($html));
    }

    public function test_active_zone_renders_content_block(): void
    {
        Zone::create([
            'key' => 'test_zone',
            'name' => 'Test alanı',
            'is_active' => true,
            'blocks' => [['type' => 'metin', 'data' => ['content' => '<p>Görünmeli içerik</p>']]],
        ]);

        $html = (string) view('components.zone', ['zoneKey' => 'test_zone'])->render();

        $this->assertStringContainsString('Görünmeli içerik', $html);
    }

    public function test_active_zone_renders_ad_block(): void
    {
        // Yerel .env'deki ADSENSE_ENABLED değerinden bağımsız, deterministik olsun diye açıkça kapatılır.
        config(['services.adsense.enabled' => false]);

        Zone::create([
            'key' => 'test_zone',
            'name' => 'Test alanı',
            'is_active' => true,
            'blocks' => [['type' => 'reklam', 'data' => ['slot_id' => '999', 'format' => 'horizontal']]],
        ]);

        $html = (string) view('components.zone', ['zoneKey' => 'test_zone'])->render();

        // AdSense kapalıyken bağış çağrısı fallback'i görünür.
        $this->assertStringContainsString('Nisoya ücretsiz kalır', $html);
    }

    public function test_active_zone_ad_block_carries_slot_id_through_to_adsense_markup(): void
    {
        config(['services.adsense.enabled' => true, 'services.adsense.publisher_id' => 'ca-pub-123']);

        Zone::create([
            'key' => 'test_zone',
            'name' => 'Test alanı',
            'is_active' => true,
            'blocks' => [['type' => 'reklam', 'data' => ['slot_id' => '999', 'format' => 'horizontal']]],
        ]);

        $html = (string) view('components.zone', ['zoneKey' => 'test_zone'])->render();

        // <x-ad-slot :slot="..."> Blade'in rezerve slot değişkeniyle çakışıp bunu
        // boş render ediyordu (bkz. ad-slot.blade.php); regresyonu burada sabitliyoruz.
        $this->assertStringContainsString('data-ad-slot="999"', $html);
        $this->assertStringContainsString('data-ad-client="ca-pub-123"', $html);
    }

    public function test_zone_scheduling_window(): void
    {
        $future = Zone::create([
            'key' => 'test_future',
            'name' => 'Test alanı',
            'is_active' => true,
            'starts_at' => now()->addDay(),
            'blocks' => [['type' => 'metin', 'data' => ['content' => '<p>Gelecek</p>']]],
        ]);
        $past = Zone::create([
            'key' => 'test_past',
            'name' => 'Test alanı',
            'is_active' => true,
            'ends_at' => now()->subDay(),
            'blocks' => [['type' => 'metin', 'data' => ['content' => '<p>Geçmiş</p>']]],
        ]);
        $current = Zone::create([
            'key' => 'test_current',
            'name' => 'Test alanı',
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'blocks' => [['type' => 'metin', 'data' => ['content' => '<p>Şimdi</p>']]],
        ]);

        $this->assertFalse($future->isCurrentlyActive());
        $this->assertFalse($past->isCurrentlyActive());
        $this->assertTrue($current->isCurrentlyActive());
    }

    public function test_admin_can_open_and_edit_zone(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $zone = Zone::create(['key' => 'test_zone', 'name' => 'Test alanı', 'is_active' => false]);

        $this->actingAs($admin)->get('/yonetim/zones')->assertOk();
        $this->actingAs($admin)->get("/yonetim/zones/{$zone->id}/edit")->assertOk();
    }

    public function test_member_cannot_access_zone_admin(): void
    {
        $member = User::factory()->create([
            'role' => UserRole::Uye,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($member)->get('/yonetim/zones')->assertForbidden();
    }
}
