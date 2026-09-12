<?php

namespace App\Filament\Resources\TemsilcilikIslemleri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\IslemTurleri\IslemTuruResource;
use App\Filament\Resources\RehberGeriBildirimleri\RehberGeriBildirimiResource;
use App\Filament\Resources\TemsilcilikIslemleri\Pages\CreateTemsilcilikIslemi;
use App\Filament\Resources\TemsilcilikIslemleri\Pages\EditTemsilcilikIslemi;
use App\Filament\Resources\TemsilcilikIslemleri\Pages\ListTemsilcilikIslemleri;
use App\Filament\Resources\Temsilcilikler\TemsilcilikResource;
use App\Models\Country;
use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Services\Ai\CountryGuideAiAssistant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Ülke Rehberi — işlem içerikleri (rehberin asıl gövdesi).
 *
 * TASLAK-ÖNCE SÖZLEŞMESİ (tasarım K7): yeni kayıt taslak doğar; sahip
 * resmî kaynaktan doğrulayıp "doğrulama tarihi"ni bugüne çekerek yayına
 * alır. Doğrulama tarihi 90 günü aşan yayındaki kayıtlar Kâhya'nın günlük
 * raporuna "bayat rehber" uyarısı olarak düşer.
 */
class TemsilcilikIslemiResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = TemsilcilikIslemi::class;

    protected static ?string $slug = 'temsilcilik-islemleri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Ülke Rehberi';

    protected static ?string $navigationLabel = 'İşlem İçerikleri';

    protected static ?int $navigationSort = 3;

    /**
     * Evrensel/iskelet seeder'ların bıraktığı jenerik varsayılan adres —
     * yol içermeyen çıplak domain. "Yayına Al" toplu işlemi bunu asla
     * doğrulanmış kaynak saymaz; yalnız bundan FARKLI, dolu bir adres
     * geçer (bkz. bu projede daha önce elle yapılan aynı ayrım).
     */
    private const JENERIK_KAYNAK_URL = 'https://www.konsolosluk.gov.tr';

    public static function getModelLabel(): string
    {
        return 'İşlem İçeriği';
    }

    public static function getPluralModelLabel(): string
    {
        return 'İşlem İçerikleri';
    }

    /** Taslak bekleyen içerik sayısı — doldurulacak işin görünür ölçüsü. */
    public static function getNavigationBadge(): ?string
    {
        return (string) (TemsilcilikIslemi::query()->where('status', TemsilcilikIslemi::STATUS_TASLAK)->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Doğrulama bekleyen taslak işlem içeriği sayısı';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Nerede, hangi işlem')
                ->columns(2)
                ->schema([
                    Select::make('temsilcilik_id')
                        ->label('Temsilcilik')
                        ->options(fn () => Temsilcilik::query()->orderBy('country_code')->orderBy('sort_order')
                            ->get()->mapWithKeys(fn (Temsilcilik $t) => [$t->id => $t->country_code.' — '.$t->ad]))
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->disabledOn('edit'),
                    Select::make('islem_turu_id')
                        ->label('İşlem türü')
                        ->options(fn () => IslemTuru::query()->orderBy('sort_order')->pluck('ad', 'id'))
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->disabledOn('edit'),
                ]),

            Section::make('İçerik')
                ->headerActions([
                    Action::make('aiRehberUret')
                        ->label('AI ile Taslak Üret')
                        ->icon(Heroicon::OutlinedSparkles)
                        ->color('primary')
                        ->action(function (callable $get, callable $set, CountryGuideAiAssistant $assistant): void {
                            $temsilcilikId = $get('temsilcilik_id');
                            $islemTuruId = $get('islem_turu_id');
                            if (! $temsilcilikId || ! $islemTuruId) {
                                Notification::make()->title('Lütfen önce temsilcilik ve işlem türünü seçin')->warning()->send();

                                return;
                            }
                            $temsilcilik = Temsilcilik::find($temsilcilikId);
                            $islemTuru = IslemTuru::find($islemTuruId);
                            if (! $temsilcilik || ! $islemTuru) {
                                return;
                            }
                            $taslak = $assistant->generateConsularContent($temsilcilik->ad, $islemTuru->ad, $temsilcilik->country_code);
                            $set('evraklar', $taslak['evraklar']);
                            $set('sure_metni', $taslak['sure_metni']);
                            $set('ucret_metni', $taslak['ucret_metni']);
                            $set('notlar', $taslak['notlar']);
                            $set('resmi_kaynak_url', $taslak['resmi_kaynak_url']);
                            Notification::make()->title('AI taslak içeriği forma yüklendi')->success()->send();
                        }),
                ])
                ->schema([
                    Repeater::make('evraklar')
                        ->label('Gerekli evraklar')
                        ->schema([
                            TextInput::make('ad')->label('Evrak')->required()->maxLength(200),
                            TextInput::make('not')->label('Not (ops.)')->maxLength(300),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(0)
                        ->addActionLabel('Evrak ekle'),
                    TextInput::make('sure_metni')
                        ->label('Süre')
                        ->placeholder('örn. aynı gün / 2-4 hafta')
                        ->maxLength(200),
                    TextInput::make('ucret_metni')
                        ->label('Ücret')
                        ->placeholder('örn. 48,52 € (2026 harç tarifesi)')
                        ->maxLength(200),
                    Textarea::make('notlar')
                        ->label('Bilmen gerekenler (serbest metin)')
                        ->rows(4)
                        ->helperText('Randevu gerekip gerekmediği, sık yapılan hatalar, ipuçları.'),
                    TextInput::make('resmi_kaynak_url')
                        ->label('Resmî kaynak adresi')
                        ->url()
                        ->required()
                        ->maxLength(300)
                        ->helperText('Sayfadaki birincil buton buraya gider — bilgiyi doğruladığın resmî sayfa.'),
                ]),

            Section::make('Yayın & doğrulama')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('Durum')
                        ->options([
                            TemsilcilikIslemi::STATUS_TASLAK => 'Taslak (sitede görünmez)',
                            TemsilcilikIslemi::STATUS_YAYIN => 'Yayında',
                        ])
                        ->default(TemsilcilikIslemi::STATUS_TASLAK)
                        ->required()
                        ->helperText('Yayına almadan önce içeriği resmî kaynaktan doğrula.'),
                    DatePicker::make('dogrulanma_tarihi')
                        ->label('Son doğrulama tarihi')
                        ->native(false)
                        ->helperText('Ziyaretçiye gösterilir. '.TemsilcilikIslemi::BAYATLIK_GUN.' günü aşarsa Kâhya raporunda "bayat" uyarısı çıkar.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['temsilcilik.country', 'islemTuru'])->withCount('geriBildirimler'))
            ->columns([
                TextColumn::make('temsilcilik.ad')
                    ->label('Temsilcilik')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->iconColor('info')
                    ->description(function (TemsilcilikIslemi $record): ?string {
                        $t = $record->temsilcilik;
                        if (! $t) {
                            return null;
                        }
                        $c = $t->country;
                        $flag = $c && $c->emoji ? $c->emoji.' ' : '';

                        return $flag.$t->country_code.($t->sehir ? ' • '.$t->sehir : '');
                    }),

                TextColumn::make('islemTuru.ad')
                    ->label('İşlem Türü')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(fn (TemsilcilikIslemi $record): BackedEnum => $record->islemTuru
                        ? IslemTuruResource::getIconForRecord($record->islemTuru)
                        : Heroicon::OutlinedClipboardDocumentList
                    )
                    ->iconColor('primary')
                    ->description(fn (TemsilcilikIslemi $record): ?string => $record->sure_metni ? '⏱ '.$record->sure_metni : null),

                TextColumn::make('ucret_metni')
                    ->label('Ücret / Harç')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Belirtilmedi')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => $state === TemsilcilikIslemi::STATUS_YAYIN ? 'success' : 'warning')
                    ->icon(fn (string $state): BackedEnum => $state === TemsilcilikIslemi::STATUS_YAYIN ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedClock)
                    ->formatStateUsing(fn (string $state): string => $state === TemsilcilikIslemi::STATUS_YAYIN ? 'Yayında' : 'Taslak'),

                TextColumn::make('dogrulanma_tarihi')
                    ->label('Son Doğrulama')
                    ->date('d.m.Y')
                    ->placeholder('Hiç')
                    ->description(function (TemsilcilikIslemi $record): ?string {
                        if ($record->status !== TemsilcilikIslemi::STATUS_YAYIN) {
                            return null;
                        }
                        if (! $record->dogrulanma_tarihi || $record->dogrulanma_tarihi->diffInDays(now()) > TemsilcilikIslemi::BAYATLIK_GUN) {
                            return '⚠️ Bayat içerik';
                        }

                        return '✓ Güncel';
                    })
                    ->sortable(),

                TextColumn::make('resmi_kaynak_url')
                    ->label('Resmî Kaynak')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->formatStateUsing(function (?string $state): string {
                        if (blank($state)) {
                            return 'Kaynak yok';
                        }
                        $host = parse_url($state, PHP_URL_HOST);

                        return $host ? str_replace('www.', '', (string) $host) : 'Kaynak';
                    })
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) && $state !== self::JENERIK_KAYNAK_URL ? 'info' : 'gray')
                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                    ->openUrlInNewTab()
                    ->tooltip('Resmi doğrulama kaynağını yeni sekmede aç')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('geri_bildirimler_count')
                    ->label('Geri Bildirim')
                    ->counts('geriBildirimler')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->url(fn (TemsilcilikIslemi $record): string => RehberGeriBildirimiResource::getUrl('index', [
                        'tableFilters' => ['incelendi' => ['value' => '0']],
                    ]))
                    ->tooltip('Geri bildirimleri incele')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('country_code')
                    ->label('Ülke')
                    ->options(fn () => Country::query()->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn (Country $c) => [$c->code => ($c->emoji ? $c->emoji.' ' : '').$c->name_tr])
                    )
                    ->query(fn ($query, $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('temsilcilik', fn ($q) => $q->where('country_code', $data['value']))
                        : $query
                    )
                    ->searchable(),

                SelectFilter::make('islem_turu_id')
                    ->label('İşlem Türü')
                    ->options(fn () => IslemTuru::query()->orderBy('sort_order')->pluck('ad', 'id'))
                    ->searchable(),

                SelectFilter::make('temsilcilik_id')
                    ->label('Temsilcilik')
                    ->options(fn () => Temsilcilik::query()->orderBy('country_code')->orderBy('sort_order')->pluck('ad', 'id'))
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        TemsilcilikIslemi::STATUS_TASLAK => 'Taslak',
                        TemsilcilikIslemi::STATUS_YAYIN => 'Yayında',
                    ]),

                Filter::make('bayat_icerikler')
                    ->label('⚠️ Bayat İçerikler (>90 gün)')
                    ->query(fn ($query) => $query->where('status', TemsilcilikIslemi::STATUS_YAYIN)->where(function ($q) {
                        $q->whereNull('dogrulanma_tarihi')
                            ->orWhere('dogrulanma_tarihi', '<', now()->subDays(TemsilcilikIslemi::BAYATLIK_GUN));
                    })),

                Filter::make('geri_bildirimli')
                    ->label('Geri Bildirim Alanlar')
                    ->query(fn ($query) => $query->has('geriBildirimler')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('yayina_al')
                        ->label('Yayına Al')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Seçili işlem içeriklerini yayına al')
                        ->modalDescription('Yalnız gerçek (jenerik olmayan) bir kaynak adresi olan taslaklar yayına alınır ve doğrulama tarihi bugüne çekilir; kaynaksız/jenerik olanlar dokunulmadan atlanır.')
                        ->action(function ($records) {
                            $alinan = 0;
                            $atlanan = 0;

                            foreach ($records as $record) {
                                if ($record->status !== TemsilcilikIslemi::STATUS_TASLAK) {
                                    continue;
                                }

                                if (blank($record->resmi_kaynak_url) || $record->resmi_kaynak_url === self::JENERIK_KAYNAK_URL) {
                                    $atlanan++;

                                    continue;
                                }

                                $record->update([
                                    'status' => TemsilcilikIslemi::STATUS_YAYIN,
                                    'dogrulanma_tarihi' => now(),
                                ]);
                                $alinan++;
                            }

                            Notification::make()
                                ->title("{$alinan} işlem içeriği yayına alındı".($atlanan > 0 ? ", {$atlanan} kaynaksız/jenerik içerik atlandı" : ''))
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('taslaga_al')
                        ->label('Taslağa Al')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Seçili içerikleri taslağa çek')
                        ->modalDescription('Seçilen içerikler yayından kaldırılır ve taslak durumuna getirilir.')
                        ->action(function ($records) {
                            $records->each->update(['status' => TemsilcilikIslemi::STATUS_TASLAK]);
                            Notification::make()->title('Seçili içerikler taslağa alındı')->warning()->send();
                        }),

                    BulkAction::make('tarihi_guncelle')
                        ->label('Doğrulama Tarihini Bugün Yap')
                        ->icon('heroicon-o-calendar-days')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Son doğrulama tarihini güncelle')
                        ->modalDescription('Seçili içeriklerin son doğrulama tarihi bugünün tarihi olarak güncellenir.')
                        ->action(function ($records) {
                            $records->each->update(['dogrulanma_tarihi' => now()]);
                            Notification::make()->title('Doğrulama tarihleri güncellendi')->success()->send();
                        }),
                ]),
            ])
            ->recordActions([
                Action::make('canli_sayfa')
                    ->label('Canlı Sayfa')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->tooltip('Canlı rehber sayfasını yeni sekmede aç')
                    ->visible(fn (TemsilcilikIslemi $record): bool => $record->status === TemsilcilikIslemi::STATUS_YAYIN && $record->temsilcilik !== null && $record->islemTuru !== null)
                    ->url(fn (TemsilcilikIslemi $record): string => url('/'.strtolower($record->temsilcilik->country_code).'/'.$record->temsilcilik->slug.'/'.$record->islemTuru->slug))
                    ->openUrlInNewTab(),

                Action::make('aiHizliIncele')
                    ->label('AI İncele & Doğrula')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('primary')
                    ->modalHeading(fn (TemsilcilikIslemi $record): string => ($record->temsilcilik ? $record->temsilcilik->ad : 'Temsilcilik').' — '.($record->islemTuru ? $record->islemTuru->ad : 'İşlem'))
                    ->modalDescription(function (TemsilcilikIslemi $record): HtmlString {
                        $evrakSayisi = count($record->evraklar);
                        $sure = $record->sure_metni ?: 'Belirtilmedi';
                        $ucret = $record->ucret_metni ?: 'Belirtilmedi';
                        $kaynak = $record->resmi_kaynak_url ?: 'Belirtilmedi';
                        $durum = $record->status === TemsilcilikIslemi::STATUS_YAYIN ? 'Yayında' : 'Taslak';

                        return new HtmlString(
                            "<div class='space-y-2 text-sm p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'>"
                            ."<div><strong>Durum:</strong> <span class='font-semibold'>{$durum}</span></div>"
                            ."<div><strong>Kayıtlı Evrak Sayısı:</strong> {$evrakSayisi} adet</div>"
                            ."<div><strong>Süre & Ücret:</strong> {$sure} / {$ucret}</div>"
                            ."<div><strong>Resmî Kaynak:</strong> <span class='text-xs text-primary-700 dark:text-primary-400 break-all'>{$kaynak}</span></div>"
                            ."<div class='text-xs text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-200 dark:border-gray-700'>Son Doğrulama: ".($record->dogrulanma_tarihi ? $record->dogrulanma_tarihi->format('d.m.Y') : 'Hiç').'</div>'
                            .'</div>'
                        );
                    })
                    ->action(function (TemsilcilikIslemi $record): void {
                        if ($record->status === TemsilcilikIslemi::STATUS_TASLAK && filled($record->resmi_kaynak_url) && $record->resmi_kaynak_url !== self::JENERIK_KAYNAK_URL) {
                            $record->update([
                                'status' => TemsilcilikIslemi::STATUS_YAYIN,
                                'dogrulanma_tarihi' => now(),
                            ]);
                            Notification::make()->title('İçerik onaylandı ve yayına alındı')->success()->send();
                        } else {
                            Notification::make()->title('İçerik incelendi')->info()->send();
                        }
                    }),

                Action::make('duzenle')
                    ->label('Düzenle')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('primary')
                    ->url(fn (TemsilcilikIslemi $record): string => static::getUrl('edit', ['record' => $record])),

                ActionGroup::make([
                    Action::make('resmi_kaynak')
                        ->label('Resmî Kaynağa Git')
                        ->icon(Heroicon::OutlinedGlobeAlt)
                        ->color('gray')
                        ->visible(fn (TemsilcilikIslemi $record): bool => filled($record->resmi_kaynak_url))
                        ->url(fn (TemsilcilikIslemi $record): string => (string) $record->resmi_kaynak_url)
                        ->openUrlInNewTab(),

                    Action::make('temsilcilik_git')
                        ->label('Temsilciliği Düzenle')
                        ->icon(Heroicon::OutlinedBuildingLibrary)
                        ->color('gray')
                        ->url(fn (TemsilcilikIslemi $record): string => TemsilcilikResource::getUrl('edit', ['record' => $record->temsilcilik_id])),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTemsilcilikIslemleri::route('/'),
            'create' => CreateTemsilcilikIslemi::route('/create'),
            'edit' => EditTemsilcilikIslemi::route('/{record}/edit'),
        ];
    }
}
