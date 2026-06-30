<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PageStatus;
use App\Models\Page;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sayfa bilgileri')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Başlık')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Kısa ad (URL)')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->rule(Rule::notIn(Page::RESERVED_SLUGS))
                        ->helperText('Adres: nisoya.com/kısa-ad — sadece küçük harf, rakam ve tire.'),
                    Select::make('status')
                        ->label('Durum')
                        ->options(PageStatus::class)
                        ->default(PageStatus::Taslak->value)
                        ->required(),
                    TextInput::make('sort_order')
                        ->label('Sıra (footer)')
                        ->numeric()
                        ->default(0),
                    Toggle::make('show_in_footer')
                        ->label('Footer menüsünde göster')
                        ->columnSpanFull(),
                    Textarea::make('meta_description')
                        ->label('SEO açıklaması (meta)')
                        ->maxLength(255)
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('İçerik blokları')
                ->description('Sayfayı bloklarla oluştur: başlık, metin, görsel, iki sütun, çağrı ve ayraç ekleyebilirsin.')
                ->schema([
                    Builder::make('blocks')
                        ->label('')
                        ->collapsible()
                        ->cloneable()
                        ->blockNumbers(false)
                        ->blocks(static::blocks()),
                ]),
        ]);
    }

    /** @return array<int, Builder\Block> */
    protected static function blocks(): array
    {
        return [
            Builder\Block::make('baslik')
                ->label('Başlık')
                ->icon('heroicon-o-bookmark')
                ->schema([
                    TextInput::make('text')->label('Başlık metni')->required(),
                    Select::make('level')
                        ->label('Boyut')
                        ->options(['h2' => 'Büyük (H2)', 'h3' => 'Orta (H3)'])
                        ->default('h2'),
                ]),

            Builder\Block::make('metin')
                ->label('Metin')
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    RichEditor::make('content')->label('')->required(),
                ]),

            Builder\Block::make('gorsel')
                ->label('Görsel')
                ->icon('heroicon-o-photo')
                ->schema([
                    FileUpload::make('image')
                        ->label('Görsel')
                        ->image()
                        ->disk('public')
                        ->directory('sayfalar')
                        ->maxSize(4096)
                        ->required(),
                    TextInput::make('alt')->label('Alternatif metin (erişilebilirlik)'),
                    TextInput::make('caption')->label('Açıklama (ops.)'),
                ]),

            Builder\Block::make('iki_sutun')
                ->label('İki sütun')
                ->icon('heroicon-o-view-columns')
                ->columns(2)
                ->schema([
                    RichEditor::make('left')->label('Sol sütun'),
                    RichEditor::make('right')->label('Sağ sütun'),
                ]),

            Builder\Block::make('cta')
                ->label('Çağrı (CTA)')
                ->icon('heroicon-o-megaphone')
                ->schema([
                    TextInput::make('title')->label('Başlık')->required(),
                    TextInput::make('button_text')->label('Buton yazısı')->required(),
                    TextInput::make('button_url')->label('Buton bağlantısı')->required()->helperText('Örn. /kayit veya https://...'),
                ]),

            Builder\Block::make('ayrac')
                ->label('Ayraç')
                ->icon('heroicon-o-minus')
                ->schema([
                    Placeholder::make('info')->label('')->content('İçeriğe görsel bir ayraç çizgisi eklenir.'),
                ]),
        ];
    }
}
