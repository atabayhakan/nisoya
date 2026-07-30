<?php

namespace App\Filament\Resources\OutreachTargets\Pages;

use App\Filament\Resources\OutreachTargets\OutreachTargetResource;
use App\Filament\Widgets\KesifIlerlemeWidget;
use App\Jobs\EnrichTargetJob;
use App\Jobs\RunDiscoveryJob;
use App\Models\OutreachTarget;
use App\Support\Growth\GrowthCatalog;
use App\Support\Growth\KesifIlerlemesi;
use App\Support\Growth\RegionPolicy;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Pagination\LengthAwarePaginator;

class ListOutreachTargets extends ListRecords
{
    protected static string $resource = OutreachTargetResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            KesifIlerlemeWidget::class,
        ];
    }

    /**
     * Havuz büyüyünce (yüzlerce aday) her sayfa gezintisi için tablonun
     * ALTINA kaydırmak zahmetliydi — üst araç çubuğuna da aynı gezinmeyi
     * koyduk. `previousPage`/`nextPage` Livewire'ın kendi sayfalama state'ini
     * (aynı `pageName` ile) tetikler; tablo bunu zaten dinliyor.
     */
    protected function getHeaderActions(): array
    {
        // getHeaderActions() `booted` sırasında, tablo ($this->table) henüz
        // KURULMADAN çağrılıyor — getTable()/getTableRecords() burada erken
        // erişilirse "typed property must not be accessed before
        // initialization" hatası verir. Bu yüzden hepsi Closure: gerçek
        // değerlendirme render anına ertelenir, tablo o zaman hazırdır.
        $paginator = function (): ?LengthAwarePaginator {
            $records = $this->getTableRecords();

            return $records instanceof LengthAwarePaginator ? $records : null;
        };

        return [
            Action::make('oncekiSayfa')
                ->label('◀')
                ->tooltip('Önceki sayfa')
                ->color('gray')
                ->disabled(fn () => ($paginator())?->onFirstPage() ?? true)
                ->action(fn () => $this->previousPage($this->getTablePaginationPageName())),

            Action::make('sayfaBilgisi')
                ->label(function () use ($paginator): string {
                    $p = $paginator();

                    return $p !== null ? "Sayfa {$p->currentPage()} / {$p->lastPage()}" : 'Sayfa 1 / 1';
                })
                ->color('gray')
                ->disabled(),

            Action::make('sonrakiSayfa')
                ->label('▶')
                ->tooltip('Sonraki sayfa')
                ->color('gray')
                ->disabled(fn () => ! (($paginator())?->hasMorePages() ?? false))
                ->action(fn () => $this->nextPage($this->getTablePaginationPageName())),

            Action::make('kesfet')
                ->label('Yeni keşif çalıştır')
                ->icon(Heroicon::OutlinedMagnifyingGlass)
                ->modalHeading('Ülkede Türk işletme keşfet')
                ->modalDescription('Seçtiğin ülkede katalogdaki şehir ve mesleklerde keşif ARKA PLANDA çalışır; sonuçlar birkaç dakika içinde bu havuza düşer (sayfayı yenile). Gönderim yapılmaz.')
                ->modalSubmitActionLabel('Keşfet')
                ->schema([
                    Select::make('country')
                        ->label('Ülke')
                        ->options(array_combine(array_keys(GrowthCatalog::CITIES), array_keys(GrowthCatalog::CITIES)))
                        ->required()
                        ->native(false),
                    TextInput::make('trades')
                        ->label('Kaç meslek taransın')
                        ->numeric()
                        ->default(3)
                        ->minValue(1)
                        ->maxValue(10),
                ])
                ->action(fn (array $data) => $this->runDiscovery((string) $data['country'], (int) ($data['trades'] ?? 3))),

            Action::make('zenginlestir')
                ->label('İletişim bul')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('İletişim e-postalarını bul')
                ->modalDescription('Web sitesi olan, henüz e-postası olmayan ve GÖNDERİLEBİLİR (AB/TR/RU dışı) adayların kendi sitesinden iletişim e-postası aranır. Arka planda çalışır; gönderim yapılmaz.')
                ->modalSubmitActionLabel('Aramayı başlat')
                ->action(fn () => $this->runEnrichment()),
        ];
    }

    /**
     * Keşfi ARKA PLANA alır: her şehir × meslek için bir kuyruk işi kuyruklar
     * (RunDiscoveryJob), böylece HTTP isteği anında döner. Gerçek kaynakla
     * (Overpass/Google) senkron keşif nginx'te 504 veriyordu — bu yüzden işe
     * bölündü. Livewire ile test edilebilsin diye ayrı public metot (bkz. repo
     * deseni: KurtarmaKiti/Yedekleme).
     */
    public function runDiscovery(string $country, int $trades = 3): void
    {
        $country = strtoupper($country);

        if (! isset(GrowthCatalog::CITIES[$country])) {
            Notification::make()->title('Bilinmeyen ülke')->danger()->send();

            return;
        }

        $cities = GrowthCatalog::CITIES[$country];
        $tradeList = array_slice(GrowthCatalog::tradesForCountry($country), 0, max(1, $trades));

        $count = count($cities) * count($tradeList);
        $lot = KesifIlerlemesi::baslat((int) auth()->id(), $count);

        foreach ($cities as $city) {
            foreach ($tradeList as $trade) {
                RunDiscoveryJob::dispatch($country, $city, $trade, lot: $lot)->onConnection('database');
            }
        }

        Notification::make()
            ->title('Keşif kuyruğa alındı')
            ->body("{$country} için {$count} arama arka planda çalışacak. Sonuçlar birkaç dakika içinde bu havuza düşer — sayfayı yenileyerek takip edebilirsin.")
            ->success()
            ->send();
    }

    /**
     * Web sitesi olan gönderilebilir adaylar için iletişim e-postası aramayı
     * ARKA PLANA alır (aday başına EnrichTargetJob). AB/TR/RU adayları GDPR
     * gereği hiç kuyruğa alınmaz.
     */
    public function runEnrichment(): void
    {
        $ids = OutreachTarget::query()
            ->pendingEnrichment()
            ->where('marketing_status', RegionPolicy::ALLOWED)
            ->pluck('id');

        if ($ids->isEmpty()) {
            Notification::make()
                ->title('Zenginleştirilecek aday yok')
                ->body('Web sitesi olan, e-postası olmayan, gönderilebilir bir aday bulunamadı.')
                ->warning()
                ->send();

            return;
        }

        foreach ($ids as $id) {
            EnrichTargetJob::dispatch((int) $id)->onConnection('database');
        }

        Notification::make()
            ->title('İletişim arama kuyruğa alındı')
            ->body("{$ids->count()} işletme için iletişim e-postası aranıyor. Sonuçlar birkaç dakika içinde 'E-posta' sütununa düşer.")
            ->success()
            ->send();
    }
}
