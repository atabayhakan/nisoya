<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Kurtarma Kiti — sahibin panele kilitlenmemesi için üç güvence (Faz 1 · G2):
 *   1) En az iki yönetici (biri kilitlenirse diğeri erişir) — sayaç + uyarı.
 *   2) Hesap kurtarma kodları — e-posta (SMTP) çalışmasa bile parola sıfırlama
 *      (genel akış: /hesap-kurtar). Kodlar burada üretilir, bir kez gösterilir.
 *   3) "Cam kır" son çare — sunucuda `php artisan admin:recover`.
 *
 * Yalnızca Admin erişebilir (RestrictsToAdmins).
 */
class KurtarmaKiti extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'Kurtarma Kiti';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.kurtarma-kiti';

    /** Yeni üretilen kodlar — yalnızca bu istek boyunca gösterilir, saklanmaz. */
    public array $generatedCodes = [];

    public function getTitle(): string
    {
        return 'Kurtarma Kiti';
    }

    /** Aktif yönetici sayısı (kilitlenme riskini gösterir). */
    public function adminCount(): int
    {
        return User::query()
            ->where('role', UserRole::Admin)
            ->where('status', UserStatus::Aktif)
            ->count();
    }

    /** Giriş yapan yöneticinin kalan kurtarma kodu sayısı. */
    public function remainingCodes(): int
    {
        return auth()->user()?->accountRecoveryCodesRemaining() ?? 0;
    }

    /** Yeni yönetici ekleme (ikinci admin) formunun URL'i. */
    public function createUserUrl(): string
    {
        return UserResource::getUrl('create');
    }

    /** Kurtarma kodları üret (eskiler geçersiz olur); bir kez göster. */
    public function generateCodes(): void
    {
        $this->generatedCodes = auth()->user()->generateAccountRecoveryCodes();

        Notification::make()
            ->title('Kurtarma kodları oluşturuldu')
            ->body('Kodları güvenli bir yere kaydet — bu ekrandan çıkınca bir daha gösterilmeyecek.')
            ->success()
            ->persistent()
            ->send();
    }
}
