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
                    TextInput::make('url')
                        ->label('Resim adresi / URL (ör. https://... veya /storage/...)')
                        ->nullable()
                        ->helperText('Medya kütüphanesinden veya sunucudan aldığınız resim linkini buraya yapıştırabilirsiniz.'),
                    FileUpload::make('path')
                        ->label('veya Bilgisayardan Resim Yükle')
                        ->image()
                        ->disk('public')
                        ->directory('highlights')
                        ->maxSize(10240)
                        ->fetchFileInformation(false)
                        ->nullable(),
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
                ->label('Video dosyası / linki')
                ->icon('heroicon-o-film')
                ->schema([
                    TextInput::make('url')
                        ->label('Video adresi / URL (ör. https://... veya /storage/highlights/video.mp4)')
                        ->nullable()
                        ->helperText('Medya kütüphanesinden veya sunucudan aldığınız doğrudan video linkini yapıştırın.'),
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
