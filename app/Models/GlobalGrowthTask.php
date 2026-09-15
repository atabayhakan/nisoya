<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalGrowthTask extends Model
{
    protected $table = 'global_growth_tasks';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime', 'period_start' => 'date', 'revision' => 'integer'];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }
}
