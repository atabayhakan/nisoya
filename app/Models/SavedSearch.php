<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'q',
        'kategori',
        'ulke',
        'sehir',
        'tip',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Bu aramaya uyan aktif ilanların sorgusu. */
    public function matchingQuery(): Builder
    {
        /*
         * gercek(): örnek ilan "yeni ilan" diye E-POSTALANMAZ.
         *
         * Bu uç sızıntının en pahalısıydı: uyarı kullanıcıya kendisi
         * gidiyordu. "Berlin'de 3 yeni ilan" e-postası açılıyor, içinde
         * `[ÖRNEK] Ev Temizliği` satırları duruyor, düğmesi ise ilanların
         * artık listelenmediği bir /ilanlar sayfasına çıkıyordu — yani
         * ilan sayfalarının düzeltilmesi bu ucu iyileştirmek yerine
         * BOZUYORDU (vaat edilen ilan, gidilen yerde yok).
         */
        $query = Listing::query()->active()->gercek();

        if ($this->q) {
            $query->where(function ($s) {
                $s->where('title', 'like', "%{$this->q}%")->orWhere('description', 'like', "%{$this->q}%");
            });
        }

        if ($this->kategori) {
            $category = Category::query()->where('slug', $this->kategori)->first();
            if ($category) {
                $ids = collect([$category->id])->merge($category->children()->pluck('id'))->all();
                $query->whereIn('category_id', $ids);
            }
        }

        if ($this->ulke) {
            $query->where('country_code', $this->ulke);
        }

        if ($this->sehir) {
            $query->where('city', 'like', "%{$this->sehir}%");
        }

        if (in_array($this->tip, ['hizmet', 'urun'], true)) {
            $query->where('type', $this->tip);
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
            'sehir' => $this->sehir,
            'tip' => $this->tip,
        ]);
    }
}
