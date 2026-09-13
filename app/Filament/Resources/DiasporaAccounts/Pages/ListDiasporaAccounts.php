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
                            'username' => '@berlinturkleri',
                            'title' => 'Berlin Türkleri Topluluğu',
                            'description' => 'Almanya Berlin ve çevresindeki Türk esnaf, etkinlik ve kültürel paylaşımlar.',
                            'country_code' => 'DE',
                            'city' => 'Berlin',
                            'is_verified' => true,
                            'autopilot' => true,
                        ],
                        [
                            'username' => '@biskek_turkleri',
                            'title' => 'Bişkek Türk Topluluğu & Dayanışma',
                            'description' => 'Kırgızistan Bişkek’te yaşayan Türk girişimciler, esnaf ve öğrenciler.',
                            'country_code' => 'KG',
                            'city' => 'Bişkek',
                            'is_verified' => true,
                            'autopilot' => false,
                        ],
                        [
                            'username' => '@amsterdamturkleri',
                            'title' => 'Amsterdam & Hollanda Türkleri',
                            'description' => 'Hollanda’daki Türk toplumu kültürel etkinlikleri ve gençlik buluşmaları.',
                            'country_code' => 'NL',
                            'city' => 'Amsterdam',
                            'is_verified' => false,
                            'autopilot' => false,
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
