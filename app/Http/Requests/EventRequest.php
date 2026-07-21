<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use App\Services\ProfanityFilterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // yetki kontrolü kontrolcüde
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(EventType::class)],
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'starts_at' => ['required', 'date'],
            'venue_name' => ['nullable', 'string', 'max:120'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'theme' => ['nullable', Rule::in(array_keys(config('event_themes')))],
            'is_active' => ['nullable', 'boolean'],
            'allow_uploads' => ['nullable', 'boolean'],
            'require_approval' => ['nullable', 'boolean'],
            'album_is_public' => ['nullable', 'boolean'],
            'website' => ['prohibited'], // honeypot
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'type' => 'etkinlik türü',
            'title' => 'başlık',
            'starts_at' => 'tarih ve saat',
            'venue_name' => 'mekan adı',
            'venue_address' => 'adres',
            'description' => 'davet metni',
            'theme' => 'tema',
        ];
    }

    public function withValidator($validator): void
    {
        // Etkinlik başlığı/metni de küfür filtresinden geçmeli (denetim #8).
        $validator->after(function ($validator): void {
            $filter = app(ProfanityFilterService::class);

            foreach (['title', 'description', 'venue_name'] as $field) {
                if ($error = $filter->validateText($this->input($field))) {
                    $validator->errors()->add($field, $error);
                }
            }
        });
    }
}
