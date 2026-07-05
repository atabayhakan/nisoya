<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

/**
 * Sayfa (PageForm) ve zone (ZoneForm) editörlerinde ortak kullanılan içerik
 * blokları. resources/views/partials/page-block.blade.php bu blok tiplerini
 * render eder.
 */
class ContentBlocks
{
    /** @return array<int, Builder\Block> */
    public static function schema(): array
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
