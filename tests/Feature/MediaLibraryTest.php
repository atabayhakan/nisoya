<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\MedyaKutuphanesi;
use App\Models\User;
use App\Support\MediaLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Faz 3 · G9 — medya kütüphanesi: disk kullanımı, dosya listeleme, güvenli silme.
 */
class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = storage_path('app/public/testmedya');
        File::ensureDirectoryExists($this->testDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testDir);
        parent::tearDown();
    }

    private function makeFile(string $name, string $content = 'x'): void
    {
        $path = $this->testDir.'/'.$name;
        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, $content);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    // ----------------------------------------------------------- Çekirdek

    public function test_usage_counts_files_and_size(): void
    {
        $this->makeFile('a.jpg', str_repeat('x', 100));
        $this->makeFile('alt/b.png', str_repeat('y', 50));

        $usage = MediaLibrary::usage();

        $this->assertArrayHasKey('testmedya', $usage['dirs']);
        $this->assertSame(2, $usage['dirs']['testmedya']['count']);
        $this->assertSame(150, $usage['dirs']['testmedya']['size']);
    }

    public function test_files_lists_and_paginates(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->makeFile("f{$i}.jpg");
        }

        $result = MediaLibrary::files('testmedya', 1, 2);

        $this->assertSame(3, $result['total']);
        $this->assertSame(2, $result['pages']);
        $this->assertCount(2, $result['items']);
    }

    public function test_delete_removes_file(): void
    {
        $this->makeFile('del.jpg');

        $this->assertTrue(MediaLibrary::delete('testmedya/del.jpg'));
        $this->assertFileDoesNotExist($this->testDir.'/del.jpg');
    }

    public function test_delete_rejects_traversal_and_missing(): void
    {
        $this->assertFalse(MediaLibrary::delete('../../.env'));
        $this->assertFalse(MediaLibrary::delete('../../../etc/passwd'));
        $this->assertFalse(MediaLibrary::delete('testmedya/yok.jpg'));
    }

    public function test_is_image_detection(): void
    {
        $this->assertTrue(MediaLibrary::isImage('a/b.JPG'));
        $this->assertTrue(MediaLibrary::isImage('x.webp'));
        $this->assertFalse(MediaLibrary::isImage('a/b.pdf'));
    }

    // --------------------------------------------------------------- Sayfa

    public function test_admin_can_view_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/yonetim/medya-kutuphanesi')
            ->assertOk()
            ->assertSee('Medya Kütüphanesi');
    }

    public function test_member_redirected(): void
    {
        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($member)
            ->get('/yonetim/medya-kutuphanesi')
            ->assertRedirect(route('dashboard'));
    }

    public function test_moderator_forbidden(): void
    {
        $mod = User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);

        $this->actingAs($mod)
            ->get('/yonetim/medya-kutuphanesi')
            ->assertForbidden();
    }

    public function test_delete_file_via_livewire(): void
    {
        $this->makeFile('livewire-del.jpg');

        Livewire::actingAs($this->admin())
            ->test(MedyaKutuphanesi::class)
            ->call('deleteFile', 'testmedya/livewire-del.jpg');

        $this->assertFileDoesNotExist($this->testDir.'/livewire-del.jpg');
    }

    public function test_type_breakdown_calculates_sizes_and_percentages(): void
    {
        $this->makeFile('photo.jpg', str_repeat('a', 100));
        $this->makeFile('clip.mp4', str_repeat('b', 200));

        $breakdown = MediaLibrary::typeBreakdown();

        $this->assertGreaterThanOrEqual(100, $breakdown['images']['size']);
        $this->assertGreaterThanOrEqual(200, $breakdown['videos']['size']);
        $this->assertGreaterThanOrEqual(300, $breakdown['total_size']);
    }

    public function test_create_directory_creates_safe_folder(): void
    {
        $this->assertTrue(MediaLibrary::createDirectory('tanitim-videolari'));
        $this->assertDirectoryExists(storage_path('app/public/tanitim-videolari'));

        File::deleteDirectory(storage_path('app/public/tanitim-videolari'));
    }

    public function test_create_folder_action_via_livewire(): void
    {
        Livewire::actingAs($this->admin())
            ->test(MedyaKutuphanesi::class)
            ->set('newFolderName', 'yeni-klasor')
            ->call('createFolder');

        $this->assertDirectoryExists(storage_path('app/public/yeni-klasor'));

        File::deleteDirectory(storage_path('app/public/yeni-klasor'));
    }
}
