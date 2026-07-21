<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Filament\Pages\MedyaKutuphanesi;
use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 2026-07-22 kod denetimi kritik düzeltmelerinin regresyon testleri.
 */
class SecurityFixesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Moderasyon atlatma (#2): admin tarafından gizlenmiş bir değerlendirme,
     * yorumu yazan tarafından yeniden gönderilerek otomatik yayına alınamaz.
     */
    public function test_hidden_company_review_cannot_be_republished_by_resubmission(): void
    {
        $employer = User::factory()->create();
        $company = Company::create(['user_id' => $employer->id, 'name' => 'Acme', 'slug' => 'acme']);
        $job = $company->jobListings()->create([
            'title' => 'Test iş', 'slug' => 'test-is', 'description' => 'Açıklama metni.',
            'employment_type' => 'tam_zamanli', 'status' => JobStatus::Aktif->value, 'positions' => 1,
        ]);

        $candidate = User::factory()->create();
        $job->applications()->create(['user_id' => $candidate->id, 'status' => 'gonderildi']);

        // Admin bu değerlendirmeyi gizlemiş.
        $review = CompanyReview::create([
            'company_id' => $company->id,
            'reviewer_id' => $candidate->id,
            'rating' => 1,
            'comment' => 'Orijinal yorum',
            'status' => 'gizli',
        ]);

        // Aday aynı değerlendirmeyi yeniden gönderiyor.
        $response = $this->actingAs($candidate)->post(route('company-reviews.store', $company), [
            'rating' => 1,
            'comment' => 'Yeniden gonderilen yorum',
            'website' => '', // honeypot boş = insan
        ]);

        $response->assertSessionHasErrors('comment');

        $review->refresh();
        $this->assertSame(ReviewStatus::Gizli, $review->status, 'Gizli değerlendirme yeniden yayına alınmamalı');
        $this->assertSame('Orijinal yorum', $review->comment, 'Gizli değerlendirmenin içeriği değişmemeli');
    }

    /**
     * Kimlik doğrulamalı olsa bile yürütülebilir dosya (RCE, #1) public diske
     * yazılmaz; izin verilen görsel dosyası yazılır.
     */
    public function test_media_upload_rejects_executable_files(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        Livewire::actingAs($admin)
            ->test(MedyaKutuphanesi::class)
            ->set('dir', 'uploads')
            ->set('newFiles', [
                UploadedFile::fake()->create('shell.php', 8, 'application/x-httpd-php'),
                UploadedFile::fake()->image('foto.jpg'),
            ])
            ->call('uploadFiles');

        Storage::disk('public')->assertMissing('uploads/shell.php');
        Storage::disk('public')->assertExists('uploads/foto.jpg');
    }
}
