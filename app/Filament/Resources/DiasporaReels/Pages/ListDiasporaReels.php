<?php

namespace App\Filament\Resources\DiasporaReels\Pages;

use App\Filament\Resources\DiasporaReels\DiasporaReelResource;
use App\Filament\Resources\DiasporaReels\Widgets\DiasporaReelsStatsWidget;
use App\Models\Country;
use App\Models\DiasporaReel;
use App\Services\Ai\CmsAiAssistant;
use App\Services\Diaspora\DiasporaIntelligenceService;
use App\Services\Diaspora\DiasporaRankingEngine;
use App\Services\Diaspora\DiasporaSyncEngine;
use Database\Seeders\DiasporaReelSeeder;
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
                ->action(function (DiasporaSyncEngine $syncEngine): void {
                    $res = $syncEngine->syncAll();

                    Notification::make()
                        ->title('Senkronizasyon Tamamlandı')
                        ->body("{$res['accounts_count']} hesap tarandı, {$res['total_created']} yeni içerik aktarıldı ({$res['autopilot_published']} otopilot yayında).")
                        ->success()
                        ->send();
                }),

            Action::make('siralaVePuanla')
                ->label('Akıllı Sıralama & Puanla')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('amber')
                ->tooltip('Beğeni, izlenme ve güncellik puanlarını hesaplayarak vitrin sıralamasını günceller.')
                ->action(function (DiasporaRankingEngine $rankingEngine): void {
                    $res = $rankingEngine->recalculateAndRank();

                    Notification::make()
                        ->title('Etkileşim Sıralaması Güncellendi')
                        ->body("{$res['recalculated_count']} içerik puanlandı. {$res['promoted_featured']} içerik öne çıkan geniş bento karta yükseltildi.")
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
                        : ($story['country_code'] ?? 'DE');

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
                        'status' => DiasporaReel::STATUS_PUBLISHED,
                        'is_active' => true,
                    ]);

                    Notification::make()
                        ->title('Diaspora Reel hikayesi oluşturuldu')
                        ->body('Claude AI başlık ve açıklamayı başarıyla hazırladı ve vitrine ekledi.')
                        ->success()
                        ->send();
                }),

            Action::make('ornekleriYukle')
                ->label('Örnekleri Yükle (Seed)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->tooltip('Ana sayfa için 6 adet örnek diaspora reels ve etkinlik kaydını yükler')
                ->action(function (): void {
                    (new DiasporaReelSeeder)->run();
                    Notification::make()
                        ->title('Örnek Diaspora Reels Yüklendi')
                        ->body('Almanya, Kırgızistan, Hollanda ve İngiltere için 6 adet örnek diaspora içeriği başarıyla kaydedildi.')
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
        $draftCount = DiasporaReel::query()->where('status', DiasporaReel::STATUS_DRAFT)->count();

        return [
            'hepsi' => Tab::make('Tüm Paylaşımlar')
                ->badge(DiasporaReel::query()->count() ?: null),

            'onay_bekleyen' => Tab::make('📥 Onay Bekleyenler (Taslak)')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', DiasporaReel::STATUS_DRAFT))
                ->badge($draftCount ?: null)
                ->badgeColor('warning'),

            'yayinda' => Tab::make('Yayında (Aktif)')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_active', true)->where('status', DiasporaReel::STATUS_PUBLISHED))
                ->badge(DiasporaReel::query()->where('is_active', true)->where('status', DiasporaReel::STATUS_PUBLISHED)->count() ?: null)
                ->badgeColor('success'),

            'one_cikan' => Tab::make('Öne Çıkanlar')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_featured', true))
                ->badge(DiasporaReel::query()->where('is_featured', true)->count() ?: null)
                ->badgeColor('warning'),

            'de' => Tab::make('🇩🇪 Almanya')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('country_code', 'DE'))
                ->badge(DiasporaReel::query()->where('country_code', 'DE')->count() ?: null),

            'kg' => Tab::make('🇰🇬 Kırgızistan')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('country_code', 'KG'))
                ->badge(DiasporaReel::query()->where('country_code', 'KG')->count() ?: null),

            'diger' => Tab::make('🌍 Diğer')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotIn('country_code', ['DE', 'KG']))
                ->badge(DiasporaReel::query()->whereNotIn('country_code', ['DE', 'KG'])->count() ?: null),
        ];
    }
}
