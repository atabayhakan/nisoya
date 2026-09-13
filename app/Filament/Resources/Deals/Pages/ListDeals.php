<?php

namespace App\Filament\Resources\Deals\Pages;

use App\Enums\DealStatus;
use App\Filament\Resources\Deals\DealResource;
use App\Filament\Resources\Deals\Widgets\DealStatsWidget;
use App\Filament\Resources\Listings\ListingResource;
use App\Models\Deal;
use App\Support\Para;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class ListDeals extends ListRecords
{
    protected static string $resource = DealResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            DealStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $toplam = Deal::query()->count();
        $acik = Deal::query()->whereIn('status', [DealStatus::Teklif, DealStatus::Kabul])->count();
        $tamamlandi = Deal::query()->where('status', DealStatus::Tamamlandi)->count();
        $sorunlu = Deal::query()->where(fn (Builder $q) => $q->where('status', DealStatus::Sorunlu)->orWhereNotNull('dispute_note'))->count();
        $iptal = Deal::query()->where('status', DealStatus::Iptal)->count();

        return [
            'hepsi' => Tab::make('Tüm Anlaşmalar')
                ->badge((string) $toplam),

            'acik' => Tab::make('Açık & Bekleyenler')
                ->badge($acik > 0 ? (string) $acik : null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', [DealStatus::Teklif, DealStatus::Kabul])),

            'tamamlandi' => Tab::make('Tamamlananlar')
                ->badge($tamamlandi > 0 ? (string) $tamamlandi : null)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', DealStatus::Tamamlandi)),

            'sorunlu' => Tab::make('Sorunlu & İtirazlar')
                ->badge($sorunlu > 0 ? (string) $sorunlu : null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $q) => $q->where(fn (Builder $sq) => $sq->where('status', DealStatus::Sorunlu)->orWhereNotNull('dispute_note'))),

            'iptal' => Tab::make('İptal Edilenler')
                ->badge($iptal > 0 ? (string) $iptal : null)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', DealStatus::Iptal)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiTicaretRaporu')
                ->label('AI Ticaret & Güvenlik Raporu')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->modalHeading('Pazar Yeri Ticaret Hacmi ve Güvenli İşlem Analizi')
                ->modalDescription(function (): HtmlString {
                    $toplam = Deal::query()->count();
                    $tamamlandi = Deal::query()->where('status', DealStatus::Tamamlandi)->count();
                    $sorunlu = Deal::query()->where('status', DealStatus::Sorunlu)->count();
                    $acik = Deal::query()->whereIn('status', [DealStatus::Teklif, DealStatus::Kabul])->count();

                    $oran = $toplam > 0 ? (int) round(($tamamlandi / $toplam) * 100) : 0;
                    $sorunOran = $toplam > 0 ? (int) round(($sorunlu / $toplam) * 100) : 0;

                    // Para birimlerine göre hacim
                    $paraBirimleri = DB::table('deals')
                        ->where('status', DealStatus::Tamamlandi->value)
                        ->whereNotNull('amount')
                        ->whereNotNull('currency')
                        ->selectRaw('currency, sum(amount) as toplam_tutar, count(*) as adet')
                        ->groupBy('currency')
                        ->get();

                    $hacimHtml = '';
                    if ($paraBirimleri->isEmpty()) {
                        $hacimHtml = "<div class='text-xs text-gray-500 py-1'>Henüz tamamlanmış finansal anlaşma kaydı bulunmuyor.</div>";
                    } else {
                        foreach ($paraBirimleri as $pb) {
                            $tutarFmt = Para::bicimle((float) $pb->toplam_tutar) ?? '0';
                            $hacimHtml .= "<div class='flex items-center justify-between text-xs py-1 border-b border-gray-100 dark:border-gray-800'><span>{$pb->currency}</span><span class='font-bold text-gray-700 dark:text-gray-300'>{$tutarFmt} {$pb->currency} ({$pb->adet} işlem)</span></div>";
                        }
                    }

                    return new HtmlString(
                        "<div class='space-y-3.5 text-sm'>"
                        ."<div class='grid grid-cols-3 gap-2.5 text-center'>"
                        ."<div class='p-3 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-xl'>"
                        ."<div class='text-primary-700 dark:text-primary-400 font-bold text-lg'>{$toplam}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Toplam Kayıt ({$acik} Açık)</div>"
                        .'</div>'
                        ."<div class='p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl'>"
                        ."<div class='text-emerald-700 dark:text-emerald-400 font-bold text-lg'>%{$oran}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Başarı ({$tamamlandi} Tamamlandı)</div>"
                        .'</div>'
                        ."<div class='p-3 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-xl'>"
                        ."<div class='text-rose-700 dark:text-rose-400 font-bold text-lg'>{$sorunlu}</div>"
                        ."<div class='text-3xs text-gray-500 dark:text-gray-400 font-medium'>Sorunlu İtiraz (%{$sorunOran})</div>"
                        .'</div>'
                        .'</div>'
                        ."<div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700'>"
                        ."<div class='text-xs font-bold text-gray-900 dark:text-gray-100 mb-1.5'>💰 Tamamlanan Güvenli Ticaret Hacmi</div>"
                        .$hacimHtml
                        .'</div>'
                        ."<div class='p-3 bg-stone-50 dark:bg-stone-800/80 rounded-xl border border-stone-200 dark:border-stone-700 text-xs text-stone-600 dark:text-stone-300 space-y-1'>"
                        ."<strong class='text-stone-900 dark:text-stone-100'>🛡️ Güvenli Pazar Yeri & Doğrulanmış İşlem Rozeti:</strong>"
                        ."<p>Nisoya para akışını tutmaz; anlaşmalar üyelerin niyet beyanıdır. Başarıyla tamamlanan her anlaşma üye değerlendirmelerine <strong>'Doğrulanmış İşlem'</strong> rozeti kazandırarak sahte yorumları engeller. İtiraz bildirilen anlaşmalarda <em>'AI Arabuluculuk'</em> butonunu kullanarak adil bir moderasyon sağlayabilirsiniz.</p>"
                        .'</div>'
                        .'</div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Kapat'),

            Action::make('ilanlar')
                ->label('Tüm İlanlar')
                ->icon(Heroicon::OutlinedShoppingBag)
                ->color('gray')
                ->url(fn (): string => ListingResource::getUrl('index')),
        ];
    }
}
