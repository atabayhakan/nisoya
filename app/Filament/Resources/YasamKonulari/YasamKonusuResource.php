<?php

namespace App\Filament\Resources\YasamKonulari;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\YasamKategorileri\YasamKategorisiResource;
use App\Filament\Resources\YasamKonuIcerikleri\YasamKonuIcerigiResource;
use App\Filament\Resources\YasamKonulari\Pages\CreateYasamKonusu;
use App\Filament\Resources\YasamKonulari\Pages\EditYasamKonusu;
use App\Filament\Resources\YasamKonulari\Pages\ListYasamKonulari;
use App\Models\YasamKategorisi;
use App\Models\YasamKonusu;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Yaşam Rehberi — konular (bir kategori altındaki, ülkeden bağımsız
 * sorular/başlıklar — örn. "SSN'siz banka hesabı açma").
 */
class YasamKonusuResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = YasamKonusu::class;

    protected static ?string $slug = 'yasam-konulari';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Ülke Rehberi';

    protected static ?string $navigationLabel = 'Yaşam Konuları';

    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return 'Yaşam Konusu';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Yaşam Konuları';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = YasamKonusu::query()->where('is_active', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Konu Detayları')
                ->description('Kategoriye bağlı soru veya rehber başlığı')
                ->columns(2)
                ->schema([
                    Select::make('kategori_id')
                        ->label('Kategori')
                        ->options(fn () => YasamKategorisi::query()->orderBy('sort_order')->get()->mapWithKeys(fn (YasamKategorisi $k) => [
                            $k->id => ($k->ikon ? $k->ikon.' ' : '').$k->ad,
                        ]))
                        ->required()
                        ->native(false)
                        ->searchable(),
                    TextInput::make('baslik')
                        ->label('Konu Başlığı')
                        ->placeholder('örn. SSN\'siz banka hesabı açma')
                        ->required()
                        ->maxLength(160)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Kısa Ad (URL)')
                        ->required()
                        ->maxLength(100)
                        ->helperText('Adres: /{ulke}/yasam/{kategori}/{kısa-ad}'),
                    TextInput::make('sort_order')
                        ->label('Görüntülenme Sırası')
                        ->numeric()
                        ->default(0),
                    TextInput::make('kisa_aciklama')
                        ->label('Kısa Açıklama (Özet)')
                        ->placeholder('Yurtdışında yaşayan vatandaşlarımız için rehber özeti...')
                        ->maxLength(300)
                        ->columnSpanFull()
                        ->hintAction(
                            Action::make('aiAciklamaUret')
                                ->label('AI Açıklama')
                                ->icon(Heroicon::OutlinedSparkles)
                                ->tooltip('Konu başlığına göre özet açıklama üret')
                                ->action(function (callable $get, callable $set): void {
                                    $baslik = (string) $get('baslik');
                                    if (blank($baslik)) {
                                        Notification::make()->title('Lütfen önce konu başlığını girin')->warning()->send();

                                        return;
                                    }
                                    $aciklama = "Yurt dışında yaşayan vatandaşlarımız için {$baslik} süreçleri, yasal haklar ve adım adım başvuru rehberi.";
                                    $set('kisa_aciklama', $aciklama);
                                    Notification::make()->title('Açıklama taslağı üretildi')->success()->send();
                                })
                        ),
                    Toggle::make('is_active')
                        ->label('Aktif (Rehberde Göster)')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('kategori'))
            ->columns([
                TextColumn::make('kategori.ad')
                    ->label('Kategori')
                    ->icon(fn (YasamKonusu $r): BackedEnum => YasamKategorisiResource::getIconForRecord($r->kategori))
                    ->formatStateUsing(fn (YasamKonusu $r): string => $r->kategori ? ($r->kategori->ikon ? $r->kategori->ikon.' ' : '').$r->kategori->ad : 'Bilinmiyor')
                    ->description(fn (YasamKonusu $r): ?string => $r->kategori ? '/yasam/'.$r->kategori->slug : null)
                    ->sortable()
                    ->searchable(),

                TextColumn::make('baslik')
                    ->label('Konu Başlığı')
                    ->icon(Heroicon::OutlinedQuestionMarkCircle)
                    ->wrap()
                    ->description(fn (YasamKonusu $r): ?string => $r->kisa_aciklama ?: ($r->kategori ? '/yasam/'.$r->kategori->slug.'/'.$r->slug : null))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Kısa Ad')
                    ->badge()
                    ->color('gray')
                    ->icon(Heroicon::OutlinedCommandLine)
                    ->copyable()
                    ->copyMessage('Kısa ad kopyalandı')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('icerikler_count')
                    ->label('Ülke İçeriği')
                    ->counts('icerikler')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'primary' : 'warning')
                    ->icon(fn (int $state): string => $state > 0 ? 'heroicon-o-globe-alt' : 'heroicon-o-exclamation-circle')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "{$state} Ülke" : '0 Ülke (İçeriksiz)')
                    ->url(fn (YasamKonusu $r): string => YasamKonuIcerigiResource::getUrl('index', ['tableFilters[yasam_konusu_id][value]' => $r->id]))
                    ->tooltip('Bu konuya ait yerel ülke içeriklerini görüntülemek için tıklayın')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->options(fn () => YasamKategorisi::query()->orderBy('sort_order')->get()->mapWithKeys(fn (YasamKategorisi $k) => [
                        $k->id => ($k->ikon ? $k->ikon.' ' : '').$k->ad,
                    ]))
                    ->searchable(),

                TernaryFilter::make('is_active')
                    ->label('Aktiflik Durumu')
                    ->placeholder('Tümü')
                    ->trueLabel('Yalnızca Aktif Konular')
                    ->falseLabel('Yalnızca Pasif Konular'),

                Filter::make('icerikli_konular')
                    ->label('Ülke İçeriği Olanlar')
                    ->query(fn ($query) => $query->has('icerikler')),

                Filter::make('iceriksiz_konular')
                    ->label('İçerik Eklenmemiş Olanlar (Eksik)')
                    ->query(fn ($query) => $query->doesntHave('icerikler')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('aktif_yap')
                        ->label('Aktif Yap')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                            Notification::make()->title('Seçili konular aktif edildi')->success()->send();
                        }),

                    BulkAction::make('pasife_al')
                        ->label('Pasife Al')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            Notification::make()->title('Seçili konular pasife alındı')->warning()->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordActions([
                Action::make('canliSayfa')
                    ->label('Canlıda Gör')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->tooltip('Almanya canlı sayfasını yeni sekmede aç')
                    ->visible(fn (YasamKonusu $r): bool => $r->kategori !== null)
                    ->url(fn (YasamKonusu $r): string => url('/de/yasam/'.$r->kategori->slug.'/'.$r->slug))
                    ->openUrlInNewTab(),

                Action::make('icerikleriGor')
                    ->label('Ülke İçerikleri')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->color('info')
                    ->url(fn (YasamKonusu $r): string => YasamKonuIcerigiResource::getUrl('index', ['tableFilters[yasam_konusu_id][value]' => $r->id])),

                Action::make('aiRehberOneri')
                    ->label('AI Taslak Tavsiyesi')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('primary')
                    ->modalHeading(fn (YasamKonusu $r): string => $r->baslik.' — AI Rehber Taslağı')
                    ->modalDescription(function (YasamKonusu $r): HtmlString {
                        $kat = $r->kategori ? $r->kategori->ad : 'Genel';

                        return new HtmlString(
                            "<div class='space-y-3 text-sm'>"
                            ."<div class='p-3 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-lg text-primary-800 dark:text-primary-300 text-xs'>"
                            ."<strong>Kategori: {$kat}</strong> | Konu: ".e($r->baslik)
                            .'</div>'
                            ."<div class='text-xs text-gray-700 dark:text-gray-300 space-y-2'>"
                            .'<p><strong>Önerilen İçerik İskeleti:</strong></p>'
                            ."<ol class='list-decimal list-inside space-y-1'>"
                            .'<li><strong>Genel Bakış & Yasal Çerçeve:</strong> İlgili ülkedeki yasal dayanak ve temel şartlar.</li>'
                            .'<li><strong>Gerekli Belgeler:</strong> Başvuru için şart olan evraklar ve onaylar.</li>'
                            .'<li><strong>Adım Adım Başvuru:</strong> Randevu alma, şahsen veya online başvuru süreci.</li>'
                            .'<li><strong>Sık Karşılaşılan Hatalar & Püf Noktalar:</strong> Reddedilmeyi önleyici gurbetçi deneyimleri.</li>'
                            .'</ol>'
                            .'</div>'
                            .'</div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                Action::make('duzenle')
                    ->label('Düzenle')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('primary')
                    ->url(fn (YasamKonusu $r): string => static::getUrl('edit', ['record' => $r])),

                DeleteAction::make(),
            ])
            ->emptyStateHeading('Henüz Yaşam Konusu Bulunmuyor')
            ->emptyStateDescription('Kategoriler altında gurbetçilerin merak ettiği soruları ve başlıkları (örn: Banka hesabı açma, İkametgâh kaydı) oluşturarak rehberi zenginleştirebilirsiniz.')
            ->emptyStateIcon(Heroicon::OutlinedQuestionMarkCircle)
            ->emptyStateActions([
                Action::make('konuEkle')
                    ->label('İlk Yaşam Konusunu Ekle')
                    ->icon(Heroicon::OutlinedPlus)
                    ->color('primary')
                    ->url(static::getUrl('create')),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListYasamKonulari::route('/'),
            'create' => CreateYasamKonusu::route('/create'),
            'edit' => EditYasamKonusu::route('/{record}/edit'),
        ];
    }
}
