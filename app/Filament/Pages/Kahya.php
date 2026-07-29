<?php

namespace App\Filament\Pages;

use App\Models\KahyaCalismasi;
use App\Services\Kahya\KahyaTeshisi;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use UnitEnum;

/**
 * Kâhya — yönetim ajanının ekranı.
 *
 * İKİ SORUYU YANITLAR:
 *   1. "Sitede şu an ne durumda?" — canlı teşhis (günlük raporun aynısı).
 *   2. "Kâhya çalışıyor mu?" — çalışma defteri.
 *
 * İKİNCİSİ SANILDIĞINDAN ÖNEMLİ. Rapor bir zamanlanmış komuttur ve
 * zamanlanmış komutlar sessizce ölür. Gelen kutusunda rapor olmaması ile
 * "her şey yolunda" ayırt edilemez. Bu sayfanın en üstündeki "son rapor N
 * saat önce" bandı, o ayrımı yapan tek yerdir.
 *
 * SAYFA CANLI TEŞHİS ÜRETİR (önbelleklenmiş rapor değil): sahip buraya
 * "şu an ne oluyor" diye bakar, "bu sabah ne oluyordu" diye değil. Maliyeti
 * kabul edilir — medya taraması limitli, log okuması satır satır.
 *
 * YALNIZ ADMIN: rapor log imzaları ve boş ayar anahtarları içeriyor;
 * moderatörün işi değil.
 */
class Kahya extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'Kâhya';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.kahya';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTitle(): string
    {
        return 'Kâhya';
    }

    public function getSubheading(): ?string
    {
        return 'Sitenin durumu, seni bekleyen işler ve bozuk olanlar.';
    }

    /**
     * Canlı teşhis.
     *
     * @return array<string, mixed>
     */
    public function getTeshis(): array
    {
        return app(KahyaTeshisi::class)->topla();
    }

    /** @return array<string, int> */
    public function getSonGun(): array
    {
        return app(KahyaTeshisi::class)->sonYirmiDortSaat();
    }

    /**
     * Son koşunun yaşı — `null` ise HİÇ koşmamış.
     *
     * null ile 0 arasındaki fark kritik: 0 "az önce koştu", null "hiç
     * koşmadı" demektir ve ikincisi bir arızadır.
     */
    public function getSonRaporYasi(): ?int
    {
        return KahyaCalismasi::sonKosuYasiSaat();
    }

    /** @return Collection<int, KahyaCalismasi> */
    public function getGecmis()
    {
        return KahyaCalismasi::query()->gunlukRapor()->latest('created_at')->limit(10)->get();
    }

    /**
     * "Şimdi örnek rapor gönder" — SMTP'nin gerçekten çalıştığını doğrulamanın
     * tek pratik yolu.
     *
     * `mergeMailConfig` yalnız DB'de mail.host doluysa devreye giriyor; boşsa
     * env korunuyor ve rapor sessizce log'a yazılabiliyor. "Rapor gelmiyor"
     * şikâyetinin ilk nedeni budur, bu düğme onu bir saniyede ayırt eder.
     */
    public function ornekRaporGonder(): void
    {
        try {
            Artisan::call('kahya:gunluk-rapor');

            $son = KahyaCalismasi::query()->gunlukRapor()->latest('id')->first();

            if ($son?->gonderildi) {
                Notification::make()
                    ->title('Rapor gönderildi')
                    ->body('Alıcı: '.$son->alici.' — gelen kutunu kontrol et. Gelmezse spam klasörüne bak.')
                    ->success()
                    ->send();

                return;
            }

            Notification::make()
                ->title('Rapor üretildi ama gönderilemedi')
                ->body($son?->hata ?: 'Sebep kaydedilmedi. Mail Ayarları sayfasını kontrol et.')
                ->warning()
                ->persistent()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Rapor üretilemedi')
                ->body(mb_substr($e->getMessage(), 0, 200))
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
