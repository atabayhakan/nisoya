<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PageStatus;
use App\Filament\Support\ContentBlocks;
use App\Models\Page;
use Filament\Forms\Components\Builder;
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
                        ->blocks(ContentBlocks::schema()),
                ]),
        ]);
    }
}
