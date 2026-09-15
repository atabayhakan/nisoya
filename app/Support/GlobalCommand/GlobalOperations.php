<?php

namespace App\Support\GlobalCommand;

use App\Models\Category;
use App\Models\Country;
use App\Models\JobListing;
use App\Models\Listing;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GlobalOperations
{
    public function countries(array $values): array
    {
        Validator::make(['countries' => $values], ['countries' => 'required|array|min:1|max:10', 'countries.*' => 'required|string|max:100'])->validate();
        $result = [];
        foreach ($values as $value) {
            $country = Country::where('is_active', true)->where(fn ($query) => $query->where('code', strtoupper($value))->orWhere('name_tr', $value))->first();
            if (! $country) {
                throw ValidationException::withMessages(['countries' => 'Ülke katalogda bulunamadı: '.$value]);
            }
            $result[] = $country->code;
        }

        return array_values(array_unique($result));
    }

    public function compareRentals(array $codes, ?string $month = null): array
    {
        $codes = $this->countries($codes);
        $month ??= now(config('app.timezone', 'UTC'))->format('Y-m');
        Validator::make(['month' => $month], ['month' => ['required', 'date_format:Y-m']])->validate();
        $start = CarbonImmutable::createFromFormat('!Y-m', $month, config('app.timezone', 'UTC'));
        $end = $start->addMonth();
        $category = Category::where('slug', 'kiralik-konut')->firstOrFail();
        $query = Listing::query()->gercek()->where('type', 'emlak')->where('category_id', $category->id)
            ->whereIn('country_code', $codes)->where('created_at', '>=', $start->utc())->where('created_at', '<', $end->utc());
        $groups = (clone $query)->select('country_code', 'currency', 'price_unit')
            ->selectRaw('COUNT(*) AS listings, COUNT(price) AS priced, AVG(price) AS mean_price')
            ->groupBy('country_code', 'currency', 'price_unit')->get()->groupBy('country_code');
        $rows = [];
        foreach ($codes as $code) {
            $rows[] = ['country_code' => $code, 'listings' => (int) ($groups[$code] ?? collect())->sum('listings'),
                'price_groups' => ($groups[$code] ?? collect())->map(fn ($row) => [
                    'currency' => $row->currency, 'unit' => $row->price_unit, 'sample_size' => (int) $row->priced,
                    'mean_price' => $row->priced >= 5 ? round((float) $row->mean_price, 2) : null,
                    'note' => $row->priced < 5 ? 'Fiyat yorumu için yetersiz örneklem' : 'Yalnız platformdaki bu ay oluşturulan ilanlar',
                ])->all()];
        }

        return ['metric' => 'Bu ay oluşturulan gerçek kiralık konut ilanları', 'from_utc' => $start->utc()->toIso8601String(),
            'to_utc_exclusive' => $end->utc()->toIso8601String(), 'rows' => $rows, 'fx_conversion' => false];
    }

    public function inspectPendingJobs(array $codes, int $actorId): array
    {
        $codes = $this->countries($codes);
        $jobs = JobListing::query()->whereIn('country_code', $codes)->where('status', 'beklemede')->orderBy('id')->limit(101)->get();
        $ids = [];
        foreach ($jobs->take(100) as $job) {
            $ids[] = app(ContentQuality::class)->request('job', $job, $actorId)->id;
        }

        return ['country_codes' => $codes, 'assessment_ids' => $ids, 'has_more' => $jobs->count() > 100,
            'result' => 'İç incelemeler kuyruğa alındı; sonuçlar operasyon merkezinde. İlan durumları değiştirilmedi.'];
    }
}
