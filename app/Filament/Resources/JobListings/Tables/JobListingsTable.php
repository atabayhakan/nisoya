<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobListings\Tables;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Filament\Resources\JobListings\JobListingResource;
use App\Models\JobListing;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class JobListingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('company.logo_path')
                    ->label('Şirket Logo')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=J&background=0284c7&color=ffffff')
                    ->size(40),

                TextColumn::make('title')
                    ->label('Pozisyon / Başlık')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(function (JobListing $record): string {
                        $company = $record->company ? $record->company->name : 'Şirket Belirtilmedi';
                        $cat = $record->category ? " • {$record->category->name}" : '';

                        return "{$company}{$cat}";
                    }),

                TextColumn::make('employment_type')
                    ->label('Çalışma Şekli')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('location')
                    ->label('Konum')
                    ->icon('heroicon-m-map-pin')
                    ->state(function (JobListing $record): string {
                        if ($record->is_remote) {
                            return '🏠 Uzaktan (Remote)';
                        }
                        $parts = [];
                        if (filled($record->city)) {
                            $parts[] = $record->city;
                        }
                        if ($record->country) {
                            $emoji = $record->country->emoji ?? '';
                            $parts[] = trim("{$emoji} {$record->country->name_tr}");
                        } elseif (filled($record->country_code)) {
                            $parts[] = strtoupper((string) $record->country_code);
                        }

                        return ! empty($parts) ? implode(', ', $parts) : '—';
                    })
                    ->toggleable(),

                TextColumn::make('salary')
                    ->label('Maaş')
                    ->state(fn (JobListing $record): string => $record->salaryLabel() ?? 'Görüşülür')
                    ->badge()
                    ->color(fn (JobListing $record): string => $record->salary_min !== null || $record->salary_max !== null ? 'success' : 'gray')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('salary_min', $direction)),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->sortable(),

                TextColumn::make('applications_count')
                    ->label('Başvuru')
                    ->counts('applications')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'info' : 'gray')
                    ->sortable(),

                IconColumn::make('is_featured')
                    ->label('Öne Çıkan')
                    ->boolean()
                    ->trueIcon('heroicon-m-sparkles')
                    ->falseIcon('heroicon-m-minus')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable(),

                TextColumn::make('views_count')
                    ->label('Görüntülenme')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state): string => "👁️ {$state}")
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deadline')
                    ->label('Son Başvuru')
                    ->date('d.m.Y')
                    ->placeholder('Süresiz')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Yayın Durumu')
                    ->options(JobStatus::class),

                SelectFilter::make('employment_type')
                    ->label('Çalışma Tipi')
                    ->options(EmploymentType::class),

                SelectFilter::make('company_id')
                    ->label('Şirket')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('job_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_remote')
                    ->label('Uzaktan Çalışma')
                    ->trueLabel('Yalnızca Uzaktan (Remote)')
                    ->falseLabel('Ofis / Hibrit'),

                TernaryFilter::make('is_featured')
                    ->label('Öne Çıkanlar')
                    ->trueLabel('Öne Çıkan İlanlar')
                    ->falseLabel('Standart İlanlar'),
            ])
            ->actions([
                Action::make('durumGuncelle')
                    ->label('Durum Değiştir')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->color('primary')
                    ->modalHeading(fn (JobListing $record): string => "İlan Durumunu Güncelle: {$record->title}")
                    ->form([
                        Select::make('status')
                            ->label('Yeni Durum')
                            ->options(JobStatus::class)
                            ->default(fn (JobListing $record) => $record->status)
                            ->required(),
                    ])
                    ->action(function (JobListing $record, array $data): void {
                        $record->status = $data['status'];
                        $record->save();
                        Notification::make()->title('İlan yayın durumu güncellendi.')->success()->send();
                    }),

                Action::make('toggleFeatured')
                    ->label(fn (JobListing $record): string => $record->is_featured ? 'Vitrinden Çıkar' : 'Öne Çıkar')
                    ->icon('heroicon-m-sparkles')
                    ->color(fn (JobListing $record): string => $record->is_featured ? 'danger' : 'warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn (JobListing $record): string => $record->is_featured ? 'Öne Çıkarmayı Kaldır' : 'İlanı Öne Çıkar')
                    ->modalDescription(fn (JobListing $record): string => $record->is_featured
                        ? 'İlan vitrinden çıkarılacaktır. Devam edilsin mi?'
                        : 'İlan 30 gün süreyle listenin ve ana sayfanın en üstünde öne çıkarılacaktır.')
                    ->action(function (JobListing $record): void {
                        $record->is_featured = ! $record->is_featured;
                        $record->featured_until = $record->is_featured ? now()->addDays(30) : null;
                        $record->save();

                        $msg = $record->is_featured
                            ? "'{$record->title}' ilanı 30 gün süreyle öne çıkarıldı."
                            : "'{$record->title}' öne çıkarma durumu kaldırıldı.";

                        Notification::make()->title($msg)->success()->send();
                    }),

                Action::make('detay')
                    ->label('İncele')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading(fn (JobListing $record): string => "İş İlanı Detayı: {$record->title}")
                    ->modalDescription(function (JobListing $record): HtmlString {
                        $companyName = $record->company ? e($record->company->name) : 'Şirket Belirtilmedi';
                        $catName = $record->category ? e($record->category->name) : 'Genel';
                        $type = $record->employment_type ? e($record->employment_type->getLabel()) : '—';
                        $exp = $record->experience_level ? e($record->experience_level->getLabel()) : 'Farketmez';
                        $salary = e($record->salaryLabel() ?? 'Görüşülür');
                        $location = $record->is_remote
                            ? '🏠 Uzaktan (Remote)'
                            : e(($record->city ? "{$record->city}, " : '').($record->country ? $record->country->name_tr : ($record->country_code ?? '—')));
                        $deadline = $record->deadline ? $record->deadline->format('d.m.Y') : 'Süresiz';
                        $desc = nl2br(e($record->description));

                        return new HtmlString(
                            "<div class='space-y-3.5 text-sm'>"
                            ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-1.5'>"
                            ."<div><strong>İşveren Şirket:</strong> {$companyName} • <strong>Kategori:</strong> {$catName}</div>"
                            ."<div><strong>Çalışma Şekli:</strong> {$type} • <strong>Deneyim:</strong> {$exp} • <strong>Kontenjan:</strong> {$record->positions} kişi</div>"
                            ."<div><strong>Konum:</strong> {$location}</div>"
                            ."<div><strong>Maaş Aralığı:</strong> <span class='font-bold text-emerald-600'>{$salary}</span> • <strong>Son Başvuru:</strong> {$deadline}</div>"
                            .'</div>'
                            ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs'>"
                            ."<strong class='text-gray-900 dark:text-gray-100 block mb-1'>İş Tanımı & Nitelikler:</strong>"
                            ."<div class='text-gray-700 dark:text-gray-300 leading-relaxed max-h-60 overflow-y-auto'>{$desc}</div>"
                            .'</div>'
                            .'</div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                Action::make('basvurulariGor')
                    ->label('Adaylar')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->color('info')
                    ->visible(fn (JobListing $record): bool => $record->applications()->count() > 0)
                    ->modalHeading(fn (JobListing $record): string => "Başvuran Adaylar: {$record->title}")
                    ->modalDescription(function (JobListing $record): HtmlString {
                        $apps = $record->applications()->with('applicant')->latest()->limit(20)->get();
                        $rows = '';
                        foreach ($apps as $app) {
                            $name = $app->applicant ? e($app->applicant->name) : 'Aday';
                            $email = $app->applicant ? e($app->applicant->email) : '—';
                            $status = e($app->status->getLabel());
                            $date = $app->created_at ? $app->created_at->format('d.m.Y H:i') : '—';
                            $rows .= "<div class='flex items-center justify-between text-xs py-1.5 border-b border-gray-100 dark:border-gray-800'><span><strong>{$name}</strong> ({$email})</span><span class='text-gray-500'>{$date} • <strong class='text-primary-600'>{$status}</strong></span></div>";
                        }

                        return new HtmlString(
                            "<div class='space-y-2 text-sm'>"
                            ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700'>"
                            .$rows
                            .'</div>'
                            .'</div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                EditAction::make(),

                Action::make('sitedeGor')
                    ->label('İlana Git')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (JobListing $record): string => route('jobs.show', ['job' => $record->id, 'slug' => $record->slug]), shouldOpenInNewTab: true),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('topluAktif')
                    ->label('Seçilenleri Yayına Al')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record instanceof JobListing) {
                                $record->status = JobStatus::Aktif;
                                $record->save();
                                $count++;
                            }
                        }
                        Notification::make()->title("{$count} ilan başarıyla yayına alındı.")->success()->send();
                    }),

                BulkAction::make('topluKapat')
                    ->label('Seçilenleri Kapat')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record instanceof JobListing) {
                                $record->status = JobStatus::Kapali;
                                $record->save();
                                $count++;
                            }
                        }
                        Notification::make()->title("{$count} ilan kapatıldı.")->warning()->send();
                    }),

                BulkAction::make('topluOneCikar')
                    ->label('Seçilenleri Öne Çıkar (30 Gün)')
                    ->icon('heroicon-m-sparkles')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record instanceof JobListing) {
                                $record->is_featured = true;
                                $record->featured_until = now()->addDays(30);
                                $record->save();
                                $count++;
                            }
                        }
                        Notification::make()->title("{$count} ilan vitrinde öne çıkarıldı.")->success()->send();
                    }),

                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Henüz İş İlanı Bulunmuyor')
            ->emptyStateDescription('İş & Kariyer Portalı üzerinde işverenlerin açtığı açık pozisyonlar, aday başvuruları, maaş ve çalışma koşulları burada yönetilir.')
            ->emptyStateIcon(Heroicon::OutlinedBriefcase)
            ->emptyStateActions([
                Action::make('yeniIlan')
                    ->label('Yeni İş İlanı Yayınla')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => JobListingResource::getUrl('create')),
            ]);
    }
}
