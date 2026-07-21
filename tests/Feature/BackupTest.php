<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Yedekleme;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

/**
 * Faz 1 · G1 — sürücü-farkında yedekleme (SQLite yolu yerelde test edilebilir;
 * MySQL yolu sunucuda mysqldump ile çalışır) + admin-only erişim/indirme.
 */
class BackupTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Test sırasında üretilen yedekleri/geçici dökümleri temizle.
        foreach (glob(storage_path('app/backups').'/*') ?: [] as $path) {
            @unlink($path);
        }
        File::deleteDirectory(storage_path('app/public/test-marka'));

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    public function test_create_produces_valid_zip_with_db_dump_and_manifest(): void
    {
        $name = app(BackupService::class)->create();
        $path = storage_path('app/backups/'.$name);

        $this->assertFileExists($path);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'Yedek arşivi açılamadı');

        $this->assertNotFalse($zip->locateName('manifest.json'), 'manifest.json yok');

        $hasDbDump = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_starts_with((string) $zip->getNameIndex($i), 'database/')) {
                $hasDbDump = true;
                break;
            }
        }
        $this->assertTrue($hasDbDump, 'Arşivde veritabanı dökümü yok');

        $zip->close();
    }

    public function test_uploaded_media_is_included_in_backup(): void
    {
        File::ensureDirectoryExists(storage_path('app/public/test-marka'));
        file_put_contents(storage_path('app/public/test-marka/logo.txt'), 'nisoya');

        $name = app(BackupService::class)->create();

        $zip = new ZipArchive;
        $zip->open(storage_path('app/backups/'.$name));
        $this->assertNotFalse($zip->locateName('media/test-marka/logo.txt'), 'Medya dosyası arşivde yok');
        $zip->close();
    }

    public function test_list_and_delete_work(): void
    {
        $service = app(BackupService::class);
        $name = $service->create();

        $this->assertContains($name, array_column($service->list(), 'name'));

        $this->assertTrue($service->delete($name));
        $this->assertNotContains($name, array_column($service->list(), 'name'));
    }

    public function test_path_rejects_traversal_and_non_zip(): void
    {
        $service = app(BackupService::class);

        $this->assertNull($service->path('../../.env'));
        $this->assertNull($service->path('notes.txt'));
        $this->assertNull($service->path('yok.zip')); // mevcut değil
    }

    public function test_prune_removes_old_but_keeps_latest(): void
    {
        $service = app(BackupService::class);
        $newest = $service->create();

        // Yapay eski yedek (30 gün önce)
        $oldPath = storage_path('app/backups/nisoya-yedek-2000-01-01_000000.zip');
        copy(storage_path('app/backups/'.$newest), $oldPath);
        touch($oldPath, now()->subDays(30)->getTimestamp());

        $removed = $service->prune(7);

        $this->assertSame(1, $removed);
        $this->assertFileDoesNotExist($oldPath);
        $this->assertFileExists(storage_path('app/backups/'.$newest));
    }

    public function test_admin_can_view_backup_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/yonetim/yedekleme')
            ->assertOk()
            ->assertSee('Yedekleme');
    }

    public function test_member_is_redirected_from_backup_page(): void
    {
        $user = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/yonetim/yedekleme')
            ->assertRedirect(route('dashboard'));
    }

    public function test_moderator_cannot_view_backup_page(): void
    {
        // Moderatör panele girebilir ama yedekleme yalnızca Admin'e açık.
        $mod = User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);

        $this->actingAs($mod)
            ->get('/yonetim/yedekleme')
            ->assertForbidden();
    }

    public function test_create_backup_action_creates_a_backup(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(Yedekleme::class)->call('createBackup');

        $this->assertNotEmpty(app(BackupService::class)->list());
    }

    public function test_admin_can_download_backup(): void
    {
        $admin = $this->admin();
        $name = app(BackupService::class)->create();

        $this->actingAs($admin)
            ->get(route('admin.backup.download', ['name' => $name]))
            ->assertOk();
    }

    public function test_non_admin_cannot_download_backup(): void
    {
        $name = app(BackupService::class)->create();
        $user = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        // Yetkisiz üye /yonetim* yollarında (bir API ucu olmadığı için) çıplak 403
        // yerine dostça kendi paneline yönlendirilir — bkz. bootstrap/app.php.
        // Dosyayı yine de indiremez.
        $this->actingAs($user)
            ->get(route('admin.backup.download', ['name' => $name]))
            ->assertRedirect(route('dashboard'));
    }

    public function test_download_returns_404_for_unknown_file(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.backup.download', ['name' => 'yok.zip']))
            ->assertNotFound();
    }
}
