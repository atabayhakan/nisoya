<?php

namespace App\Filament\Support;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Models\HomeHighlight;
use App\Services\Ai\CmsAiAssistant;
use App\Support\HighlightIcon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder as MediaBuilder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * BigHighlightResource + SmallHighlightResource'ın paylaştığı form/tablo
 * mantığı (bkz. docs/plans/2026-07-17-anasayfa-vurgu-slider-design.md).
 * Kasıtlı olarak `app/Filament/Resources/` DIŞINDA — Filament panel
 * sağlayıcısı sadece o dizini keşfediyor (bkz. AdminPanelProvider), bu
 * soyut sınıf otomatik keşif tarafından bir kaynak olarak algılanmasın diye
 * burada duruyor (ContentBlocks.php ile aynı "paylaşılan Filament mantığı"
 * konumu).
 */
abstract class HomeHighlightResourceBase extends Resource
{
    // Ana sayfa vurgu slider'ları site konfigürasyonudur → yalnızca Admin.
    use RestrictsToAdmins;

    protected static ?string $model = HomeHighlight::class;

    abstract protected static function slot(): string;

    /** Alt sınıflar (Create sayfaları) yeni kayda hangi slot'u yazacağını buradan alır. */
    public static function slotValue(): string
    {
        return static::slot();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('slot', static::slot());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('diaspora_oncelik_notu')
                ->label('')
                ->content(new HtmlString('<div class="rounded-2xl border border-amber-200 bg-amber-50/90 p-3 text-xs text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200">ℹ️ <strong>Vitrin Önceliği:</strong> Ana sayfada aktif <em>Diaspora Reels & Hikayeleri</em> kayıtları bulunduğunda öncelikli olarak Reels vitrini görüntülenir. Bu kartlar, henüz Reels girilmediğinde veya yedekleme modunda sergilenir. <a href="/yonetim/diaspora-reels" class="underline font-bold ml-1 text-emerald-700 dark:text-emerald-400">Diaspora Reels Yönet →</a></div>'))
                ->columnSpanFull(),
            TextInput::make('title')
                ->label('Başlık')
                ->nullable()
                ->maxLength(60),
            TextInput::make('text')
                ->label('Metin')
                ->nullable()
                ->maxLength(160)
                ->hintAction(
                    Action::make('aiKartMetniUret')
                        ->label('AI ile Metin Üret')
                        ->icon(Heroicon::OutlinedSparkles)
                        ->action(function (callable $get, callable $set, CmsAiAssistant $assistant): void {
                            $baslik = (string) ($get('title') ?: 'Gurbetçiler İçin Güvenilir Alışveriş ve İlan');
                            $kart = $assistant->generateHighlight($baslik);
                            if (blank($get('title'))) {
                                $set('title', $kart['title']);
                            }
                            $set('text', $kart['text']);
                            Notification::make()
                                ->title('Vurgu kartı metni oluşturuldu')
                                ->success()
                                ->send();
                        })
                ),
            Select::make('icon')
                ->label('İkon')
                ->options(HighlightIcon::OPTIONS)
                ->native(false)
                ->nullable()
                ->helperText('Aşağıya medya eklenirse kartta ikon yerine o gösterilir.'),
            MediaBuilder::make('media')
                ->label('Medya (opsiyonel)')
                ->blocks(HighlightMediaBlocks::schema())
                ->addActionLabel('Medya ekle')
                ->maxItems(6)
                ->collapsible()
                ->collapsed()
                ->blockNumbers(false),
            Toggle::make('is_active')
                ->label('Aktif (kartta göster)')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('Sıra')->sortable(),
                TextColumn::make('title')->label('Başlık')->searchable(),
                TextColumn::make('text')->label('Metin')->limit(50),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
