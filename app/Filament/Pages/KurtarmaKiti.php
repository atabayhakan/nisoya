<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Concerns\RehberYardimi;
use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\Ai\SystemToolsAiAssistant;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
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
    use RehberYardimi;
    use RestrictsToAdmins;

    /**
     * Bu ekranın rehber sayfasını slide-over'da açar. Sayfa yoksa düğme hiç
     * görünmez (bkz. RehberYardimi) — boş bir yardım penceresi, yardım
     * olmamasından kötüdür.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        $aiAction = Action::make('aiGuvenlikDenetimi')
            ->label('AI Güvenlik & Kurtarma Denetimi')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->color('primary')
            ->modalHeading('Sistem Felaket Kurtarma & Güvenlik Denetimi')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Kapat')
            ->modalContent(function (): View {
                $admins = $this->adminCount();
                $twoFa = $this->ikiFaktorluAdminCount();
                $remaining = $this->remainingCodes();
                $smtp = ! empty(Settings::get('mail.host') ?: config('mail.mailers.smtp.host'));
                $assistant = app(SystemToolsAiAssistant::class);
                $audit = $assistant->generateRecoveryAudit($admins, $twoFa, $remaining, $smtp);

                return view('filament.pages.partials.kurtarma-denetim-modal', [
                    'audit' => $audit,
                    'admins' => $admins,
                    'twoFa' => $twoFa,
                    'remaining' => $remaining,
                    'smtp' => $smtp,
                ]);
            });

        return array_values(array_filter([
            static::rehberYardimAksiyonu(),
            $aiAction,
        ]));
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'Kurtarma Kiti';

    protected static ?int $navigationSort = 9;

    public static function getNavigationBadge(): ?string
    {
        $admins = User::query()->where('role', UserRole::Admin)->where('status', UserStatus::Aktif)->count();
        $twoFa = User::query()->where('role', UserRole::Admin)->where('status', UserStatus::Aktif)->whereNotNull('two_factor_confirmed_at')->count();

        return ($admins < 2 || $twoFa < $admins) ? 'Risk' : 'Güvenli';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $admins = User::query()->where('role', UserRole::Admin)->where('status', UserStatus::Aktif)->count();
        $twoFa = User::query()->where('role', UserRole::Admin)->where('status', UserStatus::Aktif)->whereNotNull('two_factor_confirmed_at')->count();

        return ($admins < 2 || $twoFa < $admins) ? 'warning' : 'success';
    }

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

    /**
     * 2FA'sı AÇIK olan aktif yönetici sayısı.
     *
     * 2026-08-05'ten beri panel 2FA olmadan açılmıyor. Yani "iki yönetici var"
     * artık tek başına güvence DEĞİL: ikinci yöneticinin 2FA'sı kurulmamışsa,
     * birincisi kilitlendiği gün o da kurulum ekranına düşer ve panele
     * giremez. Bu sayaç, kağıt üzerindeki yedeği gerçek yedekten ayırır.
     */
    public function ikiFaktorluAdminCount(): int
    {
        return User::query()
            ->where('role', UserRole::Admin)
            ->where('status', UserStatus::Aktif)
            ->whereNotNull('two_factor_confirmed_at')
            ->count();
    }

    /** Giriş yapan yöneticinin kalan kurtarma kodu sayısı. */
    public function remainingCodes(): int
    {
        return auth()->user()?->accountRecoveryCodesRemaining() ?? 0;
    }

    /**
     * Kayıtlı kodlar okunabiliyor mu?
     *
     * false ise "0 kod" göstermek YANILTICIDIR: sahip "hiç üretmemişim"
     * sanar, oysa üretilmiş ama artık çözülemiyor. İkisi apayrı durum.
     */
    public function kodlarOkunabilirMi(): bool
    {
        return auth()->user()?->hesapKurtarmaKodlariOkunabilirMi() ?? true;
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
