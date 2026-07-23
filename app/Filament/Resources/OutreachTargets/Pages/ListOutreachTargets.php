<?php

namespace App\Filament\Resources\OutreachTargets\Pages;

use App\Filament\Resources\OutreachTargets\OutreachTargetResource;
use App\Services\Growth\DiscoveryRunner;
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
                ->modalDescription('Seçtiğin ülkede katalogdaki şehir ve mesleklerde keşif çalıştırılır; sonuçlar bu havuza eklenir. (Gönderim yapılmaz.)')
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
     * Keşfi senkron çalıştırır (şu an fixture kaynağı — anlık). Gerçek Google
     * Places anahtarı eklendiğinde çok sayıda API çağrısı yavaş olacağından bu
     * kuyruğa alınmalı (sonraki adım). Livewire ile test edilebilsin diye ayrı
     * public metot (bkz. repo deseni: KurtarmaKiti/Yedekleme).
     */
    public function runDiscovery(string $country, int $trades = 3): void
    {
        $country = strtoupper($country);

        if (! isset(GrowthCatalog::CITIES[$country])) {
            Notification::make()->title('Bilinmeyen ülke')->danger()->send();

            return;
        }

        $stats = app(DiscoveryRunner::class)->runForCountry($country, $trades);

        Notification::make()
            ->title("Keşif tamamlandı: {$country}")
            ->body("{$stats['discovered']} işletme bulundu · {$stats['turkish']} Türk · {$stats['ambiguous']} sınırda · {$stats['saved']} havuza yazıldı.")
            ->success()
            ->send();
    }
}
