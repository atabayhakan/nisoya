<?php

namespace App\Models;

use App\Enums\EmploymentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobSavedSearch extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'q',
        'kategori',
        'ulke',
        'tip',
        'uzaktan',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'uzaktan' => 'boolean',
            'last_notified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Bu aramaya uyan aktif iş ilanlarının sorgusu. */
    public function matchingQuery(): Builder
    {
        $query = JobListing::query()->active();

        if ($this->q) {
            $query->where(function ($s) {
                $s->where('title', 'like', "%{$this->q}%")->orWhere('description', 'like', "%{$this->q}%");
            });
        }

        if ($this->kategori) {
            $query->whereHas('category', fn ($c) => $c->where('slug', $this->kategori));
        }

        if ($this->ulke) {
            $query->where('country_code', $this->ulke);
        }

        if ($this->tip && in_array($this->tip, array_column(EmploymentType::cases(), 'value'), true)) {
            $query->where('employment_type', $this->tip);
        }

        if ($this->uzaktan) {
            $query->where('is_remote', true);
        }

        return $query;
    }

    /** Arama kriterlerini URL parametrelerine çevirir. */
    public function toQueryParams(): array
    {
        return array_filter([
            'q' => $this->q,
            'kategori' => $this->kategori,
            'ulke' => $this->ulke,
            'tip' => $this->tip,
            'uzaktan' => $this->uzaktan ? '1' : null,
        ]);
    }
}
