<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CompanyGalleryImage extends Model
{
    protected $fillable = [
        'company_id',
        'path_thumb',
        'path_medium',
        'path_large',
        'caption',
        'sort_order',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function url(string $variant = 'medium'): ?string
    {
        $map = [
            'thumb' => $this->path_thumb,
            'medium' => $this->path_medium,
            'large' => $this->path_large,
        ];

        $path = $map[$variant] ?? null;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /** @return array<int, string> */
    public function variantPaths(): array
    {
        return array_values(array_filter([$this->path_thumb, $this->path_medium, $this->path_large]));
    }
}
