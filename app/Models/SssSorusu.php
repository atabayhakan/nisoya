<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SssSorusu extends Model
{
    protected $table = 'sss_sorulari';

    protected $fillable = ['soru', 'cevap', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @param Builder<SssSorusu> $query */
    public function scopeAktif(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
