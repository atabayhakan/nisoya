<?php

namespace App\Filament\Resources\RehberGeriBildirimleri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\IslemTurleri\IslemTuruResource;
use App\Filament\Resources\RehberGeriBildirimleri\Pages\ListRehberGeriBildirimleri;
use App\Filament\Resources\TemsilcilikIslemleri\TemsilcilikIslemiResource;
use App\Filament\Resources\Temsilcilikler\TemsilcilikResource;
use App\Models\Country;
use App\Models\IslemTuru;
use App\Models\RehberGeriBildirimi;
use App\Models\Temsilcilik;
use App\Services\Ai\CountryGuideAiAssistant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
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
        return 'Geri Bildirim';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Geri Bildirimler';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = RehberGeriBildirimi::query()->where('incelendi', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['islem.temsilcilik.country', 'islem.islemTuru']))
            ->columns([
                TextColumn::make('islem.temsilcilik.ad')
                    ->label('Temsilcilik')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->description(function (RehberGeriBildirimi $r): ?string {
                        $temsilcilik = $r->islem?->temsilcilik;
                        if (! $temsilcilik) {
                            return null;
                        }
                        $bayrak = $temsilcilik->country?->emoji ? $temsilcilik->country->emoji.' ' : '';
                        $sehir = $temsilcilik->sehir ? ' • '.$temsilcilik->sehir : '';

                        return $bayrak.$temsilcilik->country_code.$sehir;
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('islem.islemTuru.ad')
                    ->label('İşlem')
                    ->icon(function (RehberGeriBildirimi $r): BackedEnum {
                        $tur = $r->islem?->islemTuru;

                        return $tur ? IslemTuruResource::getIconForRecord($tur) : Heroicon::OutlinedDocumentText;
                    })
                    ->description(fn (RehberGeriBildirimi $r): ?string => $r->islem?->sure_metni ? 'Süre: '.$r->islem->sure_metni : null)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tur')
                    ->label('Tür')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'hata' => 'heroicon-o-exclamation-circle',
                        'guncel_degil' => 'heroicon-o-clock',
                        'oneri' => 'heroicon-o-light-bulb',
                        default => 'heroicon-o-information-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'hata' => 'danger',
                        'guncel_degil' => 'warning',
                        'oneri' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (RehberGeriBildirimi $r): string => $r->turEtiketi()),

                TextColumn::make('metin')
                    ->label('Mesaj')
                    ->wrap()
                    ->limit(110)
                    ->tooltip(fn (RehberGeriBildirimi $r): ?string => $r->metin)
                    ->description(fn (RehberGeriBildirimi $r): ?string => filled($r->ip_hash) ? 'Anonim İstemci: '.substr((string) $r->ip_hash, 0, 8).'...' : null)
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (RehberGeriBildirimi $r): ?string => $r->created_at?->diffForHumans())
                    ->sortable(),

                TextColumn::make('incelendi')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'İncelendi' : 'Bekliyor')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
            ])
            ->filters([
                TernaryFilter::make('incelendi')
                    ->label('İnceleme Durumu')
                    ->placeholder('Tümü')
                    ->trueLabel('Yalnızca İncelenenler')
                    ->falseLabel('Yalnızca Bekleyenler (İncelenmemiş)'),

                SelectFilter::make('tur')
                    ->label('Bildirim Türü')
                    ->options(RehberGeriBildirimi::TURLER),

                SelectFilter::make('country_code')
                    ->label('Ülke')
                    ->options(fn () => Country::query()->orderBy('name_tr')->get()->mapWithKeys(fn (Country $c) => [
                        $c->code => ($c->emoji ? $c->emoji.' ' : '').$c->name_tr.' ('.$c->code.')',
                    ]))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('islem.temsilcilik', fn ($q) => $q->where('country_code', $data['value']))
                        : $query
                    )
                    ->searchable(),

                SelectFilter::make('temsilcilik_id')
                    ->label('Temsilcilik')
                    ->options(fn () => Temsilcilik::query()->orderBy('country_code')->orderBy('sort_order')->pluck('ad', 'id'))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('islem', fn ($q) => $q->where('temsilcilik_id', $data['value']))
                        : $query
                    )
                    ->searchable(),

                SelectFilter::make('islem_turu_id')
                    ->label('İşlem Türü')
                    ->options(fn () => IslemTuru::query()->orderBy('sort_order')->pluck('ad', 'id'))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('islem', fn ($q) => $q->where('islem_turu_id', $data['value']))
                        : $query
                    )
                    ->searchable(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('incelendi_yap')
                        ->label('İncelendi Olarak İşaretle')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Seçili bildirimleri incelendi yap')
                        ->modalDescription('Seçtiğiniz tüm geri bildirimler incelendi durumuna getirilecektir.')
                        ->action(function ($records) {
                            $records->each->update(['incelendi' => true]);
                            Notification::make()->title('Seçili bildirimler incelendi olarak işaretlendi')->success()->send();
                        }),

                    BulkAction::make('bekliyor_yap')
                        ->label('Bekliyor Olarak İşaretle')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['incelendi' => false]);
                            Notification::make()->title('Seçili bildirimler bekliyor durumuna alındı')->warning()->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordActions([
                Action::make('canliSayfa')
                    ->label('Canlı Sayfa')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->tooltip('Bildirilen rehber sayfasını yeni sekmede aç')
                    ->visible(fn (RehberGeriBildirimi $r): bool => $r->islem !== null && $r->islem->temsilcilik !== null && $r->islem->islemTuru !== null)
                    ->url(fn (RehberGeriBildirimi $r): string => url('/'.strtolower($r->islem->temsilcilik->country_code).'/'.$r->islem->temsilcilik->slug.'/'.$r->islem->islemTuru->slug))
                    ->openUrlInNewTab(),

                Action::make('aiAnaliz')
                    ->label('AI Analiz')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('primary')
                    ->modalHeading(fn (RehberGeriBildirimi $r): string => 'AI Geri Bildirim Analizi #'.$r->id)
                    ->modalDescription(function (RehberGeriBildirimi $r, CountryGuideAiAssistant $assistant): HtmlString {
                        $procedureName = $r->islem && $r->islem->islemTuru ? $r->islem->islemTuru->ad : 'Konsolosluk İşlemi';
                        $currentNotes = $r->islem ? $r->islem->notlar : null;
                        $eval = $assistant->evaluateConsularFeedback((string) $r->metin, $procedureName, $currentNotes);

                        $color = match ($eval['oncelik']) {
                            'yuksek' => 'text-rose-700 dark:text-rose-400',
                            'orta' => 'text-amber-700 dark:text-amber-400',
                            default => 'text-emerald-700 dark:text-emerald-400',
                        };

                        return new HtmlString(
                            "<div class='space-y-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-sm'>"
                            ."<div class='flex items-center justify-between font-bold'>"
                            ."<span>Öncelik: <span class='{$color} uppercase'>".e($eval['oncelik']).'</span></span>'
                            ."<span class='text-xs text-gray-500 dark:text-gray-400'>Geçerlilik: ".e($eval['gecerlilik']).'</span>'
                            .'</div>'
                            ."<div><strong class='text-xs text-gray-500 dark:text-gray-400'>Öneri Özeti:</strong><p class='text-xs mt-0.5 text-gray-800 dark:text-gray-200'>".e($eval['oneri_ozeti']).'</p></div>'
                            ."<div class='pt-2 border-t border-gray-200 dark:border-gray-700'><strong class='text-xs text-gray-500 dark:text-gray-400'>Önerilen Aksiyon:</strong><p class='text-xs mt-0.5 text-primary-700 dark:text-primary-400 font-medium'>".e($eval['aksiyon_onerisi']).'</p></div>'
                            .'</div>'
                        );
                    })
                    ->action(function (RehberGeriBildirimi $r): void {
                        $r->update(['incelendi' => true]);
                        Notification::make()->title('Geri bildirim incelendi olarak işaretlendi')->success()->send();
                    }),

                Action::make('islemeGit')
                    ->label('İçeriği Düzenle')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('primary')
                    ->visible(fn (RehberGeriBildirimi $r): bool => $r->temsilcilik_islemi_id !== null)
                    ->url(fn (RehberGeriBildirimi $r): string => TemsilcilikIslemiResource::getUrl('edit', ['record' => $r->temsilcilik_islemi_id])),

                Action::make('durumDegistir')
                    ->label(fn (RehberGeriBildirimi $r): string => $r->incelendi ? 'Beklemeye Al' : 'İncelendi Yap')
                    ->icon(fn (RehberGeriBildirimi $r): string => $r->incelendi ? 'heroicon-o-arrow-path' : 'heroicon-o-check')
                    ->color(fn (RehberGeriBildirimi $r): string => $r->incelendi ? 'gray' : 'success')
                    ->action(function (RehberGeriBildirimi $r): void {
                        $yeniDurum = ! $r->incelendi;
                        $r->update(['incelendi' => $yeniDurum]);
                        Notification::make()
                            ->title($yeniDurum ? 'Bildirim incelendi olarak işaretlendi' : 'Bildirim bekliyor durumuna alındı')
                            ->success()
                            ->send();
                    }),

                ActionGroup::make([
                    Action::make('resmiKaynak')
                        ->label('Resmî Kaynak')
                        ->icon(Heroicon::OutlinedGlobeAlt)
                        ->color('gray')
                        ->visible(fn (RehberGeriBildirimi $r): bool => filled($r->islem?->resmi_kaynak_url))
                        ->url(fn (RehberGeriBildirimi $r): string => (string) $r->islem->resmi_kaynak_url)
                        ->openUrlInNewTab(),

                    Action::make('temsilcilikGit')
                        ->label('Temsilciliği Düzenle')
                        ->icon(Heroicon::OutlinedBuildingLibrary)
                        ->color('gray')
                        ->visible(fn (RehberGeriBildirimi $r): bool => $r->islem?->temsilcilik_id !== null)
                        ->url(fn (RehberGeriBildirimi $r): string => TemsilcilikResource::getUrl('edit', ['record' => $r->islem->temsilcilik_id])),

                    DeleteAction::make(),
                ]),
            ])
            ->emptyStateHeading('Henüz Geri Bildirim Bulunmuyor')
            ->emptyStateDescription('Yurt dışındaki vatandaşlarımız konsolosluk işlemlerinde "Bilgi güncel değil", "Hata var" veya "Önerim var" dediğinde bildirimler anlık olarak bu ekrana düşer.')
            ->emptyStateIcon(Heroicon::OutlinedChatBubbleLeftRight)
            ->emptyStateActions([
                Action::make('islemlereGit')
                    ->label('İşlem İçeriklerini İncele')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('primary')
                    ->url(fn (): string => TemsilcilikIslemiResource::getUrl('index')),
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
