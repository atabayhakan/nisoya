<?php

namespace App\Support\GlobalCommand;

use App\Enums\UserStatus;
use App\Jobs\GlobalCommand\AssessContent;
use App\Models\ContentAssessment;
use App\Models\DiasporaReel;
use App\Models\JobListing;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContentQuality
{
    public const VERSION = 'quality-v1';

    public function source(string $kind, int $id): ?Model
    {
        $class = match ($kind) {
            'listing' => Listing::class, 'job' => JobListing::class, 'reel' => DiasporaReel::class,
            default => throw new \InvalidArgumentException('Geçersiz içerik türü.'),
        };

        return $class::find($id);
    }

    public function payload(Model $model): array
    {
        return match (true) {
            $model instanceof Listing => [
                'title' => (string) $model->title, 'text' => (string) ($model->description ?? ''),
                'country_code' => $model->country_code, 'city' => $model->city,
                'price' => $model->price, 'currency' => $model->currency, 'price_unit' => $model->price_unit,
                'images' => $model->images_count ?? $model->images()->count(), 'views' => null, 'likes' => null,
            ],
            $model instanceof JobListing => [
                'title' => (string) $model->title, 'text' => (string) ($model->description ?? ''),
                'country_code' => $model->country_code, 'city' => $model->city,
                'price' => $model->salary_min, 'currency' => $model->salary_currency, 'price_unit' => $model->salary_period,
                'images' => null, 'views' => null, 'likes' => null,
            ],
            $model instanceof DiasporaReel => [
                'title' => (string) $model->title, 'text' => (string) ($model->caption ?? ''),
                'country_code' => $model->country_code, 'city' => $model->city,
                'price' => null, 'currency' => null, 'price_unit' => null,
                'images' => null, 'views' => $model->views_count, 'likes' => $model->likes_count,
            ],
            default => throw new \InvalidArgumentException('Desteklenmeyen içerik modeli: '.$model::class),
        };
    }

    public function hash(Model $model): string
    {
        return hash('sha256', json_encode($this->payload($model), JSON_THROW_ON_ERROR));
    }

    public function request(string $kind, Model $model, ?int $actorId, bool $retry = false): ContentAssessment
    {
        if ($actorId !== null) {
            $actor = User::find($actorId);
            abort_unless($actor?->isAdmin() && $actor->status === UserStatus::Aktif, 403);
        }

        return DB::transaction(function () use ($kind, $model, $actorId, $retry): ContentAssessment {
            $payload = $this->payload($model);
            $assessment = ContentAssessment::firstOrCreate([
                'kind' => $kind, 'source_id' => $model->getKey(), 'source_hash' => $this->hash($model), 'version' => self::VERSION,
            ], ['actor_id' => $actorId, 'requested_by_system' => $actorId === null, 'country_code' => $payload['country_code'], 'city' => $payload['city'], 'title' => $payload['title'], 'status' => 'pending']);
            $dispatch = $assessment->wasRecentlyCreated;
            $assessment = ContentAssessment::whereKey($assessment->id)->lockForUpdate()->firstOrFail();
            if ($retry && in_array($assessment->status, ['failed', 'cancelled', 'stale'], true)) {
                $assessment->update(['status' => 'pending', 'attempt' => $assessment->attempt + 1,
                    'actor_id' => $actorId, 'requested_by_system' => $actorId === null,
                    'quality_score' => null, 'risk_score' => null, 'findings' => null, 'ai_result' => null,
                    'provider' => null, 'model' => null, 'assessed_at' => null]);
                $dispatch = true;
                activity('global-command')->causedBy($actorId)->performedOn($assessment)
                    ->withProperties(['attempt' => $assessment->attempt])->log('content.assessment.retried');
            }
            if ($dispatch) {
                AssessContent::dispatch($assessment->id, $assessment->attempt)->onConnection(config('global-command.diaspora_sync_queue_connection', 'database'))
                    ->onQueue('global-command')->afterCommit();
            }

            return $assessment;
        });
    }

    public function rules(array $payload): array
    {
        $findings = [];
        $score = 100;
        foreach (['title' => ['Başlık eksik', 20], 'text' => ['Açıklama kısa', 20], 'city' => ['Şehir eksik', 10], 'country_code' => ['Ülke eksik', 10]] as $key => [$label, $penalty]) {
            if (blank($payload[$key]) || ($key === 'text' && mb_strlen(strip_tags($payload[$key])) < 40)) {
                $score -= $penalty;
                $findings[] = $label;
            }
        }
        if ($payload['images'] === 0) {
            $score -= 25;
            $findings[] = 'Fotoğraf eklenmemiş';
        }
        if ($payload['price'] !== null && (float) $payload['price'] < 0) {
            $score -= 30;
            $findings[] = 'Negatif fiyat';
        }

        return ['score' => max(0, $score), 'findings' => $findings];
    }
}
