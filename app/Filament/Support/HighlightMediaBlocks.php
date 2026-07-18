<?php

namespace App\Filament\Support;

use App\Support\HighlightMedia;
use Closure;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;

/**
 * Büyük/küçük vurgu kartlarındaki medya alanının (bkz.
 * HomeHighlightResourceBase::form, docs/plans/2026-07-18-buyuk-kart-medya-design.md)
 * blok tipleri. resources/views/partials/highlight-media.blade.php bu blokları
 * render eder — aynı desen ContentBlocks/page-block.blade.php ile birebir.
 */
class HighlightMediaBlocks
{
    /** @return array<int, Builder\Block> */
    public static function schema(): array
    {
        return [
            Builder\Block::make('resim')
                ->label('Resim')
                ->icon('heroicon-o-photo')
                ->schema([
                    FileUpload::make('path')
                        ->label('Resim')
                        ->image()
                        ->disk('public')
                        ->directory('highlights')
                        ->maxSize(4096)
                        ->required(),
                ]),

            Builder\Block::make('youtube')
                ->label('YouTube video')
                ->icon('heroicon-o-play-circle')
                ->schema([
                    TextInput::make('url')
                        ->label('YouTube linki')
                        ->url()
                        ->required()
                        ->rules([
                            fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                if (! HighlightMedia::youtubeId($value)) {
                                    $fail('Geçerli bir YouTube linki girin.');
                                }
                            },
                        ]),
                ]),

            Builder\Block::make('video')
                ->label('Video dosyası')
                ->icon('heroicon-o-film')
                ->schema([
                    FileUpload::make('path')
                        ->label('Video dosyası')
                        ->disk('public')
                        ->directory('highlights')
                        ->acceptedFileTypes(['video/mp4'])
                        ->maxSize(20480)
                        ->required(),
                ]),
        ];
    }
}
