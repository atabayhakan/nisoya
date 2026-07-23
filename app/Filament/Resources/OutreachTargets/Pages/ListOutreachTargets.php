<?php

namespace App\Filament\Resources\OutreachTargets\Pages;

use App\Filament\Resources\OutreachTargets\OutreachTargetResource;
use App\Jobs\RunDiscoveryJob;
use App\Support\Growth\GrowthCatalog;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListOutreachTargets extends ListRecords
{
    protected static string $resource = OutreachTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
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

        $count = 0;
        foreach ($cities as $city) {
            foreach ($tradeList as $trade) {
                RunDiscoveryJob::dispatch($country, $city, $trade)->onConnection('database');
                $count++;
            }
        }

        Notification::make()
            ->title('Keşif kuyruğa alındı')
            ->body("{$country} için {$count} arama arka planda çalışacak. Sonuçlar birkaç dakika içinde bu havuza düşer — sayfayı yenileyerek takip edebilirsin.")
            ->success()
            ->send();
    }
}
