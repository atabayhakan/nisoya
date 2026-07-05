<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\SalaryPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property EmploymentType $employment_type
 * @property ExperienceLevel|null $experience_level
 * @property SalaryPeriod|null $salary_period
 * @property JobStatus $status
 * @property Carbon|null $deadline
 * @property Carbon|null $featured_until
 * @property bool $is_remote
 * @property bool $is_featured
 * @property string|null $salary_min
 * @property string|null $salary_max
 * @property string|null $salary_currency
 */
class JobListing extends Model
{
    protected $fillable = [
        'company_id',
        'job_category_id',
        'title',
        'slug',
        'description',
        'employment_type',
        'experience_level',
        'salary_min',
        'salary_max',
        'salary_currency',
        'salary_period',
        'country_code',
        'city',
        'is_remote',
        'deadline',
        'positions',
        'status',
        'is_featured',
        'featured_until',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'experience_level' => ExperienceLevel::class,
            'salary_period' => SalaryPeriod::class,
            'status' => JobStatus::class,
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'is_remote' => 'boolean',
            'is_featured' => 'boolean',
            'deadline' => 'date',
            'featured_until' => 'datetime',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /** @return HasMany<JobApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /** @return HasMany<JobBookmark, $this> */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(JobBookmark::class);
    }

    /** Yalnızca yayında (aktif) ilanlar. */
    public function scopeActive($query)
    {
        return $query->where('status', JobStatus::Aktif->value);
    }

    /** Son başvuru tarihi geçmiş mi? */
    public function isExpired(): bool
    {
        return $this->deadline !== null && $this->deadline->isPast();
    }

    /** İlana başvurular hâlâ kabul ediliyor mu? */
    public function isOpen(): bool
    {
        return $this->status === JobStatus::Aktif && ! $this->isExpired();
    }

    /** Öne çıkan ve süresi dolmamış mı? */
    public function isCurrentlyFeatured(): bool
    {
        return $this->is_featured && (is_null($this->featured_until) || $this->featured_until->isFuture());
    }

    /** Maaş aralığını okunur metne çevir (örn. "€2.000 – €3.000 / ay"). */
    public function salaryLabel(): ?string
    {
        if ($this->salary_min === null && $this->salary_max === null) {
            return null;
        }

        $cur = $this->salary_currency ? $this->salary_currency.' ' : '';
        $fmt = fn ($v) => $cur.number_format((float) $v, 0, ',', '.');
        $period = $this->salary_period ? ' / '.$this->salary_period->suffix() : '';

        if ($this->salary_min !== null && $this->salary_max !== null) {
            return $fmt($this->salary_min).' – '.$fmt($this->salary_max).$period;
        }

        return $fmt($this->salary_min ?? $this->salary_max).$period;
    }
}
