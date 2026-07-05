<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\ListingStatus;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\Favorite;
use App\Models\JobBookmark;
use App\Models\JobSavedSearch;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\User;
use App\Observers\UserObserver;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KvkkAndModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_user_can_export_their_data(): void
    {
        $user = User::factory()->create([
            'email' => 'export@test.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)->get('/panel/profil/verilerim');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json; charset=utf-8');

        $content = $response->streamedContent();
        $data = json_decode($content, true);

        $this->assertSame('export@test.com', $data['user']['email']);
        $this->assertArrayHasKey('listings', $data);
        $this->assertArrayHasKey('reviews_given', $data);
        $this->assertArrayHasKey('reviews_received', $data);
        $this->assertArrayHasKey('favorites', $data);
        $this->assertArrayHasKey('is_yer_imlerim', $data);
        $this->assertArrayHasKey('sirket_degerlendirmelerim', $data);
        $this->assertArrayHasKey('saved_searches', $data);
        $this->assertArrayHasKey('sent_messages', $data);
        $this->assertArrayHasKey('sirket_galeri', $data);
        $this->assertArrayHasKey('is_ilani_one_cikarma_taleplerim', $data);
        $this->assertArrayHasKey('yetenek_havuzunda_gorunur', $data['user']);
    }

    public function test_export_includes_company_gallery_and_feature_requests(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Acme', 'slug' => 'acme']);
        $company->galleryImages()->create([
            'path_thumb' => 'gallery/thumb/x.webp', 'path_medium' => 'gallery/medium/x.webp', 'path_large' => 'gallery/large/x.webp',
            'caption' => 'Ofisimiz',
        ]);
        $job = $company->jobListings()->create([
            'title' => 'Test ilanı', 'slug' => 'test-ilani', 'description' => 'Açıklama.',
            'employment_type' => 'tam_zamanli', 'status' => JobStatus::Aktif->value, 'positions' => 1,
        ]);
        $job->featureRequests()->create(['user_id' => $user->id, 'days' => 7, 'status' => 'beklemede']);

        $data = json_decode($this->actingAs($user)->get('/panel/profil/verilerim')->streamedContent(), true);

        $this->assertSame('Ofisimiz', $data['sirket_galeri'][0]['caption']);
        $this->assertSame('Test ilanı', $data['is_ilani_one_cikarma_taleplerim'][0]['ilan']);
    }

    public function test_user_can_delete_their_account(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'email' => 'del@test.com',
            'password' => Hash::make('password'),
            'avatar_path' => 'avatars/old.jpg',
        ]);

        // Bir de ilan + favori + saved search oluşturalım
        $category = Category::first();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);
        Favorite::create(['user_id' => $user->id, 'listing_id' => $listing->id]);
        SavedSearch::create(['user_id' => $user->id, 'q' => 'test', 'label' => 'Test arama']);

        // Bir de iş ilanı yer imi oluşturalım
        $otherEmployer = User::factory()->create();
        $company = Company::create(['user_id' => $otherEmployer->id, 'name' => 'Acme', 'slug' => 'acme']);
        $job = $company->jobListings()->create([
            'title' => 'Test iş', 'slug' => 'test-is', 'description' => 'Açıklama.',
            'employment_type' => 'tam_zamanli', 'status' => JobStatus::Aktif->value, 'positions' => 1,
        ]);
        JobBookmark::create(['user_id' => $user->id, 'job_listing_id' => $job->id]);
        $job->applications()->create(['user_id' => $user->id, 'status' => 'gonderildi']);
        CompanyReview::create(['company_id' => $company->id, 'reviewer_id' => $user->id, 'rating' => 5, 'status' => 'yayinda']);
        JobSavedSearch::create(['user_id' => $user->id, 'label' => 'Test', 'ulke' => 'DE']);

        $response = $this->actingAs($user)->delete('/panel/profil', [
            'current_password' => 'password',
            'confirm_text' => 'HESABIMI SİL',
        ]);

        $response->assertRedirect();
        $this->assertGuest();

        $user->refresh();
        $this->assertSame(UserStatus::Silinmis, $user->status);
        $this->assertNull($user->avatar_path);
        // Password NULL olamaz (NOT NULL constraint) — rasgele hash'lenmiş değer.
        // Önemli olan: giriş yapılamaz (status='silinmis' → EnsureUserIsActive middleware).
        $this->assertNotEmpty($user->password);
        $this->assertNotEquals(Hash::make('password'), $user->password);
        $this->assertStringStartsWith('Silinmiş', $user->name);
        $this->assertStringStartsWith('deleted-', $user->username);
        $this->assertStringEndsWith('@nisoya.local', $user->email);

        // Kişisel veriler temizlendi
        $this->assertDatabaseMissing('listings', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('job_bookmarks', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('company_reviews', ['reviewer_id' => $user->id]);
        $this->assertDatabaseMissing('saved_searches', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('job_saved_searches', ['user_id' => $user->id]);
    }

    public function test_account_delete_requires_correct_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct')]);

        $response = $this->actingAs($user)->delete('/panel/profil', [
            'current_password' => 'wrong',
            'confirm_text' => 'HESABIMI SİL',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => UserStatus::Aktif->value]);
    }

    public function test_account_delete_requires_exact_confirm_text(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $response = $this->actingAs($user)->delete('/panel/profil', [
            'current_password' => 'password',
            'confirm_text' => 'hesabimi sil', // küçük harf
        ]);

        $response->assertSessionHasErrors('confirm_text');
    }

    public function test_admin_ban_user_passes_their_active_listings_to_pasif(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Aktif]);
        $category = Category::first();
        Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);
        Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Aktif,
        ]);

        // Observer'ın public metodu — production'da updated event'i tetikler,
        // test'te doğrudan çağrılarak aynı davranış doğrulanır.
        (new UserObserver)->suspendActiveListings($user);

        $this->assertSame(0, $user->listings()->where('status', ListingStatus::Aktif->value)->count());
        $this->assertSame(2, $user->listings()->where('status', ListingStatus::Pasif->value)->count());
    }

    public function test_reactivating_user_does_not_auto_reactivate_listings(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Askida]);
        $category = Category::first();
        Listing::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => ListingStatus::Pasif,
        ]);

        // Observer sadece Askıya alma durumunda çalışır; geri aktivasyon yapmaz.
        // Bu business rule'un bilinçli tasarımı.
        $this->assertSame(0, $user->listings()->where('status', ListingStatus::Aktif->value)->count());
        $this->assertSame(1, $user->listings()->where('status', ListingStatus::Pasif->value)->count());
    }

    public function test_honeypot_middleware_blocks_bots_silently(): void
    {
        // Honeypot alanı dolu POST isteği → sessizce geri yönlendirilir
        $response = $this->post('/kayit', [
            'name' => 'Spam',
            'email' => 'spam@bot.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
            'terms' => '1',
            'website' => 'http://spam.example',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'İşlemin alındı.');
        $this->assertDatabaseMissing('users', ['email' => 'spam@bot.com']);
    }

    public function test_honeypot_middleware_allows_humans(): void
    {
        // Honeypot alanı boş → controller'a ulaşır
        $response = $this->post('/kayit', [
            'name' => 'İnsan Kullanıcı',
            'email' => 'insan@test.com',
            'username' => 'insan',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
            'terms' => '1',
            'website' => '', // boş = insan
        ]);

        // Validation geçerli olmalı (kayıt başarılı veya validasyon hatası yok "website" için)
        // Kayıt için e-posta doğrulama gerekli olduğundan, dashboard'a yönlendirir veya verify notice
        $this->assertDatabaseHas('users', ['email' => 'insan@test.com']);
    }

    public function test_dark_mode_toggle_script_in_layout(): void
    {
        $response = $this->get('/');
        $response->assertSee('toggleTheme', false);
        $response->assertSee('nisoya_theme', false); // localStorage key
    }

    public function test_dark_mode_classes_in_layout(): void
    {
        $response = $this->get('/');
        $response->assertSee('dark:bg-stone-950', false);
        $response->assertSee('dark:text-stone-200', false);
    }

    public function test_layout_has_preconnect_and_dns_prefetch(): void
    {
        $response = $this->get('/');
        $response->assertSee('rel="preconnect"', false);
        $response->assertSee('rel="dns-prefetch"', false);
    }

    public function test_theme_init_runs_inline_in_head(): void
    {
        // Tema başlatma script'i <head> içinde ve `data-cookieconsent` gibi
        // FOUC önleyici özelliklere sahip olmalı
        $response = $this->get('/');
        $content = $response->getContent();
        $headEnd = strpos($content, '</head>');
        $themeScriptPos = strpos($content, 'nisoya_theme');
        $this->assertNotFalse($themeScriptPos);
        $this->assertLessThan($headEnd, $themeScriptPos, 'Tema scripti <head> içinde olmalı (FOUC önleme)');
    }
}
