<?php

namespace App\Filament\Resources\Listings\Tables;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Listing;
use App\Services\DolandiricilikTespiti;
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
use Illuminate\Database\Eloquent\Builder;

class ListingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Başlık')
                    ->weight('medium')
                    ->description(fn ($record) => $record->user?->name)
                    ->searchable()
                    ->limit(40),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('type')
                    ->label('Tür')
                    ->badge(),
                TextColumn::make('price')
                    ->label('Fiyat')
                    ->formatStateUsing(fn ($state, $record) => $state !== null
                        ? number_format((float) $state, 2).' '.$record->currency
                        : 'Görüşülür')
                    ->sortable(),
                TextColumn::make('country_code')
                    ->label('Ülke')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge(),
                /*
                 * METİN DENETİMİ İŞARETİ.
                 *
                 * Hafif kategoride ilan yayında KALIYOR; işaret yalnız burada
                 * görünüyor. Bu sütun olmasaydı hafif tespitin hiçbir
                 * karşılığı olmazdı — kimse görmediği bir işaret, tespit
                 * sayılmaz.
                 */
                TextColumn::make('fraud_reason')
                    ->label('Metin denetimi')
                    ->badge()
                    ->color(fn (?string $state): string => $state === null
                        ? 'gray'
                        : (app(DolandiricilikTespiti::class)->agirMi($state) ? 'danger' : 'warning'))
                    ->formatStateUsing(fn (string $state): string => app(DolandiricilikTespiti::class)->kategoriAdi($state))
                    ->placeholder('—')
                    ->wrap(),
                IconColumn::make('is_featured')
                    ->label('Öne çıkan')
                    ->boolean(),
                TextColumn::make('views_count')
                    ->label('Görüntülenme')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(ListingStatus::class),
                SelectFilter::make('type')
                    ->label('Tür')
                    ->options(ListingType::class),
                TernaryFilter::make('is_featured')
                    ->label('Öne çıkan'),
                TernaryFilter::make('fraud_reason')
                    ->label('Metin denetimi işaretli')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('fraud_reason'),
                        false: fn (Builder $q) => $q->whereNull('fraud_reason'),
                        blank: fn (Builder $q) => $q,
                    ),
            ])
            ->recordActions([
                /*
                 * İŞARETİ KALDIR — yanlış alarmın çıkış yolu.
                 *
                 * Olmasaydı yanlış işaretlenen bir ilan panelde temelli
                 * kırmızı kalırdı ve gerçek işaretler bu gürültünün içinde
                 * kaybolurdu.
                 *
                 * `forceFill`: `fraud_reason` bilerek `$fillable` dışında
                 * (hiçbir form set edemesin diye), o yüzden `update()` bu
                 * alanı SESSİZCE yok sayardı.
                 */
                Action::make('isareti-kaldir')
                    ->label('İşareti kaldır')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Bu ilanın metin denetimi işareti kaldırılacak. İlanın durumu değişmez.')
                    ->visible(fn (Listing $record): bool => $record->fraud_reason !== null)
                    ->action(fn (Listing $record) => $record->forceFill(['fraud_reason' => null])->save()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
