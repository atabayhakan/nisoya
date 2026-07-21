<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Support\MediaLibrary;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Medya Kütüphanesi (Faz 3 · G9) — yüklenen dosyalara bakış: dizin başına disk
 * kullanımı, dosya ızgarası ve güvenli silme. Sahibin disk alanını görüp artık
 * kullanılmayan dosyaları temizleyebilmesi (bkz. App\Support\MediaLibrary).
 * Yalnızca Admin (silme tüm siteyi etkileyebilir).
 */
class MedyaKutuphanesi extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Medya Kütüphanesi';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.medya-kutuphanesi';

    public string $dir = '';

    public int $page = 1;

    public function mount(): void
    {
        // Varsayılan: en çok yer kaplayan dizini aç.
        $dirs = array_keys(MediaLibrary::usage()['dirs']);
        $this->dir = $dirs[0] ?? '';
    }

    public function selectDir(string $dir): void
    {
        $this->dir = $dir;
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

    public function deleteFile(string $path): void
    {
        if (MediaLibrary::delete($path)) {
            Notification::make()->title('Dosya silindi')->success()->send();

            return;
        }

        Notification::make()->title('Dosya silinemedi')->danger()->send();
    }

    /** @return array{dirs:array<string,array{count:int,size:int}>,total:array{count:int,size:int}} */
    public function getUsage(): array
    {
        return MediaLibrary::usage();
    }

    /** @return array{items:array<int,array<string,mixed>>,total:int,pages:int,page:int} */
    public function getFiles(): array
    {
        $files = MediaLibrary::files($this->dir, $this->page);
        // files() sayfa numarasını sınırlar; state'i onunla eşitle.
        $this->page = $files['page'];

        return $files;
    }

    /** Sunucudaki boş disk alanı (bayt). */
    public function freeSpace(): float
    {
        return (float) (@disk_free_space(MediaLibrary::root()) ?: 0);
    }
}
