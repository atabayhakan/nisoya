<?php

namespace App\Filament\Resources\OutreachTargets\Tables;

use App\Models\OutreachTarget;
use App\Support\Growth\GrowthCatalog;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OutreachTargetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('İşletme')
                    ->description(fn (OutreachTarget $r): ?string => $r->city ? $r->city.' · '.$r->sector : $r->sector)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('country')
                    ->label('Ülke')
                    ->badge(),
                TextColumn::make('detection_band')
                    ->label('Sonuç')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'turkish' => 'success',
                        'ambiguous' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'turkish' => '✓ Türk',
                        'ambiguous' => '? Sınırda',
                        default => 'Türk değil',
                    }),
                TextColumn::make('detection_confidence')
                    ->label('Güven')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('marketing_status')
                    ->label('Gönderim')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'allowed' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'allowed' ? 'gönderilebilir' : 'engelli'),
                TextColumn::make('signals_text')
                    ->label('Sinyaller')
                    ->state(fn (OutreachTarget $r): string => implode('; ', $r->detection_signals ?? []))
                    ->wrap()
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('contact_email')
                    ->label('E-posta')
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(),
                IconColumn::make('needs_review')
                    ->label('İnceleme')
                    ->boolean(),
                TextColumn::make('source')
                    ->label('Kaynak')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('detection_band')
                    ->label('Sonuç')
                    ->options([
                        'turkish' => '✓ Türk',
                        'ambiguous' => '? Sınırda',
                    ]),
                SelectFilter::make('marketing_status')
                    ->label('Gönderim')
                    ->options([
                        'allowed' => 'Gönderilebilir',
                        'region_blocked' => 'Engelli (AB/TR/RU)',
                    ]),
                SelectFilter::make('country')
                    ->label('Ülke')
                    ->options(array_combine(array_keys(GrowthCatalog::CITIES), array_keys(GrowthCatalog::CITIES))),
                TernaryFilter::make('needs_review')
                    ->label('İnceleme bekleyen'),
            ])
            ->defaultSort('detection_confidence', 'desc')
            // Uzun havuzda gezinmeyi kolaylaştır: sayfa boyutu seçenekleri +
            // sayfa değişince otomatik en üste kaydır (Filament sayfa numaralarını
            // tablonun ALTINDA gösterir — üstte arama + filtreler zaten var).
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->scrollToTopOnPageChange()
            ->recordActions([
                Action::make('google')
                    ->label('Google')
                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                    ->color('gray')
                    ->url(fn (OutreachTarget $r): string => 'https://www.google.com/search?q='.rawurlencode(trim($r->name.' '.$r->city.' '.$r->country)))
                    ->openUrlInNewTab(),
                Action::make('maps')
                    ->label('Maps')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->color('gray')
                    ->url(fn (OutreachTarget $r): string => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(trim($r->name.' '.$r->city)))
                    ->openUrlInNewTab(),
                Action::make('onayla')
                    ->label('Onayla')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (OutreachTarget $r): bool => $r->needs_review)
                    ->action(fn (OutreachTarget $r) => $r->update(['needs_review' => false, 'status' => 'onayli'])),
                Action::make('reddet')
                    ->label('Reddet')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (OutreachTarget $r): bool => $r->needs_review)
                    ->action(fn (OutreachTarget $r) => $r->update(['needs_review' => false, 'status' => 'reddedildi'])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
