<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ApplicationStatus $status
 * @property ?string $notified_status
 */
class JobApplication extends Model
{
    protected $fillable = [
        'job_listing_id',
        'user_id',
        'cover_letter',
        'cv_path',
        'status',
        'notified_status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
        ];
    }

    /** @return BelongsTo<JobListing, $this> */
    public function jobListing(): BelongsTo
    {
        return $this->belongsTo(JobListing::class);
    }

    /**
     * Başvuran aday.
     *
     * @return BelongsTo<User, $this>
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Adaya, güncel durumundan haberi olmayan bir değişiklik var mı?
     *
     * Gecikmeli bildirim işinin tek karar kaynağı budur: iş çalıştığında
     * durum tekrar eski hâline dönmüşse (işveren kartı geri sürüklediyse)
     * status === notified_status olur ve aday hiçbir şey görmez.
     */
    public function bildirimBekliyor(): bool
    {
        return $this->status->value !== $this->notified_status;
    }
}
