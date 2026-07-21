<?php

namespace App\Http\Requests;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\SalaryPeriod;
use App\Services\ProfanityFilterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class JobListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Kurumsal (şirket profili olan) hesaplar iş ilanı verebilir.
        return (bool) $this->user()?->company;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:8000'],
            'job_category_id' => ['nullable', 'exists:job_categories,id'],
            'employment_type' => ['required', new Enum(EmploymentType::class)],
            'experience_level' => ['nullable', new Enum(ExperienceLevel::class)],
            'salary_min' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'max:99999999', 'gte:salary_min'],
            'salary_currency' => ['nullable', 'required_with:salary_min,salary_max', 'exists:currencies,code'],
            'salary_period' => ['nullable', new Enum(SalaryPeriod::class)],
            'country_code' => ['nullable', 'exists:countries,code'],
            'city' => ['nullable', 'string', 'max:100'],
            'is_remote' => ['nullable', 'boolean'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'positions' => ['nullable', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'ilan başlığı',
            'description' => 'açıklama',
            'job_category_id' => 'kategori',
            'employment_type' => 'çalışma tipi',
            'experience_level' => 'deneyim seviyesi',
            'salary_min' => 'en düşük maaş',
            'salary_max' => 'en yüksek maaş',
            'salary_currency' => 'para birimi',
            'salary_period' => 'maaş periyodu',
            'country_code' => 'ülke',
            'city' => 'şehir',
            'deadline' => 'son başvuru tarihi',
            'positions' => 'pozisyon sayısı',
        ];
    }

    public function withValidator($validator): void
    {
        // İş ilanı başlığı/açıklaması da pazaryeri ilanları gibi küfür
        // filtresinden geçmeli (denetim #8 — kapsam boşluğu).
        $validator->after(function ($validator): void {
            $filter = app(ProfanityFilterService::class);

            foreach (['title', 'description'] as $field) {
                if ($error = $filter->validateText($this->input($field))) {
                    $validator->errors()->add($field, $error);
                }
            }
        });
    }
}
