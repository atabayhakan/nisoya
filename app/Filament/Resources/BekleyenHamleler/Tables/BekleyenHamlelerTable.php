<?php

namespace App\Filament\Resources\BekleyenHamleler\Tables;

use App\Models\BekleyenHamle;
use App\Services\Kahya\Dis\HamleGonderici;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BekleyenHamlelerTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('baslik')
                    ->label('Hamle')
                    ->searchable()
                    ->wrap()
                    ->description(fn (BekleyenHamle $record): string => Str::limit($record->gerekce, 90)),
                TextColumn::make('tur')
                    ->label('Tür')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'eposta' => 'E-posta',
                        'sosyal' => 'Sosyal',
                        default => 'Öneri',
                    })
                    ->color('gray'),
                TextColumn::make('gorev.baslik')
                    ->label('Görev')
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('durum')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        BekleyenHamle::DURUM_BEKLEMEDE => 'Kararını bekliyor',
                        BekleyenHamle::DURUM_ONAYLANDI => 'Onaylandı',
                        BekleyenHamle::DURUM_REDDEDILDI => 'Reddedildi',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        BekleyenHamle::DURUM_BEKLEMEDE => 'warning',
                        BekleyenHamle::DURUM_ONAYLANDI => 'success',
                        default => 'danger',
                    }),
                TextColumn::make('gonderildi_at')
                    ->label('Gönderim')
                    ->state(fn (BekleyenHamle $record): string => match (true) {
                        $record->gonderildi_at !== null => '✓ '.$record->gonderildi_at->format('d.m H:i'),
                        $record->gonderim_hata !== null => '⚠ hata',
                        default => '—',
                    })
                    ->tooltip(fn (BekleyenHamle $record): ?string => $record->gonderim_hata),
                TextColumn::make('created_at')
                    ->label('Önerildi')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->options([
                        BekleyenHamle::DURUM_BEKLEMEDE => 'Kararını bekliyor',
                        BekleyenHamle::DURUM_ONAYLANDI => 'Onaylandı',
                        BekleyenHamle::DURUM_REDDEDILDI => 'Reddedildi',
                    ])
                    ->default(BekleyenHamle::DURUM_BEKLEMEDE),
            ])
            ->recordActions([
                // Kartın tam içeriği — karar bu okumadan verilmemeli.
                Action::make('incele')
                    ->label('İncele')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (BekleyenHamle $record): string => $record->baslik)
                    ->modalDescription(fn (BekleyenHamle $record): string => "Gerekçe: {$record->gerekce}\n\n———\n\n{$record->icerik}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                Action::make('onayla')
                    ->label('Onayla')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (BekleyenHamle $record): bool => $record->durum === BekleyenHamle::DURUM_BEKLEMEDE)
                    ->modalHeading(fn (BekleyenHamle $record): string => "Onayla: {$record->baslik}")
                    // Onay = e-posta türünde GÖNDERİM demek (F4) — sahip neyi
                    // onayladığını alıcısıyla birlikte görmeli.
                    ->modalDescription(fn (BekleyenHamle $record): string => ($record->tur === 'eposta' && $record->alici_eposta
                            ? "Alıcı: {$record->alici_eposta} — onaylarsan Kâhya'nın gönderim kimliğiyle GÖNDERİLİR.\n\n"
                            : '')
                        .Str::limit($record->icerik, 400))
                    ->schema([
                        Textarea::make('karar_notu')
                            ->label('Not (isteğe bağlı)')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Kâhya bu notu görür — sonraki önerilerini buna göre şekillendirir.'),
                    ])
                    ->action(function (BekleyenHamle $record, array $data): void {
                        $record->kararVer(BekleyenHamle::DURUM_ONAYLANDI, $data['karar_notu'] ?? null);

                        // F4: e-posta hamlesi onaylandığında gönderim denenir;
                        // sonuç ne olursa olsun sahibe AYNEN söylenir (gönderildi /
                        // tavan dolu / yapılandırılmamış / hata) — HamleGonderici
                        // her durumda dürüst bir cümle döndürür.
                        if ($record->tur === 'eposta') {
                            $sonuc = app(HamleGonderici::class)->gonder($record->refresh());

                            Notification::make()
                                ->title('Hamle onaylandı')
                                ->body($sonuc)
                                ->{str_starts_with($sonuc, 'Gönderildi') ? 'success' : 'warning'}()
                                ->persistent()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Hamle onaylandı')
                            ->body('Karar kaydedildi. Bu tür (e-posta değil) elle uygulanır; Kâhya kararını gördü.')
                            ->success()
                            ->send();
                    }),

                Action::make('reddet')
                    ->label('Reddet')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (BekleyenHamle $record): bool => $record->durum === BekleyenHamle::DURUM_BEKLEMEDE)
                    ->modalHeading(fn (BekleyenHamle $record): string => "Reddet: {$record->baslik}")
                    ->schema([
                        Textarea::make('karar_notu')
                            ->label('Neden? (isteğe bağlı ama değerli)')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Sebep yazarsan Kâhya aynı hatayı tekrarlamamayı öğrenir.'),
                    ])
                    ->action(function (BekleyenHamle $record, array $data): void {
                        $record->kararVer(BekleyenHamle::DURUM_REDDEDILDI, $data['karar_notu'] ?? null);

                        Notification::make()->title('Hamle reddedildi')->success()->send();
                    }),
            ]);
    }
}
