<?php

namespace App\Filament\Resources\YasamKonuOnerileri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\YasamKonuIcerikleri\YasamKonuIcerigiResource;
use App\Filament\Resources\YasamKonuOnerileri\Pages\ListYasamKonuOnerileri;
use App\Models\YasamKonuOnerisi;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Yaşam Rehberi — topluluk düzeltme önerileri kuyruğu.
 *
 * F0 KAPSAMI: yalnız görüntüleme/durum değiştirme. Önerinin içeriğe
 * GERÇEKTEN işlenmesi (birleştirme akışı) ve herkese açık gönderim formu
 * F3'te gelecek — bkz. docs/plans/2026-08-21-yasam-rehberi-tasarimi.md §7.
 * "Onayla" burada yalnız durumu değiştirir; içeriği elle
 * YasamKonuIcerigiResource'tan düzenlersiniz (aşağıdaki "İçeriğe git" linki).
 *
 * SALT-OKUNUR kuyruk deseni (form yok, oluşturma yok) — Ülke Rehberi'ndeki
 * RehberGeriBildirimiResource'un aynısı.
 */
class YasamKonuOnerisiResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = YasamKonuOnerisi::class;

    protected static ?string $slug = 'yasam-konu-onerileri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static string|UnitEnum|null $navigationGroup = 'Ülke Rehberi';

    protected static ?string $navigationLabel = 'Yaşam Konu Önerileri';

    protected static ?int $navigationSort = 8;

    public static function getModelLabel(): string
    {
        return 'yaşam konu önerisi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Yaşam Konu Önerileri';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (YasamKonuOnerisi::query()->bekleyen()->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('icerik.konu.baslik')->label('Konu'),
                TextColumn::make('icerik.country_code')->label('Ülke')->badge(),
                TextColumn::make('kullanici.name')->label('Öneren'),
                TextColumn::make('onerilen_metin')->label('Öneri')->wrap()->limit(140),
                TextColumn::make('durum')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        YasamKonuOnerisi::DURUM_ONAYLANDI => 'success',
                        YasamKonuOnerisi::DURUM_REDDEDILDI => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->options([
                        YasamKonuOnerisi::DURUM_BEKLIYOR => 'Bekliyor',
                        YasamKonuOnerisi::DURUM_ONAYLANDI => 'Onaylandı',
                        YasamKonuOnerisi::DURUM_REDDEDILDI => 'Reddedildi',
                    ]),
            ])
            ->recordActions([
                Action::make('icerigeGit')
                    ->label('İçeriğe git')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (YasamKonuOnerisi $r): string => YasamKonuIcerigiResource::getUrl('edit', ['record' => $r->yasam_konu_icerigi_id])),
                Action::make('onayla')
                    ->label('Onayla')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (YasamKonuOnerisi $r): bool => $r->durum === YasamKonuOnerisi::DURUM_BEKLIYOR)
                    ->action(fn (YasamKonuOnerisi $r) => $r->update(['durum' => YasamKonuOnerisi::DURUM_ONAYLANDI])),
                Action::make('reddet')
                    ->label('Reddet')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn (YasamKonuOnerisi $r): bool => $r->durum === YasamKonuOnerisi::DURUM_BEKLIYOR)
                    ->action(fn (YasamKonuOnerisi $r) => $r->update(['durum' => YasamKonuOnerisi::DURUM_REDDEDILDI])),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListYasamKonuOnerileri::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
