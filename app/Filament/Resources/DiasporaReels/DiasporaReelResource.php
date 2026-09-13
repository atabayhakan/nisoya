<?php

namespace App\Filament\Resources\DiasporaReels;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\DiasporaReels\Pages\CreateDiasporaReel;
use App\Filament\Resources\DiasporaReels\Pages\EditDiasporaReel;
use App\Filament\Resources\DiasporaReels\Pages\ListDiasporaReels;
use App\Models\Country;
use App\Models\DiasporaReel;
use App\Services\Ai\CmsAiAssistant;
use App\Support\InstagramMedia;
use BackedEnum;
use Database\Seeders\DiasporaReelSeeder;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Diaspora Reels & Hikayeleri CMS Yönetimi (Model A).
 * Türk diasporasının en yoğun olduğu ülkelerden (DE, KG, NL vb.)
 * Instagram Reels ve video paylaşımlarını ana sayfa vitrininde sergiler.
 */
class DiasporaReelResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = DiasporaReel::class;

    protected static ?string $slug = 'diaspora-reels';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik & Tasarım (CMS)';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Diaspora Reels & Hikayeleri';
    }

    public static function getModelLabel(): string
    {
        return 'diaspora reel';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Diaspora Reels & Hikayeleri';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('instagram_url')
                ->label('Instagram Reel / Gönderi Linki')
                ->required()
                ->maxLength(500)
                ->placeholder('https://www.instagram.com/reel/C8xABC12345/')
                ->helperText('Instagram Reel, Video veya Gönderi linki yapıştırın.')
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                    if ($state && blank($get('instagram_username'))) {
                        $detected = InstagramMedia::normalizeUsername((string) $state);
                        if ($detected) {
                            $set('instagram_username', $detected);
                        }
                    }
                })
                ->columnSpanFull(),

            TextInput::make('instagram_username')
                ->label('Instagram Hesabı / Sayfa (@kullanici_adi)')
                ->placeholder('@berlin_turkleri')
                ->maxLength(100)
                ->helperText('Örn: @berlin_turkleri veya @biskey_lezzetleri'),

            Select::make('country_code')
                ->label('Diaspora Ülkesi')
                ->options(function () {
                    return Country::query()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn (Country $c) => [$c->code => ($c->emoji ? $c->emoji.' ' : '').$c->name_tr]);
                })
                ->searchable()
                ->nullable()
                ->helperText('Paylaşımın ait olduğu diaspora ülkesi'),

            TextInput::make('city')
                ->label('Şehir')
                ->placeholder('Berlin, Bişkek, Frankfurt, Amsterdam...')
                ->maxLength(100),

            TextInput::make('title')
                ->label('Başlık / Etkinlik Adı')
                ->required()
                ->maxLength(120)
                ->placeholder('Berlin Türk Kültür & Sokak Festivali')
                ->hintAction(
                    Action::make('aiReelsHikayesiUret')
                        ->label('AI ile Başlık & Hikaye Yaz (Claude)')
                        ->icon(Heroicon::OutlinedSparkles)
                        ->action(function (callable $get, callable $set, CmsAiAssistant $assistant): void {
                            $odak = (string) ($get('title') ?: $get('caption') ?: 'Türk diasporası etkinlik ve dayanışma anı');
                            $ulke = (string) ($get('country_code') ?: 'DE');
                            $sehir = (string) ($get('city') ?: '');

                            $hikaye = $assistant->generateDiasporaStory($odak, $ulke, $sehir);
                            if ($hikaye) {
                                $set('title', $hikaye['title']);
                                $set('caption', $hikaye['caption']);
                                if (blank($get('city')) && filled($hikaye['suggested_city'])) {
                                    $set('city', $hikaye['suggested_city']);
                                }
                                if (blank($get('instagram_username')) && filled($hikaye['suggested_username'])) {
                                    $set('instagram_username', $hikaye['suggested_username']);
                                }

                                Notification::make()
                                    ->title('Reels hikayesi hazırlandı')
                                    ->body('Claude AI başlık, açıklama ve topluluk bilgilerini başarıyla oluşturdu.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('AI metin üretimi başarısız oldu')
                                    ->warning()
                                    ->send();
                            }
                        })
                )
                ->columnSpanFull(),

            Textarea::make('caption')
                ->label('Açıklama / Hikaye Notu')
                ->rows(3)
                ->maxLength(500)
                ->placeholder('Kreuzberg sokaklarında hafta sonu coşkusu...')
                ->columnSpanFull(),

            TextInput::make('thumbnail_url')
                ->label('Özel Kapak Görseli Linki (Opsiyonel)')
                ->maxLength(500)
                ->placeholder('https://... veya /storage/...')
                ->helperText('Boş bırakılırsa varsayılan diaspora kart tasarımı kullanılır.'),

            TextInput::make('video_url')
                ->label('Doğrudan Video Linki (MP4 Opsiyonel)')
                ->maxLength(500)
                ->placeholder('https://... veya /storage/...')
                ->helperText('Instagram harici doğrudan MP4 video linki varsa buraya girilebilir.'),

            Toggle::make('is_featured')
                ->label('Vitrin Öne Çıkan (Geniş Kart)')
                ->default(false),

            Toggle::make('is_active')
                ->label('Aktif (Sitede Göster)')
                ->default(true),

            TextInput::make('sort_order')
                ->label('Sıralama')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->sortable()
                    ->width('60px')
                    ->alignCenter(),

                TextColumn::make('country.name_tr')
                    ->label('Ülke')
                    ->formatStateUsing(fn (DiasporaReel $record): string => ($record->country ? ($record->country->emoji.' ') : '').($record->country ? $record->country->name_tr : '—'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('city')
                    ->label('Şehir')
                    ->icon('heroicon-m-map-pin')
                    ->iconColor('stone')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Başlık & Hikaye')
                    ->description(fn (DiasporaReel $record): ?string => $record->caption ? Str::limit($record->caption, 65) : null)
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('instagram_username')
                    ->label('Instagram')
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-m-camera')
                    ->placeholder('—')
                    ->searchable(),

                ToggleColumn::make('is_featured')
                    ->label('Öne Çıkan')
                    ->alignCenter(),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('country_code')
                    ->label('Ülke')
                    ->options(fn (): array => Country::query()->where('is_active', true)->orderBy('sort_order')->get()->mapWithKeys(fn (Country $c) => [$c->code => ($c->emoji ? $c->emoji.' ' : '').$c->name_tr])->toArray())
                    ->searchable(),

                TernaryFilter::make('is_active')
                    ->label('Yayın Durumu')
                    ->trueLabel('Yalnız Aktifler')
                    ->falseLabel('Yalnız Pasifler'),

                TernaryFilter::make('is_featured')
                    ->label('Vurgu Durumu')
                    ->trueLabel('Yalnız Öne Çıkanlar')
                    ->falseLabel('Standart Kartlar'),
            ])
            ->actions([
                Action::make('onizle')
                    ->label('Önizle')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('info')
                    ->modalHeading(fn (DiasporaReel $record): string => $record->title)
                    ->modalDescription(fn (DiasporaReel $record): string => ($record->country ? ($record->country->emoji.' ') : '🌍 ').$record->displayLocation().' • '.($record->instagram_username ?: 'Instagram Reels'))
                    ->modalContent(fn (DiasporaReel $record) => view('filament.partials.diaspora-reel-preview', ['reel' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                Action::make('instagramdaAc')
                    ->label('Instagram')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (DiasporaReel $record): string => $record->instagram_url)
                    ->openUrlInNewTab(),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Henüz Diaspora Hikayesi veya Reels Eklenmemiş')
            ->emptyStateDescription('Almanya, Kırgızistan, Hollanda vb. ülkelerdeki Türk diasporasının Instagram Reels videolarını ve etkinlik hikayelerini burada listeleyerek ana sayfada canlı vitrinde yayınlayabilirsiniz.')
            ->emptyStateIcon(Heroicon::OutlinedPlayCircle)
            ->emptyStateActions([
                Action::make('ornekleriYukle')
                    ->label('📥 6 Örnek Diaspora Hikayesini Yükle (Seed)')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('info')
                    ->action(function (): void {
                        (new DiasporaReelSeeder)->run();
                        Notification::make()
                            ->title('Örnek diaspora reels kayıtları yüklendi')
                            ->body('Ana sayfa vitrini için 6 küratörlü diaspora reels kaydı eklendi.')
                            ->success()
                            ->send();
                    }),
                Action::make('aiReelsEkle')
                    ->label('✨ AI ile Hızlı Ekle (Claude)')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('primary')
                    ->url(fn (): string => ListDiasporaReels::getUrl()),
                Action::make('manuelEkle')
                    ->label('Manuel Reel Ekle')
                    ->icon(Heroicon::OutlinedPlus)
                    ->color('gray')
                    ->url(fn (): string => CreateDiasporaReel::getUrl()),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiasporaReels::route('/'),
            'create' => CreateDiasporaReel::route('/create'),
            'edit' => EditDiasporaReel::route('/{record}/edit'),
        ];
    }
}
