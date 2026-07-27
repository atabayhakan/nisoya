<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Enums\ContactCategory;
use App\Enums\ContactMessageStatus;
use App\Enums\ContactPriority;
use App\Models\ContactMessage;
use App\Support\Destek;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('priority')
                    ->label('Öncelik')
                    ->badge()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Ad Soyad')
                    ->description(fn (ContactMessage $record) => $record->email)
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Konu')
                    ->badge()
                    ->searchable(),
                TextColumn::make('message')
                    ->label('Mesaj')
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->searchable(),
                TextColumn::make('assignee.name')
                    ->label('Üstlenen')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Geldi')
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (ContactMessage $record) => $record->created_at->diffForHumans())
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Konu')
                    ->options(ContactCategory::class),
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(ContactMessageStatus::class),
                SelectFilter::make('priority')
                    ->label('Öncelik')
                    ->options(ContactPriority::class),
                TernaryFilter::make('assigned_to')
                    ->label('Üstlenilmiş')
                    ->nullable()
                    ->attribute('assigned_to'),
            ])
            // Acil biletler her zaman üstte; eşitlikte en yeni önce.
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('yanitla')
                    ->label('Yanıtla')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    // Yanıt gerçek bir e-posta gönderir — moderatöre kapalı
                    // (moderatör gelen kutusunu görebiliyor ama site adına
                    // dışarıya yazışamamalı).
                    ->visible(fn () => Destek::yanitlayabilirMi())
                    ->schema([
                        Textarea::make('body')
                            ->label('Yanıtın')
                            ->rows(6)
                            ->required()
                            ->minLength(5)
                            ->maxLength(5000)
                            ->helperText('Bu metin misafire e-posta olarak gider. Selamlama ve kapanış otomatik eklenir (Site Yönetimi → E-posta Metinleri).'),
                    ])
                    ->modalHeading(fn (ContactMessage $record) => "Yanıt: {$record->name}")
                    ->modalDescription(fn (ContactMessage $record) => Str::limit($record->message, 300))
                    ->modalSubmitActionLabel('Gönder')
                    ->action(function (ContactMessage $record, array $data) {
                        $yanit = Destek::yanitla($record, (string) $data['body']);

                        if ($yanit->basarisizMi()) {
                            Notification::make()
                                ->title('Yanıt gönderilemedi')
                                ->body('Hata bilete kaydedildi. E-posta ayarlarını kontrol et.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Yanıt gönderildi')
                            ->body("{$record->email} adresine iletildi.")
                            ->success()
                            ->send();
                    }),

                Action::make('ustlen')
                    ->label('Üstlen')
                    ->icon('heroicon-o-hand-raised')
                    ->color('gray')
                    ->visible(fn (ContactMessage $record) => $record->assigned_to !== auth()->id())
                    ->action(function (ContactMessage $record) {
                        $record->update(['assigned_to' => auth()->id()]);

                        Notification::make()->title('Bileti üstlendin')->success()->send();
                    }),

                Action::make('kapat')
                    ->label('Kapat')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Bileti kapat')
                    ->modalDescription('Kapatılan bilet "Açık" sekmesinden çıkar. Gerekirse tekrar açabilirsin.')
                    ->visible(fn (ContactMessage $record) => $record->status !== ContactMessageStatus::Kapandi)
                    ->action(function (ContactMessage $record) {
                        Destek::kapat($record);

                        Notification::make()->title('Bilet kapatıldı')->success()->send();
                    }),

                Action::make('yeniden_ac')
                    ->label('Yeniden aç')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (ContactMessage $record) => $record->status === ContactMessageStatus::Kapandi)
                    ->action(function (ContactMessage $record) {
                        $record->update([
                            'status' => ContactMessageStatus::Okundu,
                            'closed_at' => null,
                        ]);

                        Notification::make()->title('Bilet yeniden açıldı')->success()->send();
                    }),

                EditAction::make()->label('Detay'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
