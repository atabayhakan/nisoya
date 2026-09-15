<?php

namespace App\Filament\Resources\DiasporaReels\Pages;

use App\Filament\Concerns\GuardsAdminGeoContext;
use App\Filament\Resources\DiasporaReels\DiasporaReelResource;
use App\Filament\Resources\DiasporaReels\Widgets\DiasporaReelsStatsWidget;
use App\Jobs\GlobalCommand\RecalculateDiasporaRanking;
use App\Models\Country;
use App\Models\DiasporaAccount;
use App\Models\DiasporaReel;
use App\Services\Ai\CmsAiAssistant;
use App\Services\Diaspora\DiasporaIntelligenceService;
use App\Support\GlobalCommand\DiasporaDispatch;
use App\Support\GlobalCommand\GeoContext;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListDiasporaReels extends ListRecords
{
    use GuardsAdminGeoContext;

    protected static string $resource = DiasporaReelResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            DiasporaReelsStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tumunuSenkronizeEt')
                ->label('İzlenen Hesapları Tara')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->tooltip('Kayıtlı tüm diaspora hesaplarını tarayıp yeni reels videolarını aktarır.')
                ->action(function (DiasporaDispatch $dispatch): void {
                    $count = $dispatch->enqueue(auth()->user(), app(GeoContext::class)->apply(DiasporaAccount::query()));

                    Notification::make()
                        ->title('Tarama talepleri alındı')
                        ->body($count.' aktif ve doğrulanmış hesap kuyruğa alındı.')
                        ->success()
                        ->send();
                }),

            Action::make('siralaVePuanla')
                ->label('Akıllı Sıralama & Puanla')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('amber')
                ->tooltip('Beğeni, izlenme ve güncellik puanlarını hesaplayarak vitrin sıralamasını günceller.')
                ->action(function (): void {
                    abort_unless(auth()->user()?->isAdmin(), 403);
                    RecalculateDiasporaRanking::dispatch(auth()->id(), app(GeoContext::class)->selection())
                        ->onConnection(config('global-command.diaspora_sync_queue_connection', 'database'))->onQueue('diaspora-sync');

                    Notification::make()
                        ->title('Sıralama kuyruğa alındı')
                        ->body('Seçili coğrafi görünüm arka planda puanlanacak.')
                        ->success()
                        ->send();
                }),

            Action::make('aiReelsTaslagi')
                ->label('AI ile Hızlı Ekle (Claude)')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->modalHeading('Claude ile Diaspora Reels Hikayesi Oluştur')
                ->modalDescription('Diasporadaki bir etkinlik, buluşma veya lezzet konusunu yazın. Claude sizin için başlık, samimi hikaye notu ve şehir bilgilerini hazırlasın.')
                ->form([
                    TextInput::make('odak')
                        ->label('Konu / Odak Noktası')
                        ->placeholder('Örn: Köln\'de Türk gençlik spor buluşması veya Bişkek Türk börekçisi')
                        ->required(),
                    TextInput::make('instagram_url')
                        ->label('Instagram Reel / Gönderi Linki')
                        ->placeholder('https://www.instagram.com/reel/C8xABC12345/')
                        ->required(),
                    Select::make('country_code')
                        ->label('Ülke (Opsiyonel)')
                        ->options(function () {
                            return Country::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn (Country $c) => [$c->code => ($c->emoji ? $c->emoji.' ' : '').$c->name_tr]);
                        })
                        ->searchable()
                        ->nullable(),
                    TextInput::make('city')
                        ->label('Şehir (Opsiyonel)')
                        ->placeholder('Köln, Bişkek, Berlin...'),
                ])
                ->action(function (array $data, CmsAiAssistant $assistant, DiasporaIntelligenceService $intelligence): void {
                    $story = $assistant->generateDiasporaStory(
                        (string) $data['odak'],
                        isset($data['country_code']) ? (string) $data['country_code'] : null,
                        isset($data['city']) ? (string) $data['city'] : null
                    );

                    $countryCode = filled($data['country_code'] ?? null)
                        ? (string) $data['country_code']
                        : ($story['country_code'] ?? null);

                    $city = filled($data['city'] ?? null)
                        ? (string) $data['city']
                        : ($story['suggested_city'] ?? null);

                    $enriched = $intelligence->enrich([
                        'title' => $story['title'] ?? (string) $data['odak'],
                        'caption' => $story['caption'] ?? null,
                        'instagram_url' => (string) $data['instagram_url'],
                        'instagram_username' => $story['suggested_username'] ?? null,
                        'country_code' => $countryCode,
                        'city' => $city,
                    ]);

                    DiasporaReel::create([
                        'title' => (string) $enriched['title'],
                        'caption' => $enriched['caption'] ?? null,
                        'instagram_url' => (string) $data['instagram_url'],
                        'country_code' => $enriched['country_code'] ?? $countryCode,
                        'city' => $enriched['city'] ?? $city,
                        'category' => $enriched['category'] ?? DiasporaReel::CATEGORY_GENEL,
                        'safety_score' => $enriched['safety_score'] ?? 100,
                        'safety_status' => $enriched['safety_status'] ?? 'safe',
                        'instagram_username' => $enriched['instagram_username'] ?? ($story['suggested_username'] ?? null),
                        'status' => DiasporaReel::STATUS_DRAFT,
                        'is_active' => false,
                    ]);

                    Notification::make()
                        ->title('Diaspora Reel hikayesi oluşturuldu')
                        ->body('Başlık ve açıklama taslak olarak hazırlandı. Yayınlamadan önce kaynak bilgilerini inceleyin.')
                        ->success()
                        ->send();
                }),

            CreateAction::make()
                ->label('Manuel Reel Ekle')
                ->icon(Heroicon::OutlinedPlus),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $draftCount = app(GeoContext::class)->apply(DiasporaReel::query())->where('status', DiasporaReel::STATUS_DRAFT)->count();

        return [
            'hepsi' => Tab::make('Tüm Paylaşımlar')
                ->badge(app(GeoContext::class)->apply(DiasporaReel::query())->count() ?: null),

            'onay_bekleyen' => Tab::make('📥 Onay Bekleyenler (Taslak)')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', DiasporaReel::STATUS_DRAFT))
                ->badge($draftCount ?: null)
                ->badgeColor('warning'),

            'yayinda' => Tab::make('Yayında (Aktif)')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_active', true)->where('status', DiasporaReel::STATUS_PUBLISHED))
                ->badge(app(GeoContext::class)->apply(DiasporaReel::query())->where('is_active', true)->where('status', DiasporaReel::STATUS_PUBLISHED)->count() ?: null)
                ->badgeColor('success'),

            'one_cikan' => Tab::make('Öne Çıkanlar')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_featured', true))
                ->badge(app(GeoContext::class)->apply(DiasporaReel::query())->where('is_featured', true)->count() ?: null)
                ->badgeColor('warning'),

        ];
    }
}
