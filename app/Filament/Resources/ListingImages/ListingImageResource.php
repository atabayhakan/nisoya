<?php

namespace App\Filament\Resources\ListingImages;

use App\Filament\Resources\ListingImages\Pages\ListListingImages;
use App\Filament\Resources\ListingImages\Pages\ViewListingImage;
use App\Models\Country;
use App\Models\ListingImage;
use App\Notifications\GpsPrivacyNotification;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class ListingImageResource extends Resource
{
    protected static ?string $model = ListingImage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Pazaryeri';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return 'Görseller';
    }

    public static function getModelLabel(): string
    {
        return 'görsel';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Görseller';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            // Şema doğrudan view'da çağrılacak
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path_thumb')
                    ->label('Önizleme')
                    ->disk('public')
                    ->height(60)
                    ->extraImgAttributes(['class' => 'rounded object-cover']),

                TextColumn::make('listing.title')
                    ->label('İlan')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->placeholder('—'),

                TextColumn::make('width')
                    ->label('Boyut')
                    ->formatStateUsing(fn ($record) => $record->width && $record->height
                        ? "{$record->width}×{$record->height}"
                        : '—')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('size_bytes')
                    ->label('Dosya')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 1).' KB' : '—')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('had_gps')
                    ->label('GPS')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn (bool $state) => $state
                        ? '⚠️ Orijinal görsel GPS koordinatları içeriyordu (temizlendi)'
                        : 'GPS verisi yok'),

                TextColumn::make('gps_lat')
                    ->label('GPS Konum')
                    ->formatStateUsing(function ($record) {
                        if (! $record->gps_lat || ! $record->gps_lng) {
                            return '—';
                        }

                        return sprintf('%.4f, %.4f', $record->gps_lat, $record->gps_lng);
                    })
                    ->placeholder('—')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->copyable(),

                IconColumn::make('has_sensitive_exif')
                    ->label('Hassas EXIF')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-exclamation')
                    ->falseIcon('heroicon-o-shield-check')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn (bool $state) => $state
                        ? 'GPS, seri no vb. hassas EXIF bilgisi içeriyor'
                        : 'EXIF temiz'),

                TextColumn::make('reverse_city')
                    ->label('Konum (Reverse)')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->reverseLocationLabel;
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->where('reverse_city', 'like', "%{$search}%")
                                ->orWhere('reverse_country_name', 'like', "%{$search}%");
                        });
                    })
                    ->placeholder('— (henüz kodlanmadı)')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-map-pin'),

                IconColumn::make('is_cover')
                    ->label('Kapak')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Yüklendi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // === GİZLİLİK FİLTRELERİ ===

                TernaryFilter::make('had_gps')
                    ->label('GPS verisi içeriyor muydu?')
                    ->trueLabel('GPS vardı (uyarı)')
                    ->falseLabel('GPS yok')
                    ->placeholder('Tümü'),

                TernaryFilter::make('has_sensitive_exif')
                    ->label('Hassas EXIF var mı?')
                    ->trueLabel('Hassas (GPS, seri no vb.)')
                    ->falseLabel('Temiz')
                    ->placeholder('Tümü'),

                Filter::make('with_gps_coordinates')
                    ->label('GPS koordinatı çıkarılmış')
                    ->query(fn ($query) => $query->whereNotNull('gps_lat')->whereNotNull('gps_lng')),

                // === TEK TEK EXIF ALANLARINA GÖRE FİLTRELEME ===

                SelectFilter::make('camera_make')
                    ->label('Kamera markası')
                    ->options(function () {
                        return ListingImage::query()
                            ->whereNotNull('exif_metadata')
                            ->get()
                            ->pluck('exif_metadata.Make')
                            ->filter()
                            ->unique()
                            ->sort()
                            ->values()
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            return $query->where('exif_metadata->Make', $data['value']);
                        }

                        return $query;
                    })
                    ->searchable(),

                SelectFilter::make('camera_model')
                    ->label('Kamera modeli')
                    ->options(function () {
                        return ListingImage::query()
                            ->whereNotNull('exif_metadata')
                            ->get()
                            ->pluck('exif_metadata.Model')
                            ->filter()
                            ->unique()
                            ->sort()
                            ->values()
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            return $query->where('exif_metadata->Model', $data['value']);
                        }

                        return $query;
                    })
                    ->searchable(),

                SelectFilter::make('software')
                    ->label('Yazılım')
                    ->options(function () {
                        return ListingImage::query()
                            ->whereNotNull('exif_metadata')
                            ->get()
                            ->pluck('exif_metadata.Software')
                            ->filter()
                            ->unique()
                            ->sort()
                            ->values()
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            return $query->where('exif_metadata->Software', $data['value']);
                        }

                        return $query;
                    }),

                // === REVERSE GEOCODED ÜLKE/ŞEHİR FİLTRELERİ ===

                SelectFilter::make('reverse_country_code')
                    ->label('Ülke (reverse)')
                    ->options(function () {
                        return ListingImage::query()
                            ->whereNotNull('reverse_country_code')
                            ->distinct()
                            ->orderBy('reverse_country_code')
                            ->pluck('reverse_country_code', 'reverse_country_code')
                            ->mapWithKeys(fn ($code) => [$code => Country::find($code)?->name_tr ?? $code])
                            ->toArray();
                    })
                    ->searchable(),

                Filter::make('reverse_geocoded')
                    ->label('Reverse geocoded mi?')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNotNull('reverse_geocoded_at')),

                Filter::make('pending_reverse_geocoded')
                    ->label('Reverse geocoded bekleyen')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNotNull('gps_lat')->whereNull('reverse_geocoded_at')),

                // === GENEL FİLTRELER ===

                TernaryFilter::make('is_cover')
                    ->label('Sadece kapak görselleri')
                    ->placeholder('Tümü'),

                Filter::make('with_exif')
                    ->label('EXIF metadata olan')
                    ->query(fn ($query) => $query->whereNotNull('exif_metadata')->where('exif_metadata', '!=', '{}')),

                Filter::make('no_exif')
                    ->label('EXIF olmayan (temiz)')
                    ->query(fn ($query) => $query->where(function ($q) {
                        $q->whereNull('exif_metadata')
                            ->orWhere('exif_metadata', '{}');
                    })),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('Başlangıç'),
                        DatePicker::make('until')->label('Bitiş'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->label('EXIF Detay'),
                DeleteAction::make(),
                Action::make('redact_gps')
                    ->label('GPS Bilgisini Sil')
                    ->icon('heroicon-o-map-pin')
                    ->color('warning')
                    ->visible(fn (ListingImage $record) => $record->had_gps)
                    ->requiresConfirmation()
                    ->modalHeading('GPS bilgisini sil')
                    ->modalDescription('EXIF metadata\'sından GPS koordinatları kaldırılacak. Görsel yayından etkilenmez.')
                    ->action(function (ListingImage $record) {
                        $exif = $record->exif_metadata ?? [];
                        unset($exif['GPSLatitude'], $exif['GPSLongitude'], $exif['GPSLatitudeRef'], $exif['GPSLongitudeRef'], $exif['GPSAltitude']);

                        $record->update([
                            'exif_metadata' => $exif,
                            'had_gps' => false,
                            'has_sensitive_exif' => ListingImage::hasSensitiveExif($exif),
                            'gps_lat' => null,
                            'gps_lng' => null,
                        ]);

                        activity('image')
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->log('GPS bilgisi silindi (KVKK talebi)');

                        Notification::make()
                            ->title('GPS bilgisi silindi')
                            ->body("Görsel #{$record->id} için GPS koordinatları kaldırıldı.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('redact_gps_bulk')
                        ->label('GPS bilgilerini sil (KVKK)')
                        ->icon('heroicon-o-map-pin')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Seçili görsellerden GPS bilgisini sil')
                        ->modalDescription('EXIF metadata\'sından GPS koordinatları kaldırılacak.')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (! $record->had_gps) {
                                    continue;
                                }
                                $exif = $record->exif_metadata ?? [];
                                unset($exif['GPSLatitude'], $exif['GPSLongitude'], $exif['GPSLatitudeRef'], $exif['GPSLongitudeRef'], $exif['GPSAltitude']);
                                $record->update([
                                    'exif_metadata' => $exif,
                                    'had_gps' => false,
                                    'has_sensitive_exif' => ListingImage::hasSensitiveExif($exif),
                                    'gps_lat' => null,
                                    'gps_lng' => null,
                                ]);
                                $count++;
                            }
                            Notification::make()
                                ->title("{$count} görselden GPS silindi")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('notify_users_gps')
                        ->label('Kullanıcıları uyar (GPS)')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('GPS\'li görseller için kullanıcıları uyar')
                        ->modalDescription('Seçili görsellerin sahiplerine KVKK kapsamında bilgilendirme e-postası gönderilir.')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (! $record->had_gps || ! $record->listing?->user) {
                                    continue;
                                }

                                $record->listing->user->notify(new GpsPrivacyNotification(
                                    listingId: $record->listing->id,
                                    listingTitle: $record->listing->title,
                                ));

                                activity('image')
                                    ->performedOn($record)
                                    ->causedBy(auth()->user())
                                    ->withProperties(['user_id' => $record->listing->user_id])
                                    ->log('Kullanıcıya GPS uyarısı gönderildi');
                                $count++;
                            }
                            Notification::make()
                                ->title("{$count} kullanıcıya uyarı gönderildi")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListListingImages::route('/'),
            'view' => ViewListingImage::route('/{record}'),
        ];
    }
}
