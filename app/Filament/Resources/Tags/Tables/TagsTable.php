<?php

namespace App\Filament\Resources\Tags\Tables;

use App\Filament\Resources\Listings\ListingResource;
use App\Filament\Resources\Tags\TagResource;
use App\Models\Tag;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class TagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Etiket Adı')
                    ->weight('bold')
                    ->formatStateUsing(fn (string $state): string => "#{$state}")
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Etiket kopyalandı'),

                TextColumn::make('slug')
                    ->label('Kısa Ad (Slug)')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('listings_count')
                    ->label('Bağlı İlan Sayısı')
                    ->counts('listings')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn (int $state): string => "{$state} ilan")
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Kayıt Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Son Güncelleme')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('has_listings')
                    ->label('İlan Varlığı')
                    ->placeholder('Tümü')
                    ->trueLabel('İlanı Olanlar')
                    ->falseLabel('Boşta Kalanlar (0 İlan)')
                    ->queries(
                        true: fn (Builder $q) => $q->has('listings'),
                        false: fn (Builder $q) => $q->doesntHave('listings'),
                        blank: fn (Builder $q) => $q,
                    ),
            ])
            ->recordActions([
                Action::make('ilanlariGor')
                    ->label('İlanları Gör')
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->color('gray')
                    ->url(fn (Tag $record): string => ListingResource::getUrl('index', ['tableFilters[tag_id][value]' => $record->id]))
                    ->visible(fn (Tag $record): bool => $record->listings()->count() > 0),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Henüz etiket bulunmuyor')
            ->emptyStateDescription('İlanların aramalarda ve vitrinde kolayca keşfedilmesi için etiketler ekleyebilir veya popüler hazır etiketleri sisteme yükleyebilirsiniz.')
            ->emptyStateIcon(Heroicon::OutlinedHashtag)
            ->emptyStateActions([
                Action::make('populerleriYukle')
                    ->label('Popüler Etiketleri Yükle')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('primary')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('Popüler Pazar Yeri Etiketlerini Yükle')
                    ->modalDescription('Gurbetçi pazar yerinde en çok kullanılan 15 temel etiket (Usta, Nakliye, Tercüme, İkinci El vb.) otomatik eklensin mi?')
                    ->action(function (): void {
                        $hazirEtiketler = [
                            'Usta & Tamirat',
                            'Evden Eve Nakliye',
                            'İkinci El Eşya',
                            'Yeminli Tercüme',
                            'Doktor & Sağlık',
                            'Avukat & Hukuk',
                            'Muhasebe & Vergi',
                            'Temizlik Hizmetleri',
                            'Oto Bakım & Onarım',
                            'Kiralık Daire',
                            'Bebek & Çocuk Bakımı',
                            'Tadilat & Boya',
                            'Türkçe Özel Ders',
                            'Düğün & Organizasyon',
                            'Bilişim & Web Hizmetleri',
                        ];

                        $eklenen = 0;
                        foreach ($hazirEtiketler as $etiketAdi) {
                            $slug = Str::slug($etiketAdi);
                            $varMi = Tag::query()->where('slug', $slug)->exists();
                            if (! $varMi) {
                                Tag::query()->create([
                                    'name' => $etiketAdi,
                                    'slug' => $slug,
                                ]);
                                $eklenen++;
                            }
                        }

                        Notification::make()
                            ->title("{$eklenen} adet popüler etiket sisteme eklendi.")
                            ->success()
                            ->send();
                    }),
                Action::make('yeniEtiket')
                    ->label('Yeni Etiket Oluştur')
                    ->icon(Heroicon::OutlinedPlus)
                    ->color('gray')
                    ->url(fn (): string => TagResource::getUrl('create')),
            ]);
    }
}
