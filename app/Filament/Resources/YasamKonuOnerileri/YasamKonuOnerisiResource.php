<?php

namespace App\Filament\Resources\YasamKonuOnerileri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\YasamKonuIcerikleri\YasamKonuIcerigiResource;
use App\Filament\Resources\YasamKonuOnerileri\Pages\ListYasamKonuOnerileri;
use App\Models\Country;
use App\Models\YasamKategorisi;
use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonuOnerisi;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Yaşam Rehberi — topluluk düzeltme önerileri kuyruğu.
 *
 * Gurbetçi vatandaşlarımız yayındaki yaşam rehberi konularına güncelleme,
 * ek bilgi veya kaynak teyidi önerdiğinde kayıtlar bu ekranda toplanır.
 * Yöneticiler AI asistanı ile önerileri hızla değerlendirebilir, tek tıkla
 * onaylayabilir veya ilgili içeriğe giderek rehberi güncelleyebilir.
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
        return 'Yaşam Konu Önerisi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Yaşam Konu Önerileri';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = YasamKonuOnerisi::query()->bekleyen()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['icerik.konu.kategori', 'icerik.country', 'kullanici']))
            ->columns([
                TextColumn::make('icerik.konu.baslik')
                    ->label('Yaşam Konusu')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->weight('bold')
                    ->description(function (YasamKonuOnerisi $r): ?string {
                        $kategori = $r->icerik?->konu?->kategori;
                        if (! $kategori) {
                            return null;
                        }

                        return ($kategori->ikon ? $kategori->ikon.' ' : '').$kategori->ad;
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('icerik.country_code')
                    ->label('Ülke')
                    ->badge()
                    ->formatStateUsing(function (YasamKonuOnerisi $r): string {
                        $country = $r->icerik?->country;
                        $code = $r->icerik ? (string) $r->icerik->country_code : '';
                        $emoji = $country?->emoji ? $country->emoji.' ' : '';

                        return $emoji.$code;
                    })
                    ->description(fn (YasamKonuOnerisi $r): ?string => $r->icerik?->country?->name_tr)
                    ->sortable(),

                TextColumn::make('kullanici.name')
                    ->label('Öneren Üye')
                    ->icon(Heroicon::OutlinedUser)
                    ->description(fn (YasamKonuOnerisi $r): ?string => $r->kullanici?->email)
                    ->placeholder('Anonim / Ziyaretçi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('onerilen_metin')
                    ->label('Öneri Özeti')
                    ->wrap()
                    ->limit(130)
                    ->tooltip(fn (YasamKonuOnerisi $r): string => (string) $r->onerilen_metin)
                    ->description(function (YasamKonuOnerisi $r): ?string {
                        if (filled($r->kaynak_url)) {
                            $host = parse_url((string) $r->kaynak_url, PHP_URL_HOST);

                            return '🔗 Kaynak: '.($host ?: 'Resmî Bağlantı');
                        }

                        return null;
                    })
                    ->searchable(),

                TextColumn::make('kaynak_url')
                    ->label('Resmî Kaynak')
                    ->badge()
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->color('gray')
                    ->placeholder('—')
                    ->formatStateUsing(function (?string $state): ?string {
                        if (! filled($state)) {
                            return null;
                        }
                        $host = parse_url($state, PHP_URL_HOST);

                        return $host ?: 'Resmî Site';
                    })
                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                    ->openUrlInNewTab(),

                TextColumn::make('durum')
                    ->label('Durum')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        YasamKonuOnerisi::DURUM_ONAYLANDI => 'heroicon-o-check-circle',
                        YasamKonuOnerisi::DURUM_REDDEDILDI => 'heroicon-o-x-circle',
                        default => 'heroicon-o-clock',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        YasamKonuOnerisi::DURUM_ONAYLANDI => 'success',
                        YasamKonuOnerisi::DURUM_REDDEDILDI => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (YasamKonuOnerisi $r): string => $r->durumEtiketi())
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (YasamKonuOnerisi $r): ?string => $r->created_at?->diffForHumans())
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('durum')
                    ->label('Durum')
                    ->options([
                        YasamKonuOnerisi::DURUM_BEKLIYOR => 'Bekliyor',
                        YasamKonuOnerisi::DURUM_ONAYLANDI => 'Onaylandı',
                        YasamKonuOnerisi::DURUM_REDDEDILDI => 'Reddedildi',
                    ]),

                SelectFilter::make('country_code')
                    ->label('Ülke')
                    ->options(fn () => Country::query()->orderBy('name_tr')->get()->mapWithKeys(fn (Country $c) => [
                        $c->code => ($c->emoji ? $c->emoji.' ' : '').$c->name_tr.' ('.$c->code.')',
                    ]))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('icerik', fn ($q) => $q->where('country_code', $data['value']))
                        : $query
                    )
                    ->searchable(),

                SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->options(fn () => YasamKategorisi::query()->orderBy('sort_order')->get()->mapWithKeys(fn (YasamKategorisi $k) => [
                        $k->id => ($k->ikon ? $k->ikon.' ' : '').$k->ad,
                    ]))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('icerik.konu', fn ($q) => $q->where('yasam_kategorisi_id', $data['value']))
                        : $query
                    )
                    ->searchable(),

                SelectFilter::make('yasam_konu_icerigi_id')
                    ->label('Konu İçeriği')
                    ->options(fn () => YasamKonuIcerigi::with(['konu', 'country'])->get()->mapWithKeys(function (YasamKonuIcerigi $i) {
                        $baslik = $i->konu ? $i->konu->baslik : 'İçerik #'.$i->id;
                        $bayrak = $i->country?->emoji ? $i->country->emoji.' ' : '';

                        return [$i->id => $bayrak.$i->country_code.' • '.$baslik];
                    }))
                    ->searchable(),

                Filter::make('kaynakli_oneriler')
                    ->label('Yalnızca Resmî Kaynaklılar')
                    ->query(fn ($query) => $query->whereNotNull('kaynak_url')->where('kaynak_url', '!=', '')),

                Filter::make('bekleyenler')
                    ->label('Yalnızca İnceleme Bekleyenler')
                    ->query(fn ($query) => $query->where('durum', YasamKonuOnerisi::DURUM_BEKLIYOR)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('toplu_onayla')
                        ->label('Seçilenleri Onayla')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Seçili önerileri onayla')
                        ->modalDescription('Seçtiğiniz tüm öneriler onaylandı durumuna getirilecektir.')
                        ->action(function ($records) {
                            $records->each->update(['durum' => YasamKonuOnerisi::DURUM_ONAYLANDI]);
                            Notification::make()->title('Seçili öneriler onaylandı olarak işaretlendi')->success()->send();
                        }),

                    BulkAction::make('toplu_reddet')
                        ->label('Seçilenleri Reddet')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Seçili önerileri reddet')
                        ->modalDescription('Seçtiğiniz tüm öneriler reddedildi olarak işaretlenecektir.')
                        ->action(function ($records) {
                            $records->each->update(['durum' => YasamKonuOnerisi::DURUM_REDDEDILDI]);
                            Notification::make()->title('Seçili öneriler reddedildi olarak işaretlendi')->warning()->send();
                        }),

                    BulkAction::make('toplu_beklemeye_al')
                        ->label('Bekliyor Olarak İşaretle')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['durum' => YasamKonuOnerisi::DURUM_BEKLIYOR]);
                            Notification::make()->title('Seçili öneriler bekliyor durumuna alındı')->info()->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordActions([
                Action::make('canliSayfa')
                    ->label('Canlı Sayfa')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->tooltip('Canlı yaşam rehberi sayfasını yeni sekmede aç')
                    ->visible(fn (YasamKonuOnerisi $r): bool => $r->icerik !== null && $r->icerik->konu !== null && $r->icerik->konu->kategori !== null)
                    ->url(fn (YasamKonuOnerisi $r): string => url('/'.strtolower((string) $r->icerik->country_code).'/yasam/'.$r->icerik->konu->kategori->slug.'/'.$r->icerik->konu->slug))
                    ->openUrlInNewTab(),

                Action::make('aiOneriDegerlendir')
                    ->label('AI Değerlendir')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('primary')
                    ->modalHeading(fn (YasamKonuOnerisi $r): string => 'AI Öneri Analizi #'.$r->id)
                    ->modalDescription(function (YasamKonuOnerisi $r, CountryGuideAiAssistant $assistant): HtmlString {
                        $baslik = $r->icerik && $r->icerik->konu ? $r->icerik->konu->baslik : 'Yaşam Konusu';
                        $eval = $assistant->evaluateLifeTopicSuggestion((string) $r->onerilen_metin, $baslik);

                        $color = match ($eval['karar']) {
                            'onayla' => 'text-emerald-700 dark:text-emerald-400',
                            'reddet' => 'text-rose-700 dark:text-rose-400',
                            default => 'text-amber-700 dark:text-amber-400',
                        };

                        $blokHtml = '';
                        if (! empty($eval['onerilen_bloklar'])) {
                            $blokHtml .= "<div class='pt-2 border-t border-gray-200 dark:border-gray-700 space-y-1'><strong class='text-xs text-gray-500 dark:text-gray-400'>Önerilen Rehber Blokları:</strong><ul class='list-disc pl-4 text-xs text-gray-700 dark:text-gray-300 space-y-1'>";
                            foreach ($eval['onerilen_bloklar'] as $blok) {
                                $blokHtml .= '<li>'.e($blok['metin']).'</li>';
                            }
                            $blokHtml .= '</ul></div>';
                        }

                        $kaynakHtml = filled($r->kaynak_url)
                            ? "<div class='pt-1'><strong class='text-xs text-gray-500 dark:text-gray-400'>Kullanıcı Kaynak URL:</strong> <a href='".e($r->kaynak_url)."' target='_blank' rel='noopener noreferrer' class='text-xs text-primary-700 dark:text-primary-400 hover:underline font-mono'>".e($r->kaynak_url).'</a></div>'
                            : '';

                        return new HtmlString(
                            "<div class='space-y-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-sm'>"
                            ."<div class='flex items-center justify-between font-bold'>"
                            ."<span>Tavsiye Karar: <span class='{$color} uppercase'>".e($eval['karar']).'</span></span>'
                            ."<span class='text-xs text-gray-500 dark:text-gray-400'>Konu: ".e($baslik).'</span>'
                            .'</div>'
                            ."<div><strong class='text-xs text-gray-500 dark:text-gray-400'>AI Gerekçe:</strong><p class='text-xs mt-0.5 text-gray-800 dark:text-gray-200'>".e($eval['gerekce']).'</p></div>'
                            ."<div class='pt-2 border-t border-gray-200 dark:border-gray-700'><strong class='text-xs text-gray-500 dark:text-gray-400'>Önerilen Metin:</strong><p class='text-xs mt-0.5 font-medium text-gray-900 dark:text-gray-100'>".e($r->onerilen_metin).'</p></div>'
                            .$kaynakHtml
                            .$blokHtml
                            .'</div>'
                        );
                    })
                    ->action(function (YasamKonuOnerisi $r, CountryGuideAiAssistant $assistant): void {
                        $baslik = $r->icerik && $r->icerik->konu ? $r->icerik->konu->baslik : 'Yaşam Konusu';
                        $eval = $assistant->evaluateLifeTopicSuggestion((string) $r->onerilen_metin, $baslik);
                        if ($eval['karar'] === 'onayla') {
                            $r->update(['durum' => YasamKonuOnerisi::DURUM_ONAYLANDI]);
                            Notification::make()->title('Öneri onaylandı')->success()->send();
                        } else {
                            Notification::make()->title('Öneri incelendi')->info()->send();
                        }
                    }),

                Action::make('onayla')
                    ->label('Onayla')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (YasamKonuOnerisi $r): bool => $r->durum === YasamKonuOnerisi::DURUM_BEKLIYOR)
                    ->action(function (YasamKonuOnerisi $r) {
                        $r->update(['durum' => YasamKonuOnerisi::DURUM_ONAYLANDI]);
                        Notification::make()->title('Öneri onaylandı')->success()->send();
                    }),

                Action::make('reddet')
                    ->label('Reddet')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn (YasamKonuOnerisi $r): bool => $r->durum === YasamKonuOnerisi::DURUM_BEKLIYOR)
                    ->action(function (YasamKonuOnerisi $r) {
                        $r->update(['durum' => YasamKonuOnerisi::DURUM_REDDEDILDI]);
                        Notification::make()->title('Öneri reddedildi')->warning()->send();
                    }),

                ActionGroup::make([
                    Action::make('icerigeGit')
                        ->label('İçeriği Düzenle')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->color('primary')
                        ->url(fn (YasamKonuOnerisi $r): string => YasamKonuIcerigiResource::getUrl('edit', ['record' => $r->yasam_konu_icerigi_id])),

                    Action::make('resmiKaynak')
                        ->label('Resmî Kaynak Bağlantısı')
                        ->icon(Heroicon::OutlinedGlobeAlt)
                        ->color('gray')
                        ->visible(fn (YasamKonuOnerisi $r): bool => filled($r->kaynak_url))
                        ->url(fn (YasamKonuOnerisi $r): string => (string) $r->kaynak_url)
                        ->openUrlInNewTab(),

                    DeleteAction::make(),
                ]),
            ])
            ->emptyStateHeading('Henüz Yaşam Konu Önerisi Bulunmuyor')
            ->emptyStateDescription('Yurt dışındaki vatandaşlarımız yaşam rehberi konularına güncelleme veya ek bilgi önerdiğinde kayıtlar anlık olarak bu ekrana düşer. Doğrulanmış önerileri inceleyip tek tıkla onaylayabilir veya içeriğe işleyebilirsiniz.')
            ->emptyStateIcon(Heroicon::OutlinedLightBulb)
            ->emptyStateActions([
                Action::make('iceriklereGit')
                    ->label('Yaşam Konu İçeriklerini İncele')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('primary')
                    ->url(fn (): string => YasamKonuIcerigiResource::getUrl('index')),
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
