<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Services\BackupService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use UnitEnum;

/**
 * Yedekleme & Geri Yükleme — site sahibinin geliştirici/harici servis olmadan
 * tek başına tam yedek alıp indirebildiği sayfa (bkz. Faz 1 · G1).
 *
 * Yedekleme mantığı BackupService'te; bu sayfa yalnızca ince bir kabuk:
 * "Şimdi Yedek Al", listeleme, silme ve rehberli geri yükleme bilgisi.
 * Yalnızca Admin erişebilir (RestrictsToAdmins) — yedekler tüm siteyi kapsar.
 */
class Yedekleme extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'Yedekleme';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.yedekleme';

    public bool $isWorking = false;

    public function getTitle(): string
    {
        return 'Yedekleme & Geri Yükleme';
    }

    /** "Şimdi Yedek Al" — tam yedek oluşturur. */
    public function createBackup(): void
    {
        $this->isWorking = true;

        try {
            $name = app(BackupService::class)->create();

            Notification::make()
                ->title('Yedek alındı')
                ->body($name)
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Yedekleme başarısız')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        } finally {
            $this->isWorking = false;
        }
    }

    /** Bir yedeği siler. */
    public function deleteBackup(string $name): void
    {
        if (app(BackupService::class)->delete($name)) {
            Notification::make()->title('Yedek silindi')->success()->send();

            return;
        }

        Notification::make()->title('Yedek bulunamadı')->danger()->send();
    }

    /**
     * Blade için mevcut yedekler (her render'da tazelenir → aksiyon sonrası güncel).
     *
     * @return array<int,array{name:string,size:int,created_at:Carbon}>
     */
    public function getBackups(): array
    {
        return app(BackupService::class)->list();
    }

    /** @return array{count:int,total_size:int,latest:?Carbon,free_space:float,driver:string} */
    public function getStats(): array
    {
        return app(BackupService::class)->stats();
    }
}
