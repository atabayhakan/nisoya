<?php

namespace App\Filament\Resources\RehberGeriBildirimleri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\RehberGeriBildirimleri\Pages\ListRehberGeriBildirimleri;
use App\Filament\Resources\TemsilcilikIslemleri\TemsilcilikIslemiResource;
use App\Models\RehberGeriBildirimi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Ülke Rehberi — "bu bilgi güncel mi?" geri bildirim kuyruğu.
 *
 * SALT-OKUNUR kuyruk (form yok, oluşturma yok): kayıtlar siteden gelir,
 * admin yalnız okur, işaretler ve gerekirse ilgili işlem içeriğine gidip
 * düzeltir. Rozet incelenmemiş sayısını gösterir — sıfırlanması beklenen
 * bir gelen kutusudur, arşiv değil.
 */
class RehberGeriBildirimiResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = RehberGeriBildirimi::class;

    protected static ?string $slug = 'rehber-geri-bildirimleri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Ülke Rehberi';

    protected static ?string $navigationLabel = 'Geri Bildirimler';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return 'geri bildirim';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Geri Bildirimler';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (RehberGeriBildirimi::query()->where('incelendi', false)->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('islem.temsilcilik.ad')->label('Temsilcilik'),
                TextColumn::make('islem.islemTuru.ad')->label('İşlem'),
                TextColumn::make('tur')
                    ->label('Tür')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'oneri' ? 'info' : 'warning')
                    ->formatStateUsing(fn (RehberGeriBildirimi $r): string => $r->turEtiketi()),
                TextColumn::make('metin')->label('Mesaj')->wrap()->limit(120)->placeholder('—'),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                IconColumn::make('incelendi')->label('İncelendi')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('incelendi')->label('İncelendi'),
            ])
            ->recordActions([
                Action::make('islemeGit')
                    ->label('İçeriğe git')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (RehberGeriBildirimi $r): string => TemsilcilikIslemiResource::getUrl('edit', ['record' => $r->temsilcilik_islemi_id])),
                Action::make('incelendiIsaretle')
                    ->label('İncelendi')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (RehberGeriBildirimi $r): bool => ! $r->incelendi)
                    ->action(fn (RehberGeriBildirimi $r) => $r->update(['incelendi' => true])),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRehberGeriBildirimleri::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
