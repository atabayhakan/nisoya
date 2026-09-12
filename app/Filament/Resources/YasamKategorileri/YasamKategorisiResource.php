<?php

namespace App\Filament\Resources\YasamKategorileri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\YasamKategorileri\Pages\CreateYasamKategorisi;
use App\Filament\Resources\YasamKategorileri\Pages\EditYasamKategorisi;
use App\Filament\Resources\YasamKategorileri\Pages\ListYasamKategorileri;
use App\Filament\Resources\YasamKonulari\YasamKonusuResource;
use App\Models\YasamKategorisi;
use App\Services\Ai\CountryGuideAiAssistant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Yaşam Rehberi — kategoriler (Bankacılık & Finans, Barınma, ...).
 *
 * Ülkeden bağımsız şablon — Ülke Rehberi'ndeki IslemTuruResource'un aynısı.
 */
class YasamKategorisiResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = YasamKategorisi::class;

    protected static ?string $slug = 'yasam-kategorileri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Ülke Rehberi';

    protected static ?string $navigationLabel = 'Yaşam Kategorileri';

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return 'Yaşam Kategorisi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Yaşam Kategorileri';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = YasamKategorisi::query()->where('is_active', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getIconForRecord(?YasamKategorisi $record): BackedEnum
    {
        if (! $record) {
            return Heroicon::OutlinedSquares2x2;
        }

        return match ($record->slug) {
            'bankacilik-finans' => Heroicon::OutlinedBanknotes,
            'barinma' => Heroicon::OutlinedHome,
            'saglik-sigorta' => Heroicon::OutlinedHeart,
            'is-kariyer' => Heroicon::OutlinedBriefcase,
            'egitim' => Heroicon::OutlinedAcademicCap,
            'ulasim' => Heroicon::OutlinedTruck,
            'gundelik-burokrasi' => Heroicon::OutlinedClipboardDocumentList,
            'kultur-uyum' => Heroicon::OutlinedGlobeEuropeAfrica,
            default => Heroicon::OutlinedSquares2x2,
        };
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kategori Bilgileri')
                ->description('Yaşam rehberinin ülkelerden bağımsız ana kategorisi')
                ->columns(2)
                ->schema([
                    TextInput::make('ad')
                        ->label('Kategori Adı')
                        ->placeholder('örn. Bankacılık & Finans')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Kısa Ad (URL)')
                        ->required()
                        ->maxLength(80)
                        ->helperText('Adres: /{ulke}/yasam/{kısa-ad}'),
                    TextInput::make('ikon')
                        ->label('İkon (Emoji)')
                        ->placeholder('🏦')
                        ->maxLength(60)
                        ->hintAction(
                            Action::make('aiEmojiOner')
                                ->label('AI Emoji')
                                ->icon(Heroicon::OutlinedSparkles)
                                ->tooltip('Kategori adına göre emoji belirle')
                                ->action(function (callable $get, callable $set, CountryGuideAiAssistant $assistant): void {
                                    $ad = (string) $get('ad');
                                    if (blank($ad)) {
                                        Notification::make()->title('Lütfen önce kategori adını girin')->warning()->send();

                                        return;
                                    }
                                    $emoji = $assistant->suggestEmoji($ad);
                                    $set('ikon', $emoji);
                                    Notification::make()->title("Önerilen emoji '{$emoji}' uygulandı")->success()->send();
                                })
                        ),
                    TextInput::make('sort_order')
                        ->label('Görüntülenme Sırası')
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label('Aktif (Rehberde Göster)')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ad')
                    ->label('Kategori')
                    ->icon(fn (YasamKategorisi $r): BackedEnum => static::getIconForRecord($r))
                    ->formatStateUsing(fn (YasamKategorisi $r): string => ($r->ikon ? $r->ikon.' ' : '').$r->ad)
                    ->description(fn (YasamKategorisi $r): string => 'Sıra: #'.$r->sort_order)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('URL Yolu')
                    ->badge()
                    ->color('gray')
                    ->icon(Heroicon::OutlinedCommandLine)
                    ->formatStateUsing(fn (YasamKategorisi $r): string => '/yasam/'.$r->slug)
                    ->copyable()
                    ->copyMessage('URL yolu kopyalandı')
                    ->searchable(),

                TextColumn::make('konular_count')
                    ->label('Konular')
                    ->counts('konular')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'primary' : 'gray')
                    ->icon(fn (int $state): string => $state > 0 ? 'heroicon-o-document-text' : 'heroicon-o-minus-circle')
                    ->formatStateUsing(fn (int $state): string => "{$state} Konu")
                    ->url(fn (YasamKategorisi $r): string => YasamKonusuResource::getUrl('index', ['tableFilters[kategori_id][value]' => $r->id]))
                    ->tooltip('Kategoriye ait konuları listelemek için tıklayın')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktiflik Durumu')
                    ->placeholder('Tümü')
                    ->trueLabel('Yalnızca Aktif Kategoriler')
                    ->falseLabel('Yalnızca Pasif Kategoriler'),

                Filter::make('konusu_olanlar')
                    ->label('Konusu Olanlar')
                    ->query(fn ($query) => $query->has('konular')),

                Filter::make('konusu_olmayanlar')
                    ->label('Konusu Olmayanlar (Eksik)')
                    ->query(fn ($query) => $query->doesntHave('konular')),
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
                            Notification::make()->title('Seçili kategoriler aktif edildi')->success()->send();
                        }),

                    BulkAction::make('pasife_al')
                        ->label('Pasife Al')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            Notification::make()->title('Seçili kategoriler pasife alındı')->warning()->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordActions([
                Action::make('canliSayfa')
                    ->label('Canlıda Gör')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->tooltip('Kategorinin canlı sayfasını yeni sekmede aç')
                    ->url(fn (YasamKategorisi $r): string => url('/de/yasam/'.$r->slug))
                    ->openUrlInNewTab(),

                Action::make('konulariGor')
                    ->label('Konuları Aç')
                    ->icon(Heroicon::OutlinedQuestionMarkCircle)
                    ->color('primary')
                    ->url(fn (YasamKategorisi $r): string => YasamKonusuResource::getUrl('index', ['tableFilters[kategori_id][value]' => $r->id])),

                Action::make('aiKonuOner')
                    ->label('AI Konu Öner')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('info')
                    ->modalHeading(fn (YasamKategorisi $r): string => $r->ad.' — AI Konu Başlığı Önerileri')
                    ->modalDescription(function (YasamKategorisi $r): HtmlString {
                        $ornekler = match ($r->slug) {
                            'barinma' => [
                                'Kira sözleşmesi (Mietvertrag) yaparken dikkat edilecekler',
                                'Depozito (Kaution) ödemesi ve geri alma süreci',
                                'Schufa / Kredi notu olmadan ev kiralama imkânları',
                                'Ev arkadaşlığı (WG - Wohngemeinschaft) kültürü ve kuralları',
                            ],
                            'saglik-sigorta' => [
                                'Zorunlu kamu sağlık sigortası (Gesetzliche Krankenversicherung) seçimi',
                                'Özel sağlık sigortası (PKV) kimler için avantajlıdır?',
                                'Aile hekimi (Hausarzt) kaydı nasıl yapılır?',
                                'Acil servis ve nöbetçi eczane sistemi işleyişi',
                            ],
                            'is-kariyer' => [
                                'Alman formatında CV (Lebenslauf) ve ön yazı (Anschreiben) hazırlama',
                                'Mavi Kart (Blaue Karte EU) maaş alt sınırları ve hakları',
                                'Vergi sınıfı (Steuerklasse) seçimi ve maaş kesintileri',
                                'İşten çıkarılma ve kıdem tazminatı hakları (Kündigungsschutz)',
                            ],
                            'egitim' => [
                                'Çocuklar için okul öncesi eğitim (Kita) başvurusu ve yer bulma',
                                'İlkokul (Grundschule) kayıt ve dil yeterlilik testleri',
                                'Üniversite denklik ve başvuru süreçleri (Uni-Assist)',
                                'Öğrenci burs ve kredi imkânları (BAföG)',
                            ],
                            'ulasim' => [
                                'Türk ehliyetinin Avrupa/Almanya sürücü belgesine dönüştürülmesi',
                                'Toplu taşıma abonman kartları ve indirimli biletler',
                                'Araç alımı, tescili (Zulassung) ve zorunlu trafik sigortası',
                                'Bisiklet yolları ve şehir içi bisiklet kuralları',
                            ],
                            'gundelik-burokrasi' => [
                                'İkametgâh kaydı (Anmeldung) randevusu ve gerekli belgeler',
                                'Vergi kimlik numarası (Steuer-ID) ve Sosyal Güvenlik Numarası (SV-Nummer)',
                                'Radyo & Televizyon vergisi (GEZ / Rundfunkbeitrag) muafiyet ve ödeme',
                                'Telefon hattı ve internet sözleşmesi yaparken cayma hakları',
                            ],
                            default => [
                                'Yurt dışı banka hesabı açılışı ve gerekli evraklar',
                                'Düşük maliyetli uluslararası para transferi yöntemleri',
                                'Kredi kartı ve debit kart kullanım şartları',
                            ],
                        };

                        $html = "<div class='space-y-3 text-sm'>"
                            ."<div class='p-3 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-lg text-primary-800 dark:text-primary-300 text-xs'>"
                            .'<strong>💡 Bu kategori için gurbetçilerin en çok aradığı sorular:</strong>'
                            .'</div>'
                            ."<ul class='space-y-1.5 list-disc list-inside text-xs text-gray-700 dark:text-gray-300'>";
                        foreach ($ornekler as $ornek) {
                            $html .= '<li>'.e($ornek).'</li>';
                        }
                        $html .= '</ul></div>';

                        return new HtmlString($html);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                Action::make('duzenle')
                    ->label('Düzenle')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('primary')
                    ->url(fn (YasamKategorisi $r): string => static::getUrl('edit', ['record' => $r])),

                DeleteAction::make(),
            ])
            ->emptyStateHeading('Henüz Yaşam Kategorisi Bulunmuyor')
            ->emptyStateDescription('Yaşam Rehberi ana başlıklarını (Bankacılık, Barınma, Sağlık, Eğitim vb.) oluşturarak rehber taksonomisini başlatabilirsiniz.')
            ->emptyStateIcon(Heroicon::OutlinedSquares2x2)
            ->emptyStateActions([
                Action::make('kategoriEkle')
                    ->label('İlk Yaşam Kategorisini Ekle')
                    ->icon(Heroicon::OutlinedPlus)
                    ->color('primary')
                    ->url(static::getUrl('create')),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListYasamKategorileri::route('/'),
            'create' => CreateYasamKategorisi::route('/create'),
            'edit' => EditYasamKategorisi::route('/{record}/edit'),
        ];
    }
}
