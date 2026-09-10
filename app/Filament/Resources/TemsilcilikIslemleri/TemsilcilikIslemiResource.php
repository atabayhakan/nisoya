<?php

namespace App\Filament\Resources\TemsilcilikIslemleri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\TemsilcilikIslemleri\Pages\CreateTemsilcilikIslemi;
use App\Filament\Resources\TemsilcilikIslemleri\Pages\EditTemsilcilikIslemi;
use App\Filament\Resources\TemsilcilikIslemleri\Pages\ListTemsilcilikIslemleri;
use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use BackedEnum;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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
        return 'işlem içeriği';
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
            ->columns([
                TextColumn::make('temsilcilik.ad')->label('Temsilcilik')->searchable()->sortable(),
                TextColumn::make('islemTuru.ad')->label('İşlem')->searchable(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => $state === TemsilcilikIslemi::STATUS_YAYIN ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === TemsilcilikIslemi::STATUS_YAYIN ? 'Yayında' : 'Taslak'),
                TextColumn::make('dogrulanma_tarihi')
                    ->label('Son doğrulama')
                    ->date('d.m.Y')
                    ->placeholder('hiç')
                    ->sortable(),
                TextColumn::make('geri_bildirimler_count')->label('Geri bildirim')->counts('geriBildirimler'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        TemsilcilikIslemi::STATUS_TASLAK => 'Taslak',
                        TemsilcilikIslemi::STATUS_YAYIN => 'Yayında',
                    ]),
                SelectFilter::make('temsilcilik_id')
                    ->label('Temsilcilik')
                    ->options(fn () => Temsilcilik::query()->orderBy('sort_order')->pluck('ad', 'id')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    /*
                     * 1656 kayıtlık bir listede teker teker açıp durum +
                     * doğrulama tarihini elle değiştirmek gerçekçi değil
                     * (sahip 2026-09-11'de bunu fark etti — Yaşam Konu
                     * İçerikleri'nde de aynı eksik vardı, bkz. o resource).
                     * K7 kapısı burada da UI seviyesinde: jenerik/boş
                     * resmi_kaynak_url taşıyan bir taslak, seçilse bile
                     * atlanır — bu düğme "gerçekten araştırılmış" ile
                     * "iskelet doğduğu gibi kalmış" arasındaki farkı
                     * otomatik gözetir.
                     */
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
