<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyReviews\Tables;

use App\Enums\ReviewStatus;
use App\Filament\Resources\CompanyReviews\CompanyReviewResource;
use App\Models\CompanyReview;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class CompanyReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('company.logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=C&background=0284c7&color=ffffff')
                    ->size(36),

                TextColumn::make('company.name')
                    ->label('Şirket')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(function (CompanyReview $record): string {
                        $sector = $record->company ? $record->company->sector : null;
                        $city = $record->company ? $record->company->city : null;
                        $parts = array_filter([$sector, $city]);

                        return ! empty($parts) ? implode(' • ', $parts) : 'Kurumsal Profil';
                    }),

                TextColumn::make('reviewer.name')
                    ->label('Değerlendiren')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable()
                    ->description(fn (CompanyReview $record): ?string => $record->reviewer ? $record->reviewer->email : null),

                TextColumn::make('rating')
                    ->label('Puan')
                    ->state(fn (CompanyReview $record): string => "⭐ {$record->rating} / 5")
                    ->badge()
                    ->color(fn (CompanyReview $record): string => match ($record->rating) {
                        5, 4 => 'success',
                        3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('comment')
                    ->label('Yorum')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? Str::limit($state, 50) : '—')
                    ->tooltip(fn (CompanyReview $record): ?string => $record->comment)
                    ->wrap(),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Yayın Durumu')
                    ->options(ReviewStatus::class),

                SelectFilter::make('rating')
                    ->label('Puanlama')
                    ->options([
                        5 => '⭐⭐⭐⭐⭐ (5 Yıldız)',
                        4 => '⭐⭐⭐⭐ (4 Yıldız)',
                        3 => '⭐⭐⭐ (3 Yıldız)',
                        2 => '⭐⭐ (2 Yıldız)',
                        1 => '⭐ (1 Yıldız)',
                    ]),

                SelectFilter::make('company_id')
                    ->label('Şirket')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Action::make('toggleStatus')
                    ->label(fn (CompanyReview $record): string => $record->status === ReviewStatus::Yayinda ? 'Gizle' : 'Yayına Al')
                    ->icon(fn (CompanyReview $record): string => $record->status === ReviewStatus::Yayinda ? 'heroicon-m-eye-slash' : 'heroicon-m-check-badge')
                    ->color(fn (CompanyReview $record): string => $record->status === ReviewStatus::Yayinda ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (CompanyReview $record): string => $record->status === ReviewStatus::Yayinda ? 'Değerlendirmeyi Gizle' : 'Değerlendirmeyi Yayına Al')
                    ->modalDescription(fn (CompanyReview $record): string => $record->status === ReviewStatus::Yayinda
                        ? 'Yorum şirketin profilinden ve ortalama puanından gizlenecektir.'
                        : 'Yorum onaylanarak şirketin profilinde yayına alınacaktır.')
                    ->action(function (CompanyReview $record): void {
                        $record->status = $record->status === ReviewStatus::Yayinda ? ReviewStatus::Gizli : ReviewStatus::Yayinda;
                        $record->save();

                        $msg = $record->status === ReviewStatus::Yayinda
                            ? 'Değerlendirme yayına alındı.'
                            : 'Değerlendirme moderasyon gereği gizlendi.';

                        Notification::make()->title($msg)->success()->send();
                    }),

                Action::make('detay')
                    ->label('İncele')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading('Şirket Değerlendirmesi Detayı')
                    ->modalDescription(function (CompanyReview $record): HtmlString {
                        $companyName = $record->company ? e($record->company->name) : 'Belirtilmedi';
                        $reviewerName = $record->reviewer ? e($record->reviewer->name) : 'Aday';
                        $reviewerEmail = $record->reviewer ? e($record->reviewer->email) : '—';
                        $ratingStr = "⭐ {$record->rating} / 5";
                        $statusStr = e($record->status->getLabel());
                        $date = $record->created_at ? $record->created_at->format('d.m.Y H:i') : '—';
                        $comment = nl2br(e($record->comment ?? 'Yorum girilmemiş.'));

                        return new HtmlString(
                            "<div class='space-y-3.5 text-sm'>"
                            ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-1.5'>"
                            ."<div><strong>Şirket:</strong> {$companyName} • <strong>Puan:</strong> <strong class='text-amber-500'>{$ratingStr}</strong></div>"
                            ."<div><strong>Değerlendiren:</strong> {$reviewerName} ({$reviewerEmail})</div>"
                            ."<div><strong>Tarih:</strong> {$date} • <strong>Durum:</strong> {$statusStr}</div>"
                            .'</div>'
                            ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs'>"
                            ."<strong class='text-gray-900 dark:text-gray-100 block mb-1'>Adayın Yorumu:</strong>"
                            ."<div class='text-gray-700 dark:text-gray-300 leading-relaxed max-h-60 overflow-y-auto'>{$comment}</div>"
                            .'</div>'
                            .'</div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                Action::make('sirketSayfasi')
                    ->label('Şirkete Git')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->visible(fn (CompanyReview $record): bool => $record->company !== null)
                    ->url(fn (CompanyReview $record): string => route('companies.show', $record->company->slug), shouldOpenInNewTab: true),

                EditAction::make(),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('topluYayinla')
                    ->label('Seçilenleri Yayına Al')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record instanceof CompanyReview) {
                                $record->status = ReviewStatus::Yayinda;
                                $record->save();
                                $count++;
                            }
                        }
                        Notification::make()->title("{$count} değerlendirme yayına alındı.")->success()->send();
                    }),

                BulkAction::make('topluGizle')
                    ->label('Seçilenleri Gizle')
                    ->icon('heroicon-m-eye-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record instanceof CompanyReview) {
                                $record->status = ReviewStatus::Gizli;
                                $record->save();
                                $count++;
                            }
                        }
                        Notification::make()->title("{$count} değerlendirme gizlendi.")->warning()->send();
                    }),

                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Henüz Şirket Değerlendirmesi Bulunmuyor')
            ->emptyStateDescription('Adaylar iş başvurusu yaptıkları veya çalıştıkları kurumsal firmalar hakkında geri bildirim paylaştığında değerlendirmeler, puanlar ve yorumlar burada listelenir.')
            ->emptyStateIcon(Heroicon::OutlinedStar)
            ->emptyStateActions([
                Action::make('yeniYorum')
                    ->label('Yeni Değerlendirme Ekle')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => CompanyReviewResource::getUrl('create')),
            ]);
    }
}
