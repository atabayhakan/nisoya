<?php

namespace App\Filament\Resources\DiasporaAccounts\Pages;

use App\Filament\Resources\DiasporaAccounts\DiasporaAccountResource;
use App\Models\DiasporaAccount;
use App\Services\Diaspora\DiasporaSyncEngine;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListDiasporaAccounts extends ListRecords
{
    protected static string $resource = DiasporaAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tumunuSenkronizeEt')
                ->label('Tüm Hesapları Şimdi Tara')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->tooltip('Tüm aktif izlenen diaspora hesaplarını tarar ve yeni reels içeriklerini çeker.')
                ->action(function (DiasporaSyncEngine $syncEngine): void {
                    $result = $syncEngine->syncAll();

                    Notification::make()
                        ->title('Senkronizasyon Tamamlandı')
                        ->body("{$result['accounts_count']} hesap tarandı. Toplam {$result['total_created']} yeni içerik aktarıldı ({$result['autopilot_published']} otopilot yayında, kalanı onay bekliyor).")
                        ->success()
                        ->send();
                }),

            Action::make('ornekHesaplariEkle')
                ->label('Örnek Hesapları Ekle')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->action(function (): void {
                    $accounts = [
                        [
                            'username' => '@amerikaliturkler',
                            'title' => 'Amerikalı Türkler (TRUS)',
                            'description' => 'Amerika’daki en büyük Türk topluluk ve haber ağı.',
                            'country_code' => 'US',
                            'city' => 'New York',
                            'is_verified' => true,
                            'autopilot' => true,
                        ],
                        [
                            'username' => '@almanyaturkagi',
                            'title' => 'Almanya Türk Ağı (ATA)',
                            'description' => 'Deutsch-Türkisches Netzwerk für Bildung, Kultur und Gemeinschaft.',
                            'country_code' => 'DE',
                            'city' => 'Berlin',
                            'is_verified' => true,
                            'autopilot' => true,
                        ],
                        [
                            'username' => '@turkishcommunityinqatar',
                            'title' => 'Turkish Community in Qatar',
                            'description' => 'Katar’da yaşayan Türklerin resmi topluluk ve buluşma sayfası.',
                            'country_code' => 'QA',
                            'city' => 'Doha',
                            'is_verified' => true,
                            'autopilot' => true,
                        ],
                        [
                            'username' => '@turkishcommunitycentre',
                            'title' => 'Turkish Community Centre of Canada',
                            'description' => 'Kanada Türk Toplum Mirası Merkezi (TCHCC).',
                            'country_code' => 'CA',
                            'city' => 'Toronto',
                            'is_verified' => true,
                            'autopilot' => true,
                        ],
                        [
                            'username' => '@turkishbusinesscouncildubai',
                            'title' => 'Turkish Business Council Dubai',
                            'description' => 'Körfez ve Dubai Türk iş dünyası ve ticaret konseyi.',
                            'country_code' => 'AE',
                            'city' => 'Dubai',
                            'is_verified' => true,
                            'autopilot' => true,
                        ],
                    ];

                    $added = 0;
                    foreach ($accounts as $acc) {
                        if (! DiasporaAccount::query()->where('username', $acc['username'])->exists()) {
                            DiasporaAccount::create($acc);
                            $added++;
                        }
                    }

                    Notification::make()
                        ->title('Örnek Hesaplar Eklendi')
                        ->body("{$added} adet doğrulanmış diaspora topluluk hesabı listeye eklendi.")
                        ->success()
                        ->send();
                }),

            CreateAction::make()
                ->label('Yeni Hesap Takip Et')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
