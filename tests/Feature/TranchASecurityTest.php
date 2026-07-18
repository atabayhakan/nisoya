<?php

namespace Tests\Feature;

use App\Enums\EventType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\IcerikAyarlari;
use App\Filament\Pages\YapayZekaAyarlari;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyGalleryImage;
use App\Models\Event;
use App\Models\EventMedia;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tranş A güvenlik & doğruluk düzeltmeleri regresyon testleri
 * (harici kod incelemesi bulguları — bkz. .claude/plans).
 */
class TranchASecurityTest extends TestCase
{
    use RefreshDatabase;

    private function seedRefs(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function makeListing(User $owner): Listing
    {
        return Listing::factory()->create([
            'user_id' => $owner->id,
            'category_id' => Category::first()->id,
        ]);
    }

    // ---- #2 + #3: Admin süper-kullanıcı + moderatör içerik moderasyonu ----

    public function test_admin_can_update_and_delete_others_listing_via_gate(): void
    {
        $this->seedRefs();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $owner = User::factory()->create(['role' => UserRole::Uye]);
        $listing = $this->makeListing($owner);

        $this->assertTrue(Gate::forUser($admin)->allows('update', $listing));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $listing));
    }

    public function test_moderator_can_moderate_others_listing_but_member_cannot(): void
    {
        $this->seedRefs();
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $stranger = User::factory()->create(['role' => UserRole::Uye]);
        $owner = User::factory()->create(['role' => UserRole::Uye]);
        $listing = $this->makeListing($owner);

        $this->assertTrue(Gate::forUser($moderator)->allows('update', $listing));
        $this->assertTrue(Gate::forUser($moderator)->allows('delete', $listing));
        // Sıradan üye başkasının ilanını düzenleyemez.
        $this->assertFalse(Gate::forUser($stranger)->allows('update', $listing));
    }

    public function test_admin_can_open_others_listing_edit_page_in_panel(): void
    {
        $this->seedRefs();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
        $owner = User::factory()->create(['role' => UserRole::Uye]);
        $listing = $this->makeListing($owner);

        // Bug #2: sahiplik-temelli policy yüzünden admin başkasının ilanını
        // panelde 403 alıyordu. Gate::before ile artık düzenleyebilir.
        $this->actingAs($admin)->get("/yonetim/listings/{$listing->id}/edit")->assertOk();
    }

    // ---- #3: AI anahtarı + ham kod enjeksiyonu sayfaları yalnızca Admin ----

    public function test_moderator_cannot_access_ai_settings_page(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        $this->actingAs($moderator);
        $this->assertFalse(YapayZekaAyarlari::canAccess());
        $this->actingAs($moderator)->get('/yonetim/yapay-zeka-ayarlari')->assertForbidden();

        $this->actingAs($admin);
        $this->assertTrue(YapayZekaAyarlari::canAccess());
    }

    public function test_moderator_cannot_access_content_code_injection_page(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        $this->actingAs($moderator);
        $this->assertFalse(IcerikAyarlari::canAccess());
        $this->actingAs($moderator)->get('/yonetim/icerik-ayarlari')->assertForbidden();

        $this->actingAs($admin);
        $this->assertTrue(IcerikAyarlari::canAccess());
    }

    public function test_user_role_helpers(): void
    {
        $this->assertTrue(User::factory()->create(['role' => UserRole::Admin])->isAdmin());
        $this->assertFalse(User::factory()->create(['role' => UserRole::Moderator])->isAdmin());
        $this->assertTrue(User::factory()->create(['role' => UserRole::Moderator])->isModerator());
        $this->assertFalse(User::factory()->create(['role' => UserRole::Admin])->isModerator());
    }

    // ---- M1: Askıya alınmış kullanıcı korumalı route'ta oturumdan atılır ----

    public function test_suspended_user_is_logged_out_on_protected_route(): void
    {
        $suspended = User::factory()->create([
            'status' => UserStatus::Askida,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($suspended)->get('/panel')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_active_user_reaches_protected_route(): void
    {
        $active = User::factory()->create([
            'status' => UserStatus::Aktif,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($active)->get('/panel')->assertOk();
    }

    // ---- #7: Şifre sıfırlama account enumeration sızdırmaz ----

    public function test_password_reset_does_not_leak_account_existence(): void
    {
        $user = User::factory()->create(['email' => 'var@nisoya.test']);

        $known = $this->post('/sifremi-unuttum', ['email' => 'var@nisoya.test']);
        $known->assertSessionHasNoErrors();
        $known->assertSessionHas('status');

        $unknown = $this->post('/sifremi-unuttum', ['email' => 'yok@nisoya.test']);
        // Kayıtlı olmayan e-posta için de HATA değil, AYNI nötr status mesajı.
        $unknown->assertSessionHasNoErrors();
        $unknown->assertSessionHas('status', session('status'));
        $this->assertSame(
            $known->getSession()->get('status'),
            $unknown->getSession()->get('status'),
        );
    }

    // ---- #4: JSON-LD XSS kaçışı ----

    public function test_json_ld_escapes_script_tag_injection(): void
    {
        $html = view('components.json-ld', [
            'type' => 'Product',
            'data' => ['name' => 'Kötü </script><script>alert(1)</script>'],
        ])->render();

        // Ham "</script>" enjeksiyonu çıktıda OLMAMALI; kaçışlı < olmalı.
        $this->assertStringNotContainsString('</script><script>', $html);
        $this->assertStringContainsString('<', $html);
    }

    // ---- #5: Silmede öksüz medya temizliği ----

    public function test_deleting_event_cleans_media_files_from_disk(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['status' => UserStatus::Aktif, 'email_verified_at' => now()]);

        $event = Event::create([
            'user_id' => $owner->id,
            'type' => EventType::cases()[0],
            'title' => 'Test Etkinlik',
            'starts_at' => now()->addWeek(),
        ]);

        Storage::disk('public')->put('event-media/foto.webp', 'x');
        Storage::disk('public')->put('event-media/foto_thumb.webp', 'x');
        EventMedia::create([
            'event_id' => $event->id,
            'type' => 'image',
            'status' => 'approved',
            'path' => 'event-media/foto.webp',
            'path_thumb' => 'event-media/foto_thumb.webp',
            'size_bytes' => 1,
        ]);

        $this->actingAs($owner)->delete(route('panel.events.destroy', $event))
            ->assertRedirect(route('panel.events.index'));

        Storage::disk('public')->assertMissing('event-media/foto.webp');
        Storage::disk('public')->assertMissing('event-media/foto_thumb.webp');
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        $this->assertDatabaseMissing('event_media', ['event_id' => $event->id]);
    }

    public function test_deleting_company_cleans_logo_and_gallery_files_from_disk(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();

        $company = Company::create([
            'user_id' => $owner->id,
            'name' => 'Test Şirket',
            'slug' => 'test-sirket',
            'logo_path' => 'company-logos/large/logo.webp',
        ]);

        Storage::disk('public')->put('company-logos/large/logo.webp', 'x');
        Storage::disk('public')->put('company-logos/thumb/logo.webp', 'x');
        Storage::disk('public')->put('company-gallery/g1.webp', 'x');

        CompanyGalleryImage::create([
            'company_id' => $company->id,
            'path_thumb' => 'company-gallery/g1.webp',
            'path_medium' => 'company-gallery/g1.webp',
            'path_large' => 'company-gallery/g1.webp',
        ]);

        $company->delete();

        Storage::disk('public')->assertMissing('company-logos/large/logo.webp');
        Storage::disk('public')->assertMissing('company-logos/thumb/logo.webp');
        Storage::disk('public')->assertMissing('company-gallery/g1.webp');
    }
}
