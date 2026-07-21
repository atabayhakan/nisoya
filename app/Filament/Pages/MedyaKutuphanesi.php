<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Support\MediaLibrary;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\WithFileUploads;
use UnitEnum;

/**
 * Medya Kütüphanesi (Faz 3 · G9 & İleri Düzey Yönetim) — iOS tarzı canlı depolama
 * grafiği, dinamik klasör oluşturma, dosya yükleme, arama ve filtreleme.
 */
class MedyaKutuphanesi extends Page
{
    use RestrictsToAdmins;
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Medya Kütüphanesi';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.medya-kutuphanesi';

    public string $dir = '';

    public int $page = 1;

    public string $filterType = 'all'; // all, image, video, document

    public string $search = '';

    public string $newFolderName = '';

    /** @var array<int, mixed> */
    public $newFiles = [];

    public function mount(): void
    {
        $dirs = array_keys(MediaLibrary::usage()['dirs']);
        $this->dir = $dirs[0] ?? 'highlights';
    }

    public function selectDir(string $dir): void
    {
        $this->dir = $dir;
        $this->page = 1;
    }

    public function setFilterType(string $type): void
    {
        $this->filterType = $type;
        $this->page = 1;
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function prevPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function createFolder(): void
    {
        $name = trim($this->newFolderName);

        if ($name === '') {
            Notification::make()->title('Klasör adı boş olamaz')->warning()->send();

            return;
        }

        if (MediaLibrary::createDirectory($name)) {
            $cleanDir = \Illuminate\Support\Str::slug($name);
            $this->dir = $cleanDir;
            $this->newFolderName = '';
            Notification::make()->title("{$name} klasörü oluşturuldu")->success()->send();
        } else {
            Notification::make()->title('Klasör oluşturulamadı')->danger()->send();
        }
    }

    public function uploadFiles(): void
    {
        if (empty($this->newFiles)) {
            return;
        }

        if ($this->dir === '') {
            $this->dir = 'uploads';
            MediaLibrary::createDirectory('uploads');
        }

        $count = 0;
        foreach ($this->newFiles as $file) {
            $filename = $file->getClientOriginalName();
            $file->storeAs($this->dir, $filename, 'public');
            $count++;
        }

        $this->newFiles = [];
        $this->page = 1;
        Notification::make()->title("{$count} dosya yüklendi")->success()->send();
    }

    public function deleteFile(string $path): void
    {
        if (MediaLibrary::delete($path)) {
            Notification::make()->title('Dosya silindi')->success()->send();

            return;
        }

        Notification::make()->title('Dosya silinemedi')->danger()->send();
    }

    public function getTypeBreakdown(): array
    {
        return MediaLibrary::typeBreakdown();
    }

    public function getUsage(): array
    {
        return MediaLibrary::usage();
    }

    public function getFiles(): array
    {
        $files = MediaLibrary::files(
            $this->dir,
            $this->page,
            24,
            $this->filterType === 'all' ? null : $this->filterType,
            $this->search !== '' ? $this->search : null
        );

        $this->page = $files['page'];

        return $files;
    }

    public function freeSpace(): float
    {
        return (float) (@disk_free_space(MediaLibrary::root()) ?: 0);
    }
}
