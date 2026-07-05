<?php

namespace Tests\Feature;

use App\Http\Controllers\CompanyGalleryController;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_new_profile_fields(): void
    {
        $user = User::factory()->create();
        Company::create(['user_id' => $user->id, 'name' => 'Acme', 'slug' => 'acme']);

        $this->actingAs($user)->put('/panel/sirket', [
            'name' => 'Acme',
            'video_url' => 'https://youtube.com/watch?v=abc',
            'address' => 'Musterstraße 1, Berlin',
            'social_whatsapp' => 'https://wa.me/491234567',
            'social_twitter' => 'https://x.com/acme',
        ])->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'user_id' => $user->id,
            'video_url' => 'https://youtube.com/watch?v=abc',
            'address' => 'Musterstraße 1, Berlin',
            'social_whatsapp' => 'https://wa.me/491234567',
            'social_twitter' => 'https://x.com/acme',
        ]);
    }

    public function test_public_company_page_shows_new_fields(): void
    {
        $user = User::factory()->create();
        Company::create([
            'user_id' => $user->id, 'name' => 'Acme', 'slug' => 'acme',
            'address' => 'Musterstraße 1, Berlin', 'video_url' => 'https://youtube.com/watch?v=abc',
        ]);

        $this->get('/sirket/acme')
            ->assertOk()
            ->assertSee('Musterstraße 1, Berlin')
            ->assertSee('Tanıtım videosu');
    }

    public function test_owner_can_upload_gallery_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Company::create(['user_id' => $user->id, 'name' => 'Acme', 'slug' => 'acme']);

        $this->actingAs($user)->post('/panel/sirket/galeri', [
            'image' => UploadedFile::fake()->image('office.jpg', 800, 600),
            'caption' => 'Ofisimiz',
        ])->assertRedirect();

        $this->assertDatabaseHas('company_gallery_images', ['caption' => 'Ofisimiz']);
    }

    public function test_gallery_upload_respects_max_items_limit(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Acme', 'slug' => 'acme']);

        for ($i = 0; $i < CompanyGalleryController::MAX_ITEMS; $i++) {
            $company->galleryImages()->create([
                'path_thumb' => "gallery/thumb/{$i}.webp", 'path_medium' => "gallery/medium/{$i}.webp", 'path_large' => "gallery/large/{$i}.webp",
                'sort_order' => $i,
            ]);
        }

        $this->actingAs($user)->post('/panel/sirket/galeri', [
            'image' => UploadedFile::fake()->image('extra.jpg'),
        ])->assertSessionHasErrors('image');

        $this->assertSame(CompanyGalleryController::MAX_ITEMS, $company->galleryImages()->count());
    }

    public function test_only_owner_can_delete_gallery_image(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $company = Company::create(['user_id' => $owner->id, 'name' => 'Acme', 'slug' => 'acme']);
        Storage::disk('public')->put('gallery/large/x.webp', 'fake');
        $image = $company->galleryImages()->create([
            'path_thumb' => 'gallery/thumb/x.webp', 'path_medium' => 'gallery/medium/x.webp', 'path_large' => 'gallery/large/x.webp',
        ]);

        $this->actingAs(User::factory()->create())
            ->delete("/panel/sirket/galeri/{$image->id}")
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete("/panel/sirket/galeri/{$image->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('company_gallery_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing('gallery/large/x.webp');
    }

    public function test_account_deletion_removes_gallery_images(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $company = Company::create(['user_id' => $user->id, 'name' => 'Acme', 'slug' => 'acme']);
        Storage::disk('public')->put('gallery/large/y.webp', 'fake');
        $company->galleryImages()->create([
            'path_thumb' => 'gallery/thumb/y.webp', 'path_medium' => 'gallery/medium/y.webp', 'path_large' => 'gallery/large/y.webp',
        ]);

        $this->actingAs($user)->delete('/panel/profil', [
            'current_password' => 'password',
            'confirm_text' => 'HESABIMI SİL',
        ])->assertRedirect();

        $this->assertDatabaseMissing('company_gallery_images', ['company_id' => $company->id]);
        Storage::disk('public')->assertMissing('gallery/large/y.webp');
    }
}
