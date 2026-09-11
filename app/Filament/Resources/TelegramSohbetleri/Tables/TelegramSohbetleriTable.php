<?php

namespace App\Filament\Resources\TelegramSohbetleri\Tables;

use App\Models\TelegramSohbeti;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TelegramSohbetleriTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('soru_metni')
                    ->label('Soru')
                    ->limit(60)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn (?string $state): string => $state === null ? 'gray' : 'warning')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'vize_hukuk' => 'Vize/Hukuk',
                        'para' => 'Para',
                        default => '—',
                    }),
                IconColumn::make('needs_review')
                    ->label('İnceleme')
                    ->boolean(),
                TextColumn::make('telegram_kullanici_adi')
                    ->label('Kullanıcı')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Ne zaman')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('needs_review')
                    ->label('İnceleme bekleyen')
                    ->default(true),
            ])
            ->recordActions([
                Action::make('incele')
                    ->label('İncele')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Telegram sohbeti')
                    ->modalDescription(fn (TelegramSohbeti $r): string => "SORU:\n{$r->soru_metni}\n\n———\n\nCEVAP:\n".Str::limit((string) $r->cevap_metni, 2000))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                Action::make('incelendi')
                    ->label('İncelendi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TelegramSohbeti $r): bool => $r->needs_review)
                    ->action(fn (TelegramSohbeti $r) => $r->update(['needs_review' => false])),
            ]);
    }
}
