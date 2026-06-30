<?php

namespace App\Http\Requests;

use App\Enums\ListingType;
use App\Enums\PriceUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // yetki kontrolü kontrolcüde (policy)
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['nullable', new Enum(ListingType::class)],
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'currency' => ['required', Rule::exists('currencies', 'code')],
            'price_unit' => ['required', new Enum(PriceUnit::class)],
            'country_code' => ['required', Rule::exists('countries', 'code')],
            'city' => ['nullable', 'string', 'max:255'],
            'is_remote' => ['nullable', 'boolean'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'website' => ['prohibited'], // honeypot
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'title' => 'başlık',
            'category_id' => 'kategori',
            'description' => 'açıklama',
            'price' => 'fiyat',
            'currency' => 'para birimi',
            'price_unit' => 'fiyat birimi',
            'country_code' => 'ülke',
            'city' => 'şehir',
            'images' => 'görseller',
            'images.*' => 'görsel',
        ];
    }
}
