<?php

namespace App\Filament\Resources\ListingImages;

use App\Enums\ListingStatus;
use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\ListingImages\Pages\ListListingImages;
use App\Filament\Resources\ListingImages\Pages\ViewListingImage;
use App\Filament\Resources\ListingImages\Widgets\ListingImageStatsWidget;
use App\Filament\Resources\Listings\ListingResource;
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
    use RestrictsToAdmins;

    protected static ?string $model = ListingImage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Pazaryeri & Ticaret';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return 'Görseller';
    }

    public static function getModelLabel(): string
    {
        return 'Görsel';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Görseller';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ListingImage::query()->where('is_flagged', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
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
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover shadow-sm ring-1 ring-gray-200 dark:ring-gray-800']),

                TextColumn::make('listing.title')
                    ->label('İlan')
                    ->weight('bold')
                    ->description(function (ListingImage $record): ?string {
                        $cat = $record->listing && $record->listing->category ? $record->listing->category->name : null;
                        $user = $record->listing && $record->listing->user ? $record->listing->user->name : null;
                        if ($cat && $user) {
                            return "{$cat} • {$user}";
                        }

                        return $cat ?? $user;
                    })
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->placeholder('—'),

                TextColumn::make('width')
                    ->label('Boyut')
                    ->formatStateUsing(fn (ListingImage $record): string => $record->width && $record->height
                        ? "{$record->width}×{$record->height}"
                        : '—')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('size_bytes')
                    ->label('Dosya')
                    ->formatStateUsing(function ($state): string {
                        if (! $state) {
                            return '—';
                        }
                        $bytes = (int) $state;
                        if ($bytes >= 1048576) {
                            return number_format($bytes / 1048576, 1, ',', '.').' MB';
                        }

                        return number_format($bytes / 1024, 0, ',', '.').' KB';
                    })
                    ->sortable(),

                IconColumn::make('had_gps')
                    ->label('GPS')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn (bool $state): string => $state
                        ? '⚠️ Orijinal görsel GPS koordinatları içeriyordu (temizlendi)'
                        : 'GPS verisi yok'),

                TextColumn::make('gps_lat')
                    ->label('GPS Konum')
                    ->formatStateUsing(function (ListingImage $record): string {
                        if (! $record->gps_lat || ! $record->gps_lng) {
                            return '—';
                        }

                        return sprintf('%.4f, %.4f', $record->gps_lat, $record->gps_lng);
                    })
                    ->placeholder('—')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('has_sensitive_exif')
                    ->label('Hassas EXIF')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-exclamation')
                    ->falseIcon('heroicon-o-shield-check')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn (bool $state): string => $state
                        ? 'GPS, seri no vb. hassas EXIF bilgisi içeriyor'
                        : 'EXIF temiz'),

                TextColumn::make('reverse_city')
                    ->label('Konum (Reverse)')
                    ->formatStateUsing(function ($state, ListingImage $record): string {
                        $loc = $record->reverseLocationLabel;
                        if (filled($loc)) {
                            return (string) $loc;
                        }

                        return ($record->gps_lat && $record->gps_lng) ? 'Bekliyor' : '—';
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->where(function ($q) use ($search): void {
                            $q->where('reverse_city', 'like', "%{$search}%")
                                ->orWhere('reverse_country_name', 'like', "%{$search}%");
                        });
                    })
                    ->placeholder('—')
                    ->badge(fn (ListingImage $record): bool => filled($record->reverseLocationLabel))
                    ->color(fn (ListingImage $record): string => filled($record->reverseLocationLabel) ? 'info' : 'gray')
                    ->icon(fn (ListingImage $record): ?string => filled($record->reverseLocationLabel) ? 'heroicon-o-map-pin' : null),

                IconColumn::make('is_cover')
                    ->label('Kapak')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable(),

                IconColumn::make('is_flagged')
                    ->label('AI Moderasyon')
                    ->boolean()
                    ->trueIcon('heroicon-o-flag')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn (ListingImage $record): string => $record->is_flagged
                        ? '⚠️ AI tarafından uygunsuz işaretlendi: '.($record->flagged_reason ?? 'sebep belirtilmedi')
                        : 'AI moderasyonundan geçti')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Yüklendi')
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (ListingImage $record): string => $record->created_at ? $record->created_at->diffForHumans() : '')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
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

                TernaryFilter::make('is_flagged')
                    ->label('AI moderasyonu')
                    ->trueLabel('İşaretlenenler (inceleme bekliyor)')
                    ->falseLabel('Temiz')
                    ->placeholder('Tümü'),

                Filter::make('with_exif')
                    ->label('EXIF metadata olan')
                    ->query(fn ($query) => $query->whereNotNull('exif_metadata')->where('exif_metadata', '!=', '{}')),

                Filter::make('no_exif')
                    ->label('EXIF olmayan (temiz)')
                    ->query(fn ($query) => $query->where(function ($q): void {
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

                Action::make('kapakYap')
                    ->label('Kapak Yap')
                    ->icon(Heroicon::OutlinedStar)
                    ->color('warning')
                    ->visible(fn (ListingImage $record): bool => ! $record->is_cover && $record->listing !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Kapak Görseli Olarak Belirle')
                    ->modalDescription('Bu görsel ilanın vitrin ve arama sonuçlarındaki ana kapak resmi yapılacak. Onaylıyor musunuz?')
                    ->action(function (ListingImage $record): void {
                        ListingImage::query()
                            ->where('listing_id', $record->listing_id)
                            ->update(['is_cover' => false]);

                        $record->update(['is_cover' => true]);

                        Notification::make()
                            ->title('Kapak görseli güncellendi')
                            ->success()
                            ->send();
                    }),

                Action::make('approve_flagged')
                    ->label('Onayla (İşareti Kaldır)')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ListingImage $record): bool => $record->is_flagged)
                    ->requiresConfirmation()
                    ->modalHeading('Görseli Onayla')
                    ->modalDescription('AI işareti kaldırılır. İlan "Onay bekliyor" durumundaysa tekrar "Aktif" yapılır.')
                    ->action(function (ListingImage $record): void {
                        $record->update(['is_flagged' => false, 'flagged_reason' => null]);

                        $listing = $record->listing;
                        if ($listing && $listing->status === ListingStatus::Beklemede) {
                            $listing->update(['status' => ListingStatus::Aktif]);
                        }

                        activity('image')
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->log('Admin AI işaretini onayladı, kaldırdı');

                        Notification::make()
                            ->title('Görsel onaylandı')
                            ->body('AI işareti kaldırıldı, ilan tekrar aktif.')
                            ->success()
                            ->send();
                    }),

                Action::make('sitedeGor')
                    ->label('İlana Git')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->visible(fn (ListingImage $record): bool => $record->listing !== null)
                    ->url(fn (ListingImage $record): string => route('listings.show', [$record->listing->id, $record->listing->slug]), shouldOpenInNewTab: true),

                Action::make('redact_gps')
                    ->label('GPS Sil')
                    ->icon('heroicon-o-map-pin')
                    ->color('warning')
                    ->visible(fn (ListingImage $record): bool => $record->had_gps)
                    ->requiresConfirmation()
                    ->modalHeading('GPS bilgisini sil')
                    ->modalDescription('EXIF metadata\'sından GPS koordinatları kaldırılacak. Görsel yayından etkilenmez.')
                    ->action(function (ListingImage $record): void {
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

                DeleteAction::make(),
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
                        ->action(function ($records): void {
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
                        ->action(function ($records): void {
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
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Henüz görsel bulunmuyor')
            ->emptyStateDescription('İlanlara fotoğraf eklendikçe görseller, EXIF metadata bilgileri, GPS koordinatları ve AI moderasyon durumları burada listelenir.')
            ->emptyStateIcon(Heroicon::OutlinedPhoto)
            ->emptyStateActions([
                Action::make('ilanlar')
                    ->label('Tüm İlanları Gör')
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->color('primary')
                    ->url(fn (): string => ListingResource::getUrl('index')),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            ListingImageStatsWidget::class,
        ];
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
