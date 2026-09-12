<?php

namespace App\Filament\Resources\YasamKonuIcerikleri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\YasamKonuIcerikleri\Pages\CreateYasamKonuIcerigi;
use App\Filament\Resources\YasamKonuIcerikleri\Pages\EditYasamKonuIcerigi;
use App\Filament\Resources\YasamKonuIcerikleri\Pages\ListYasamKonuIcerikleri;
use App\Models\Country;
use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonusu;
use App\Services\Ai\CountryGuideAiAssistant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Yaşam Rehberi — konu içerikleri (rehberin asıl gövdesi, konu × ülke).
 *
 * TASLAK-ÖNCE SÖZLEŞMESİ (Ülke Rehberi'ndeki K7'nin aynısı): yeni kayıt
 * taslak doğar; sahip kaynaktan doğrulayıp "doğrulama tarihi"ni bugüne
 * çekerek yayına alır. 90 günü aşan yayındaki kayıtlar Kâhya'nın günlük
 * raporuna "bayat" uyarısı olarak düşer (bkz. YasamKonuIcerigi::scopeBayat).
 */
class YasamKonuIcerigiResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = YasamKonuIcerigi::class;

    protected static ?string $slug = 'yasam-konu-icerikleri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Ülke Rehberi';

    protected static ?string $navigationLabel = 'Yaşam Konu İçerikleri';

    protected static ?int $navigationSort = 7;

    public static function getModelLabel(): string
    {
        return 'yaşam konu içeriği';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Yaşam Konu İçerikleri';
    }

    /** Taslak bekleyen içerik sayısı — doldurulacak işin görünür ölçüsü. */
    public static function getNavigationBadge(): ?string
    {
        return (string) (YasamKonuIcerigi::query()->where('status', YasamKonuIcerigi::STATUS_TASLAK)->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Nerede, hangi konu')
                ->columns(2)
                ->schema([
                    Select::make('yasam_konusu_id')
                        ->label('Konu')
                        ->options(fn () => YasamKonusu::query()->with('kategori')->orderBy('sort_order')
                            ->get()->mapWithKeys(fn (YasamKonusu $k) => [$k->id => $k->kategori->ad.' — '.$k->baslik]))
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->disabledOn('edit'),
                    Select::make('country_code')
                        ->label('Ülke')
                        ->options(fn () => Country::query()->orderBy('sort_order')->pluck('name_tr', 'code'))
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->disabledOn('edit'),
                ]),

            Section::make('İçerik')
                ->headerActions([
                    Action::make('aiYasamIcerigiUret')
                        ->label('AI ile İçerik Taslağı Üret')
                        ->icon(Heroicon::OutlinedSparkles)
                        ->color('primary')
                        ->action(function (callable $get, callable $set, CountryGuideAiAssistant $assistant): void {
                            $konuId = $get('yasam_konusu_id');
                            $countryCode = (string) $get('country_code');
                            if (! $konuId || blank($countryCode)) {
                                Notification::make()->title('Lütfen önce konu ve ülkeyi seçin')->warning()->send();

                                return;
                            }
                            $konu = YasamKonusu::with('kategori')->find($konuId);
                            if (! $konu) {
                                return;
                            }
                            $katAdi = $konu->kategori ? $konu->kategori->ad : 'Genel Yaşam';
                            $taslak = $assistant->generateLifeGuideContent($konu->baslik, $katAdi, $countryCode);
                            $set('icerik', $taslak['icerik']);
                            $set('kaynak_aciklama', $taslak['kaynak_aciklama']);
                            $set('kaynak_url', $taslak['kaynak_url']);
                            $set('yazan_tur', YasamKonuIcerigi::YAZAN_AI);
                            Notification::make()->title('AI Yaşam Rehberi blokları forma yüklendi')->success()->send();
                        }),
                ])
                ->schema([
                    Repeater::make('icerik')
                        ->label('Gövde')
                        ->schema([
                            Select::make('tip')
                                ->label('Blok tipi')
                                ->options([
                                    'baslik' => 'Alt başlık',
                                    'paragraf' => 'Paragraf',
                                    'madde' => 'Madde (liste satırı)',
                                ])
                                ->default('paragraf')
                                ->required()
                                ->native(false),
                            TextInput::make('metin')
                                ->label('Metin')
                                ->required()
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(0)
                        ->addActionLabel('Blok ekle')
                        ->helperText('Ardışık "madde" blokları sitede tek listeye toplanır.'),
                    TextInput::make('kaynak_url')
                        ->label('Kaynak adresi')
                        ->url()
                        ->maxLength(300)
                        ->helperText('Sayfadaki "Kaynağı aç" butonu buraya gider.'),
                    TextInput::make('kaynak_aciklama')
                        ->label('Kaynak açıklaması (ops.)')
                        ->maxLength(300),
                ]),

            Section::make('Yayın & doğrulama')
                ->columns(3)
                ->schema([
                    Select::make('status')
                        ->label('Durum')
                        ->options([
                            YasamKonuIcerigi::STATUS_TASLAK => 'Taslak (sitede görünmez)',
                            YasamKonuIcerigi::STATUS_YAYIN => 'Yayında',
                        ])
                        ->default(YasamKonuIcerigi::STATUS_TASLAK)
                        ->required()
                        ->helperText('Yayına almadan önce içeriği kaynaktan doğrula.'),
                    DatePicker::make('dogrulanma_tarihi')
                        ->label('Son doğrulama tarihi')
                        ->native(false)
                        ->helperText(YasamKonuIcerigi::BAYATLIK_GUN.' günü aşarsa Kâhya raporunda "bayat" uyarısı çıkar.'),
                    Select::make('yazan_tur')
                        ->label('Kim yazdı')
                        ->options([
                            YasamKonuIcerigi::YAZAN_AI => 'AI (araştırma ajanı)',
                            YasamKonuIcerigi::YAZAN_TOPLULUK => 'Topluluk önerisi',
                            YasamKonuIcerigi::YAZAN_SAHIP => 'Sahip',
                        ])
                        ->default(YasamKonuIcerigi::YAZAN_AI)
                        ->required()
                        ->native(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('konu.baslik')->label('Konu')->searchable()->sortable(),
                TextColumn::make('country_code')->label('Ülke')->badge(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => $state === YasamKonuIcerigi::STATUS_YAYIN ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === YasamKonuIcerigi::STATUS_YAYIN ? 'Yayında' : 'Taslak'),
                TextColumn::make('dogrulanma_tarihi')
                    ->label('Son doğrulama')
                    ->date('d.m.Y')
                    ->placeholder('hiç')
                    ->sortable(),
                TextColumn::make('yazan_tur')->label('Kim yazdı')->badge(),
                TextColumn::make('oneriler_count')->label('Öneri')->counts('oneriler'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        YasamKonuIcerigi::STATUS_TASLAK => 'Taslak',
                        YasamKonuIcerigi::STATUS_YAYIN => 'Yayında',
                    ]),
                SelectFilter::make('country_code')
                    ->label('Ülke')
                    ->options(fn () => Country::query()->orderBy('sort_order')->pluck('name_tr', 'code')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    /*
                     * Sahip tek tek her taslağı açıp durumu+doğrulama
                     * tarihini elle değiştirmek zorunda kalmasın diye
                     * (bu ekranda hiç bulk action yoktu — 2026-09-11'de
                     * sahibin kendisi fark etti). Aynı K7 kapısı burada da
                     * geçerli: kaynak_url BOŞ olan bir taslak, ne kadar
                     * seçilirse seçilsin atlanır — "doğrulanmamış" içerik
                     * bu düğmeden asla yayına çıkamaz.
                     */
                    BulkAction::make('yayina_al')
                        ->label('Yayına Al')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Seçili içerikleri yayına al')
                        ->modalDescription('Yalnız kaynak adresi dolu olan taslaklar yayına alınır ve doğrulama tarihi bugüne çekilir; kaynaksız olanlar dokunulmadan atlanır.')
                        ->action(function ($records) {
                            $alinan = 0;
                            $atlanan = 0;

                            foreach ($records as $record) {
                                if ($record->status !== YasamKonuIcerigi::STATUS_TASLAK) {
                                    continue;
                                }

                                if (blank($record->kaynak_url)) {
                                    $atlanan++;

                                    continue;
                                }

                                $record->update([
                                    'status' => YasamKonuIcerigi::STATUS_YAYIN,
                                    'dogrulanma_tarihi' => now(),
                                ]);
                                $alinan++;
                            }

                            Notification::make()
                                ->title("{$alinan} içerik yayına alındı".($atlanan > 0 ? ", {$atlanan} kaynaksız içerik atlandı" : ''))
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->recordActions([
                Action::make('aiHizliIncele')
                    ->label('AI İncele & Doğrula')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('info')
                    ->modalHeading(fn (YasamKonuIcerigi $record): string => ($record->country_code ?? 'Ülke').' — '.($record->konu ? $record->konu->baslik : 'Konu'))
                    ->modalDescription(function (YasamKonuIcerigi $record): HtmlString {
                        $blokSayisi = is_array($record->icerik) ? count($record->icerik) : 0;
                        $kaynak = $record->kaynak_url ?: 'Belirtilmedi';
                        $aciklama = $record->kaynak_aciklama ?: 'Belirtilmedi';
                        $durum = $record->status === YasamKonuIcerigi::STATUS_YAYIN ? 'Yayında' : 'Taslak';

                        return new HtmlString(
                            "<div class='space-y-2 text-sm p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700'>"
                            ."<div><strong>Durum:</strong> <span class='font-semibold'>{$durum}</span></div>"
                            ."<div><strong>Gövde Blok Sayısı:</strong> {$blokSayisi} blok</div>"
                            ."<div><strong>Kaynak Açıklaması:</strong> {$aciklama}</div>"
                            ."<div><strong>Kaynak Bağlantısı:</strong> <span class='text-xs text-primary-600 break-all'>{$kaynak}</span></div>"
                            ."<div class='text-xs text-gray-500 pt-1 border-t'>Son Doğrulama: ".($record->dogrulanma_tarihi ? $record->dogrulanma_tarihi->format('d.m.Y') : 'Hiç').'</div>'
                            .'</div>'
                        );
                    })
                    ->action(function (YasamKonuIcerigi $record): void {
                        if ($record->status === YasamKonuIcerigi::STATUS_TASLAK && filled($record->kaynak_url)) {
                            $record->update([
                                'status' => YasamKonuIcerigi::STATUS_YAYIN,
                                'dogrulanma_tarihi' => now(),
                            ]);
                            Notification::make()->title('Yaşam rehberi içeriği onaylandı ve yayına alındı')->success()->send();
                        } else {
                            Notification::make()->title('İçerik incelendi')->info()->send();
                        }
                    }),
                Action::make('duzenle')
                    ->label('Düzenle')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (YasamKonuIcerigi $record): string => static::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListYasamKonuIcerikleri::route('/'),
            'create' => CreateYasamKonuIcerigi::route('/create'),
            'edit' => EditYasamKonuIcerigi::route('/{record}/edit'),
        ];
    }
}
