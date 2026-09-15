<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserStatus;
use App\Filament\Resources\DiasporaAccounts\DiasporaAccountResource;
use App\Filament\Resources\DiasporaReels\DiasporaReelResource;
use App\Jobs\GlobalCommand\SyncDiasporaAccount;
use App\Models\DiasporaAccount;
use App\Models\DiasporaReel;
use App\Support\GlobalCommand\GeoContext;
use App\Support\GlobalCommand\IntelligenceScore;
use App\Support\GlobalCommand\RadarMediaUrl;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

class DiasporaMediaRadar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';

    protected static ?string $navigationGroup = 'İçerik & Tasarım (CMS)';

    protected static ?string $navigationLabel = 'Diaspora Medya Radarı';

    protected static ?string $title = 'Diaspora Medya Radarı';

    protected static ?string $slug = 'diaspora-medya-radari';

    protected static string $view = 'filament.pages.diaspora-media-radar';

    #[Locked]
    public int $radarPage = 1;

    #[Locked]
    public string $filter = 'all';

    #[Locked]
    public ?int $previewId = null;

    #[Locked]
    public string $contextKey = '';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return (bool) config('global-command.enabled')
            && Filament::getCurrentPanel()?->getId() === 'admin'
            && $user?->isAdmin() === true
            && $user->status === UserStatus::Aktif;
    }

    // Runs on the initial page AND every Livewire update, including inherited actions.
    public function boot(): void
    {
        $this->assertAdmin();
    }

    public function mount(): void
    {
        $this->contextKey = app(GeoContext::class)->key();
    }

    public function hydrate(): void
    {
        $this->assertAdmin();

        // Covers a lens change in another browser tab sharing this session.
        if ($this->contextKey !== app(GeoContext::class)->key()) {
            $this->radarPage = 1;
            $this->previewId = null;
            $this->contextKey = app(GeoContext::class)->key();
        }
    }

    public function setFilter(string $filter): void
    {
        $this->assertAdmin();
        abort_unless(in_array($filter, ['all', 'draft', 'published', 'archived', 'needs_review'], true), 422);
        $this->filter = $filter;
        $this->radarPage = 1;
        $this->previewId = null;
    }

    public function nextRadarPage(): void
    {
        $this->assertAdmin();

        if ($this->reels()->hasMorePages()) {
            $this->radarPage++;
            $this->previewId = null;
        }
    }

    public function previousRadarPage(): void
    {
        $this->assertAdmin();
        $this->radarPage = max(1, $this->radarPage - 1);
        $this->previewId = null;
    }

    public function openPreview(int $reelId): void
    {
        $this->assertAdmin();
        $reel = $this->scopedReels()->findOrFail($reelId);
        abort_unless(RadarMediaUrl::embed($reel->instagram_url) !== null, 422);
        $this->previewId = $reel->id;
    }

    public function closePreview(): void
    {
        $this->assertAdmin();
        $this->previewId = null;
    }

    public function verifyAccount(int $accountId): void
    {
        $this->assertAdmin();
        DB::transaction(function () use ($accountId): void {
            $account = $this->scopedAccounts()->lockForUpdate()->findOrFail($accountId);
            abort_unless($account->is_active, 422);
            if (! $account->is_verified) {
                $account->update(['is_verified' => true]);
                activity('global-command')->causedBy(Filament::auth()->user())
                    ->performedOn($account)->withProperties(['geo_context' => app(GeoContext::class)->key()])
                    ->log('diaspora.account.manually_verified');
            }
        });

        Notification::make()->title('Hesap yönetici tarafından doğrulandı')->success()->send();
    }

    public function syncAccount(int $accountId): void
    {
        $this->assertAdmin();
        abort_unless(config('global-command.diaspora_sync_enabled', false), 409);
        $account = $this->scopedAccounts()->findOrFail($accountId);
        abort_unless($account->is_active && $account->is_verified, 422);

        $connection = (string) config('global-command.diaspora_sync_queue_connection', 'database');
        abort_unless(in_array(config("queue.connections.{$connection}.driver"), ['database', 'redis'], true), 503);
        $cacheStore = (string) config('global-command.diaspora_sync_cache_store', 'database');
        abort_unless(in_array(config("cache.stores.{$cacheStore}.driver"), ['database', 'redis'], true), 503);

        $limiterKey = 'global-command:sync:'.Filament::auth()->id().':'.$account->id;
        if (RateLimiter::tooManyAttempts($limiterKey, 1)) {
            Notification::make()->title('Bu hesap için yakın zamanda tarama istendi')->warning()->send();

            return;
        }

        SyncDiasporaAccount::dispatch(
            $account->id,
            (int) Filament::auth()->id(),
            $account->country_code,
            $account->city,
        )->onConnection($connection)->onQueue('diaspora-sync');

        RateLimiter::hit($limiterKey, 60);
        Notification::make()->title('Tarama talebi alındı')
            ->body('Aynı hesabın bekleyen talebi varsa birleştirilir. Yeni içerikler taslak olarak alınır.')
            ->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addAccount')->label('Hesap ekle')->icon('heroicon-o-plus')
                ->url(DiasporaAccountResource::getUrl('create', panel: 'admin')),
            Action::make('accounts')->label('Hesapları yönet')->color('gray')
                ->url(DiasporaAccountResource::getUrl('index', panel: 'admin')),
        ];
    }

    protected function getViewData(): array
    {
        $this->assertAdmin();
        $reels = $this->reels();
        $preview = $this->previewId === null ? null : $this->scopedReels()->find($this->previewId);

        return [
            'geoLabel' => app(GeoContext::class)->label(),
            'cards' => collect($reels->items())->map(fn (DiasporaReel $reel): array => [
                'id' => $reel->id,
                'title' => $reel->title,
                'caption' => Str::limit((string) $reel->caption, 220),
                'location' => $reel->displayLocation() ?: 'Konum atanmamış',
                'username' => $reel->account?->username ?? $reel->instagram_username ?? 'Hesap atanmamış',
                'accountId' => $reel->account?->id,
                'verified' => $reel->account?->is_verified ?? false,
                'activeAccount' => $reel->account?->is_active ?? false,
                'status' => match ($reel->status) {
                    'published' => $reel->is_active ? 'Yayında' : 'Yayın duraklatılmış',
                    'draft' => 'Taslak',
                    'archived' => 'Arşiv',
                    default => 'Durum bilinmiyor',
                },
                'score' => IntelligenceScore::forReel($reel),
                'engagement' => number_format($reel->engagement_score, 0, ',', '.'),
                'views' => $reel->views_count === null ? 'Ölçülmedi' : number_format($reel->views_count, 0, ',', '.'),
                'likes' => $reel->likes_count === null ? 'Ölçülmedi' : number_format($reel->likes_count, 0, ',', '.'),
                'thumbnail' => RadarMediaUrl::thumbnail($reel->thumbnail_url),
                'canPreview' => RadarMediaUrl::embed($reel->instagram_url) !== null,
                'sourceUrl' => RadarMediaUrl::instagram($reel->instagram_url),
                'editUrl' => DiasporaReelResource::getUrl('edit', ['record' => $reel], panel: 'admin'),
            ]),
            'preview' => $preview === null ? null : [
                'id' => $preview->id,
                'title' => $preview->title,
                'url' => RadarMediaUrl::embed($preview->instagram_url),
            ],
            'hasMorePages' => $reels->hasMorePages(),
            'syncEnabled' => (bool) config('global-command.diaspora_sync_enabled', false),
        ];
    }

    protected function reels(): Paginator
    {
        return $this->scopedReels()->with(['country:code,name_tr', 'account:id,username,is_verified,is_active'])
            ->orderByDesc('id')->simplePaginate(12, ['*'], 'radarPage', $this->radarPage);
    }

    protected function scopedReels(): Builder
    {
        $query = app(GeoContext::class)->apply(DiasporaReel::query());

        if ($this->filter === 'needs_review') {
            $query->where(fn (Builder $query) => $query->whereNull('safety_score')
                ->orWhere('safety_status', '!=', 'safe')->orWhere('safety_score', '<', 85));
        } elseif ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        return $query;
    }

    protected function scopedAccounts(): Builder
    {
        return app(GeoContext::class)->apply(DiasporaAccount::query());
    }

    protected function assertAdmin(): void
    {
        abort_unless(static::canAccess(), 403);
    }
}
