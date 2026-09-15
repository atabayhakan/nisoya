<?php

namespace App\Models;

use App\Models\Concerns\NormalizesCityName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $kind
 * @property int $source_id
 * @property string $source_hash
 * @property string $version
 * @property ?int $actor_id
 * @property bool $requested_by_system
 * @property ?string $country_code
 * @property ?string $city
 * @property string $title
 * @property string $status
 * @property ?int $quality_score
 * @property ?int $risk_score
 * @property ?array $findings
 * @property ?array $ai_result
 * @property ?string $provider
 * @property ?string $model
 * @property Carbon|null $assessed_at
 * @property int $attempt
 */
class ContentAssessment extends Model
{
    use NormalizesCityName;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['findings' => 'array', 'ai_result' => 'array', 'assessed_at' => 'datetime', 'attempt' => 'integer', 'requested_by_system' => 'boolean'];
    }
}
