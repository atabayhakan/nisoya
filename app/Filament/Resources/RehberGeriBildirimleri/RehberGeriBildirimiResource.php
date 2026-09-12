<?php

namespace App\Filament\Resources\RehberGeriBildirimleri;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\RehberGeriBildirimleri\Pages\ListRehberGeriBildirimleri;
use App\Filament\Resources\TemsilcilikIslemleri\TemsilcilikIslemiResource;
use App\Models\RehberGeriBildirimi;
use App\Services\Ai\CountryGuideAiAssistant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Ülke Rehberi — "bu bilgi güncel mi?" geri bildirim kuyruğu.
 *
 * SALT-OKUNUR kuyruk (form yok, oluşturma yok): kayıtlar siteden gelir,
 * admin yalnız okur, işaretler ve gerekirse ilgili işlem içeriğine gidip
 * düzeltir. Rozet incelenmemiş sayısını gösterir — sıfırlanması beklenen
 * bir gelen kutusudur, arşiv değil.
 */
class RehberGeriBildirimiResource extends Resource
{
    use RestrictsToAdmins;

    protected static ?string $model = RehberGeriBildirimi::class;

    protected static ?string $slug = 'rehber-geri-bildirimleri';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Ülke Rehberi';

    protected static ?string $navigationLabel = 'Geri Bildirimler';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return 'geri bildirim';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Geri Bildirimler';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (RehberGeriBildirimi::query()->where('incelendi', false)->count() ?: '');
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('islem.temsilcilik.ad')->label('Temsilcilik'),
                TextColumn::make('islem.islemTuru.ad')->label('İşlem'),
                TextColumn::make('tur')
                    ->label('Tür')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'oneri' ? 'info' : 'warning')
                    ->formatStateUsing(fn (RehberGeriBildirimi $r): string => $r->turEtiketi()),
                TextColumn::make('metin')->label('Mesaj')->wrap()->limit(120)->placeholder('—'),
                TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                IconColumn::make('incelendi')->label('İncelendi')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('incelendi')->label('İncelendi'),
            ])
            ->recordActions([
                Action::make('aiAnaliz')
                    ->label('AI Analiz')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('warning')
                    ->modalHeading(fn (RehberGeriBildirimi $r): string => 'AI Geri Bildirim Analizi #'.$r->id)
                    ->modalDescription(function (RehberGeriBildirimi $r, CountryGuideAiAssistant $assistant): HtmlString {
                        $procedureName = $r->islem && $r->islem->islemTuru ? $r->islem->islemTuru->ad : 'Konsolosluk İşlemi';
                        $currentNotes = $r->islem ? $r->islem->notlar : null;
                        $eval = $assistant->evaluateConsularFeedback((string) $r->metin, $procedureName, $currentNotes);

                        $color = match ($eval['oncelik']) {
                            'yuksek' => 'text-rose-600 dark:text-rose-400',
                            'orta' => 'text-amber-600 dark:text-amber-400',
                            default => 'text-emerald-600 dark:text-emerald-400',
                        };

                        return new HtmlString(
                            "<div class='space-y-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-sm'>"
                            ."<div class='flex items-center justify-between font-bold'>"
                            ."<span>Öncelik: <span class='{$color} uppercase'>".e($eval['oncelik']).'</span></span>'
                            ."<span class='text-xs text-gray-500'>Geçerlilik: ".e($eval['gecerlilik']).'</span>'
                            .'</div>'
                            ."<div><strong class='text-xs text-gray-500'>Öneri Özeti:</strong><p class='text-xs mt-0.5 text-gray-800 dark:text-gray-200'>".e($eval['oneri_ozeti']).'</p></div>'
                            ."<div class='pt-2 border-t border-gray-200 dark:border-gray-700'><strong class='text-xs text-gray-500'>Önerilen Aksiyon:</strong><p class='text-xs mt-0.5 text-primary-600 dark:text-primary-400 font-medium'>".e($eval['aksiyon_onerisi']).'</p></div>'
                            .'</div>'
                        );
                    })
                    ->action(function (RehberGeriBildirimi $r): void {
                        $r->update(['incelendi' => true]);
                        Notification::make()->title('Geri bildirim incelendi olarak işaretlendi')->success()->send();
                    }),
                Action::make('islemeGit')
                    ->label('İçeriğe git')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (RehberGeriBildirimi $r): string => TemsilcilikIslemiResource::getUrl('edit', ['record' => $r->temsilcilik_islemi_id])),
                Action::make('incelendiIsaretle')
                    ->label('İncelendi')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (RehberGeriBildirimi $r): bool => ! $r->incelendi)
                    ->action(fn (RehberGeriBildirimi $r) => $r->update(['incelendi' => true])),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRehberGeriBildirimleri::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
