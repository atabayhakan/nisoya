<?php

namespace App\Filament\Resources\Listings\Infolists;

use App\Models\ListingImage;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

/**
 * İlan görselinin detaylı bilgi görüntüleme.
 * EXIF metadata audit paneli (admin/mod için).
 */
class ListingImageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Görsel Önizleme')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('path_large')
                            ->label('Varyantlar')
                            ->height(280)
                            ->extraImgAttributes(['class' => 'rounded-lg object-cover']),
                        TextEntry::make('path_thumb')
                            ->label('Thumb path')
                            ->copyable()
                            ->fontFamily('mono')
                            ->size('sm'),
                        TextEntry::make('path_medium')
                            ->label('Medium path')
                            ->copyable()
                            ->fontFamily('mono')
                            ->size('sm'),
                        TextEntry::make('path_large')
                            ->label('Large path')
                            ->copyable()
                            ->fontFamily('mono')
                            ->size('sm'),
                    ]),

                Section::make('Boyut & Meta')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('width')
                            ->label('Genişlik')
                            ->suffix(' px')
                            ->numeric(),
                        TextEntry::make('height')
                            ->label('Yükseklik')
                            ->suffix(' px')
                            ->numeric(),
                        TextEntry::make('size_bytes')
                            ->label('Dosya boyutu')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 1).' KB' : '—'),
                        TextEntry::make('is_cover')
                            ->label('Kapak')
                            ->badge()
                            ->color(fn (bool $state) => $state ? 'success' : 'gray')
                            ->formatStateUsing(fn (bool $state) => $state ? 'Kapak' : 'Ek'),
                        TextEntry::make('sort_order')
                            ->label('Sıra')
                            ->numeric(),
                        TextEntry::make('created_at')
                            ->label('Yüklendi')
                            ->dateTime('d.m.Y H:i'),
                    ]),

                Section::make('EXIF Metadata (Audit)')
                    ->description('Yükleme anında orijinal görselden alındı. Kullanıcıya gösterilmez, sadece admin/mod erişir.')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('had_gps')
                            ->label('GPS Verisi')
                            ->badge()
                            ->color(fn (bool $state) => $state ? 'warning' : 'success')
                            ->formatStateUsing(fn (bool $state) => $state ? '⚠️ GPS koordinatları vardı (temizlendi)' : '✓ GPS yok'),
                        TextEntry::make('exif_summary')
                            ->label('EXIF Bilgileri')
                            ->getStateUsing(function (ListingImage $record) {
                                $summary = $record->exifSummary();
                                if (empty($summary)) {
                                    return 'EXIF metadata yok veya temizlenmiş';
                                }

                                return collect($summary)
                                    ->map(fn ($v, $k) => "{$k}: {$v}")
                                    ->implode("\n");
                            })
                            ->placeholder('EXIF metadata yok')
                            ->fontFamily('mono')
                            ->copyable()
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-finger-print')
                    ->color('gray'),

                Section::make('Ham Veri (JSON)')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        KeyValueEntry::make('exif_metadata')
                            ->label('Tüm EXIF alanları')
                            ->placeholder('EXIF metadata boş'),
                    ])
                    ->icon('heroicon-o-code-bracket'),
            ]);
    }
}