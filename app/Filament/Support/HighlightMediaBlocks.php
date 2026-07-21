<?php

namespace App\Filament\Support;

use App\Support\HighlightMedia;
use Closure;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;

use Filament\Forms\Components\Toggle;

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
                        ->maxSize(10240),
                ]),

            Builder\Block::make('youtube')
                ->label('YouTube video')
                ->icon('heroicon-o-play-circle')
                ->schema([
                    TextInput::make('url')
                        ->label('YouTube linki')
                        ->url()
                        ->nullable()
                        ->rules([
                            fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                if ($value && ! HighlightMedia::youtubeId($value)) {
                                    $fail('Geçerli bir YouTube linki girin.');
                                }
                            },
                        ]),
                    Toggle::make('autoplay')
                        ->label('Otomatik başlasın')
                        ->default(true),
                    Toggle::make('muted')
                        ->label('Sessiz başlasın')
                        ->default(true)
                        ->helperText('Tarayıcılar otomatik başlayan videoları genellikle sessiz modda kabul eder.'),
                ]),

            Builder\Block::make('video')
                ->label('Video dosyası')
                ->icon('heroicon-o-film')
                ->schema([
                    FileUpload::make('path')
                        ->label('Video dosyası')
                        ->disk('public')
                        ->directory('highlights')
                        ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm', 'video/x-m4v', 'video/ogg'])
                        ->maxSize(51200)
                        ->helperText('Yükleme tamamen bittikten sonra "Değişiklikleri kaydet" butonuna basın.'),
                    Toggle::make('autoplay')
                        ->label('Otomatik başlasın')
                        ->default(true),
                    Toggle::make('muted')
                        ->label('Sessiz başlasın')
                        ->default(true)
                        ->helperText('Sessiz kapalı olursa bazı tarayıcılar (Chrome/Safari) otomatik başlatmaya izin vermeyebilir.'),
                    Toggle::make('loop')
                        ->label('Sürekli dönsün (Loop)')
                        ->default(true),
                ]),
        ];
    }
}
