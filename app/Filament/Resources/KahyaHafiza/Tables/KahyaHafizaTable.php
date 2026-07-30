<?php

namespace App\Filament\Resources\KahyaHafiza\Tables;

use App\Enums\HafizaTuru;
use App\Models\KahyaHafizasi;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class KahyaHafizaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('tur')
                    ->label('Tür')
                    ->badge()
                    ->formatStateUsing(fn (HafizaTuru $state): string => $state->etiket())
                    ->color(fn (HafizaTuru $state): string => match ($state) {
                        HafizaTuru::Kural => 'warning',
                        HafizaTuru::Gercek => 'info',
                        HafizaTuru::Ders => 'success',
                        HafizaTuru::Not => 'gray',
                    }),
                TextColumn::make('metin')
                    ->label('Metin')
                    ->searchable()
                    ->wrap()
                    ->limit(120),
                TextColumn::make('kaynak')
                    ->label('Kaynak')
                    ->badge()
                    // Kâhya'nın kendi çıkarımı sahibinkiyle aynı görünmemeli —
                    // saçma çıkarım tek bakışta ayırt edilip silinebilmeli.
                    ->color(fn (string $state): string => $state === KahyaHafizasi::KAYNAK_CIKARIM ? 'danger' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === KahyaHafizasi::KAYNAK_CIKARIM ? 'Kâhya çıkarımı' : 'Sahip'),
                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('kullanim_sayisi')
                    ->label('Arandı')
                    ->numeric()
                    ->sortable()
                    ->tooltip('Kâhya\'nın bu kaydı tablo-sorgula ile kaç kez arayıp bulduğu.'),
                TextColumn::make('created_at')
                    ->label('Eklendi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tur')
                    ->label('Tür')
                    ->options(collect(HafizaTuru::cases())->mapWithKeys(
                        fn (HafizaTuru $t): array => [$t->value => $t->etiket()],
                    )),
                TernaryFilter::make('aktif')->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
