<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Tables;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use App\Models\Country;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Illuminate\Support\Str;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=C&background=0284c7&color=ffffff')
                    ->size(40),

                TextColumn::make('name')
                    ->label('Şirket Adı')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(function (Company $record): ?string {
                        if (filled($record->tagline)) {
                            return Str::limit($record->tagline, 36);
                        }
                        if (filled($record->website)) {
                            $host = parse_url((string) $record->website, PHP_URL_HOST);

                            return $host ?: Str::limit((string) $record->website, 30);
                        }

                        return null;
                    }),

                TextColumn::make('user.name')
                    ->label('Sahip (Yönetici)')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Company $record): ?string => $record->user ? $record->user->email : null),

                TextColumn::make('sector')
                    ->label('Sektör')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('location')
                    ->label('Konum')
                    ->icon('heroicon-m-map-pin')
                    ->state(function (Company $record): string {
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

                TextColumn::make('job_listings_count')
                    ->label('İlan')
                    ->counts('jobListings')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('rating_summary')
                    ->label('Puan & Yorum')
                    ->state(function (Company $record): string {
                        $count = $record->reviews()->where('status', 'yayinda')->count();
                        if ($count === 0) {
                            return '—';
                        }
                        $avg = $record->averageRating();

                        return "⭐ {$avg} ({$count})";
                    })
                    ->badge()
                    ->color(fn (Company $record): string => $record->reviews()->where('status', 'yayinda')->count() > 0 ? 'warning' : 'gray')
                    ->toggleable(),

                IconColumn::make('is_verified')
                    ->label('Doğrulandı')
                    ->boolean()
                    ->trueIcon('heroicon-m-check-badge')
                    ->falseIcon('heroicon-m-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Kayıt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_verified')
                    ->label('Doğrulama Durumu')
                    ->trueLabel('Doğrulanmış Firmalar')
                    ->falseLabel('Onay Bekleyenler'),

                SelectFilter::make('country_code')
                    ->label('Ülke')
                    ->options(fn () => Country::query()->where('is_active', true)->orderBy('sort_order')->pluck('name_tr', 'code')->toArray())
                    ->searchable(),

                SelectFilter::make('company_size')
                    ->label('Çalışan Sayısı')
                    ->options([
                        '1-10' => '1 - 10 Çalışan',
                        '11-50' => '11 - 50 Çalışan',
                        '51-200' => '51 - 200 Çalışan',
                        '201-500' => '201 - 500 Çalışan',
                        '500+' => '500+ Çalışan',
                    ]),
            ])
            ->actions([
                Action::make('toggleVerify')
                    ->label(fn (Company $record): string => $record->is_verified ? 'Onayı Kaldır' : 'Doğrula')
                    ->icon(fn (Company $record): string => $record->is_verified ? 'heroicon-m-x-circle' : 'heroicon-m-check-badge')
                    ->color(fn (Company $record): string => $record->is_verified ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Company $record): string => $record->is_verified ? "'{$record->name}' Onayını Kaldır" : "'{$record->name}' Şirketini Doğrula")
                    ->modalDescription(fn (Company $record): string => $record->is_verified
                        ? 'Şirketin mavi doğrulama rozeti kaldırılacaktır. Onaylıyor musunuz?'
                        : 'Şirkete mavi kurumsal doğrulama rozeti atanacaktır. Onaylıyor musunuz?')
                    ->action(function (Company $record): void {
                        $record->is_verified = ! $record->is_verified;
                        $record->save();

                        $msg = $record->is_verified
                            ? "'{$record->name}' başarıyla doğrulandı ve kurumsal rozet tanımlandı."
                            : "'{$record->name}' doğrulama rozeti kaldırıldı.";

                        Notification::make()->title($msg)->success()->send();
                    }),

                Action::make('detay')
                    ->label('İncele')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading(fn (Company $record): string => "Kurumsal Profil: {$record->name}")
                    ->modalDescription(function (Company $record): HtmlString {
                        $ownerName = $record->user ? e($record->user->name) : 'Belirtilmedi';
                        $ownerEmail = $record->user ? e($record->user->email) : '—';
                        $website = $record->website ? "<a href='".e($record->website)."' target='_blank' class='text-primary-600 underline'>".e($record->website).'</a>' : '—';
                        $video = $record->video_url ? "<a href='".e($record->video_url)."' target='_blank' class='text-primary-600 underline'>Video İzle</a>" : '—';
                        $location = e(($record->city ? "{$record->city}, " : '').($record->country ? $record->country->name_tr : ($record->country_code ?? '—')));
                        $tagline = $record->tagline ? e($record->tagline) : '—';
                        $about = $record->about ? nl2br(e($record->about)) : 'Tanıtım yazısı girilmemiş.';
                        $social = [];
                        if ($record->social_linkedin) {
                            $social[] = "<a href='".e($record->social_linkedin)."' target='_blank' class='text-blue-600 underline'>LinkedIn</a>";
                        }
                        if ($record->social_instagram) {
                            $social[] = "<span class='text-pink-600'>Instagram: ".e($record->social_instagram).'</span>';
                        }
                        if ($record->social_whatsapp) {
                            $social[] = "<a href='".e($record->social_whatsapp)."' target='_blank' class='text-emerald-600 underline'>WhatsApp</a>";
                        }
                        if ($record->social_twitter) {
                            $social[] = "<a href='".e($record->social_twitter)."' target='_blank' class='text-sky-600 underline'>X/Twitter</a>";
                        }
                        $socialHtml = ! empty($social) ? implode(' • ', $social) : 'Bağlı sosyal medya hesabı yok.';

                        return new HtmlString(
                            "<div class='space-y-3.5 text-sm'>"
                            ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 space-y-1.5'>"
                            ."<div><strong>Şirket Sahibi:</strong> {$ownerName} ({$ownerEmail})</div>"
                            ."<div><strong>Konum:</strong> {$location}</div>"
                            .'<div><strong>Sektör & Büyüklük:</strong> '.e($record->sector ?? '—').' • '.e($record->company_size ?? '—').' (Kuruluş: '.e((string) ($record->founded_year ?? '—')).')</div>'
                            ."<div><strong>Web Sitesi:</strong> {$website}</div>"
                            ."<div><strong>Tanıtım Videosu:</strong> {$video}</div>"
                            ."<div><strong>Sosyal Kanallar:</strong> {$socialHtml}</div>"
                            .'</div>'
                            ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs'>"
                            ."<strong class='text-gray-900 dark:text-gray-100 block mb-1'>Slogan:</strong> <p class='italic text-gray-700 dark:text-gray-300'>{$tagline}</p>"
                            ."<strong class='text-gray-900 dark:text-gray-100 block mt-2 mb-1'>Hakkında:</strong> <div class='text-gray-700 dark:text-gray-300'>{$about}</div>"
                            .'</div>'
                            .'</div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                EditAction::make(),

                Action::make('sitedeGor')
                    ->label('Sayfaya Git')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Company $record): string => route('companies.show', $record->slug), shouldOpenInNewTab: true),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('topluDogrula')
                    ->label('Seçilenleri Doğrula')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record instanceof Company) {
                                $record->is_verified = true;
                                $record->save();
                                $count++;
                            }
                        }
                        Notification::make()->title("{$count} şirket başarıyla doğrulandı.")->success()->send();
                    }),

                BulkAction::make('topluOnayKaldir')
                    ->label('Seçilenlerin Doğrulamasını Kaldır')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record instanceof Company) {
                                $record->is_verified = false;
                                $record->save();
                                $count++;
                            }
                        }
                        Notification::make()->title("{$count} şirketin doğrulaması kaldırıldı.")->warning()->send();
                    }),

                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Henüz Kayıtlı Şirket Bulunmuyor')
            ->emptyStateDescription('İş & Kariyer Portalı üzerinde işverenlerin oluşturduğu kurumsal şirket profilleri, çalışan değerlendirmeleri ve yayındaki iş ilanları burada yönetilir.')
            ->emptyStateIcon(Heroicon::OutlinedBuildingOffice2)
            ->emptyStateActions([
                Action::make('yeniSirket')
                    ->label('Yeni Şirket Profili Oluştur')
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => CompanyResource::getUrl('create')),
            ]);
    }
}
