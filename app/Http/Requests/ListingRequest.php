<?php

namespace App\Http\Requests;

use App\Enums\ListingType;
use App\Enums\PriceUnit;
use App\Models\ListingPropertyDetail;
use App\Models\ListingVehicleDetail;
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
            // Faz M5: ürün boyutları (cm) — boyut karşılaştırma görseli için
            'width_cm' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'height_cm' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'website' => ['prohibited'], // honeypot

            // Emlak detayları (yalnızca tip=emlak formunda bulunur; diğer
            // tiplerde alanlar gönderilmediği için nullable kurallar yeterli)
            'rooms' => ['nullable', Rule::in(ListingPropertyDetail::ROOM_OPTIONS)],
            'area_m2' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'floor' => ['nullable', 'integer', 'min:-5', 'max:90'],
            'furnished' => ['nullable', 'boolean'],
            'deposit' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'available_from' => ['nullable', 'date'],
            'max_guests' => ['nullable', 'integer', 'min:1', 'max:50'],
            'min_stay_nights' => ['nullable', 'integer', 'min:1', 'max:365'],

            // Vasıta detayları (yalnızca tip=vasita formunda bulunur)
            'brand' => ['nullable', 'string', 'max:60'],
            'model' => ['nullable', 'string', 'max:60'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'mileage_km' => ['nullable', 'integer', 'min:0', 'max:2000000'],
            'fuel' => ['nullable', Rule::in(array_keys(ListingVehicleDetail::FUELS))],
            'transmission' => ['nullable', Rule::in(array_keys(ListingVehicleDetail::TRANSMISSIONS))],
            'body_type' => ['nullable', Rule::in(array_keys(ListingVehicleDetail::BODY_TYPES))],
            'color' => ['nullable', 'string', 'max:30'],
            'min_rental_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'km_limit_per_day' => ['nullable', 'integer', 'min:0', 'max:10000'],

            // Rozetler: geçerli küme ilan tipine göre değişir
            'badges' => ['nullable', 'array'],
            'badges.*' => [Rule::in(array_keys(
                $this->input('type') === 'vasita' ? ListingVehicleDetail::BADGES : ListingPropertyDetail::BADGES
            ))],
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
            'rooms' => 'oda sayısı',
            'area_m2' => 'metrekare',
            'floor' => 'bulunduğu kat',
            'deposit' => 'depozito',
            'available_from' => 'müsait tarih',
            'max_guests' => 'konuk kapasitesi',
            'min_stay_nights' => 'minimum konaklama',
            'badges' => 'özellik rozetleri',
            'brand' => 'marka',
            'model' => 'model',
            'year' => 'model yılı',
            'mileage_km' => 'kilometre',
            'fuel' => 'yakıt',
            'transmission' => 'vites',
            'body_type' => 'kasa tipi',
            'color' => 'renk',
            'min_rental_days' => 'minimum kiralama günü',
            'km_limit_per_day' => 'günlük km sınırı',
        ];
    }
}
