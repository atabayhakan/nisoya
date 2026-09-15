<?php

namespace App\Jobs\GlobalCommand;

use App\Enums\UserStatus;
use App\Models\DiasporaAccount;
use App\Models\User;
use App\Support\GlobalCommand\StrictDiasporaSync;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;

class SyncDiasporaAccount implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 45;

    public int $tries = 5;

    public int $uniqueFor = 900;

    public function __construct(public int $accountId, public int $actorId, public ?string $countryCode, public ?string $city) {}

    public function uniqueId(): string
    {
        return 'gcc:account:'.$this->accountId;
    }

    public function uniqueVia(): Repository
    {
        return Cache::store(config('global-command.diaspora_sync_cache_store', 'database'));
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->uniqueId()))->releaseAfter(60)->expireAfter(90)];
    }

    public function backoff(): array
    {
        return [60, 120, 240, 480];
    }

    public function handle(StrictDiasporaSync $sync): void
    {
        if (! config('global-command.enabled') || ! config('global-command.diaspora_sync_enabled')) {
            return;
        }
        $actor = User::query()->find($this->actorId);
        $account = DiasporaAccount::query()->find($this->accountId);
        if (! $actor?->isAdmin() || $actor->status !== UserStatus::Aktif || ! $account?->is_active
            || ! $account->is_verified || $account->country_code !== $this->countryCode || $account->city !== $this->city) {
            return;
        }
        $cache = $this->uniqueVia();
        $limiter = new RateLimiter($cache);
        $permit = $cache->lock('gcc:provider:rate-lock', 5)->get(function () use ($limiter): bool {
            if ($limiter->tooManyAttempts('gcc:provider:minute', (int) config('global-command.diaspora_sync_requests_per_minute', 10))) {
                return false;
            }
            $limiter->hit('gcc:provider:minute', 60);

            return true;
        });
        if (! $permit) {
            $this->release(60);

            return;
        }
        $created = $sync->run($account);
        activity('global-command')->causedBy($actor)->performedOn($account)
            ->withProperties(['created' => $created, 'country' => $this->countryCode, 'city' => $this->city])
            ->log('diaspora.sync.completed');
    }
}
