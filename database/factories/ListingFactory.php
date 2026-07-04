<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::query()->whereNotNull('parent_id')->inRandomOrder()->value('id'),
            'type' => 'hizmet',
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 500),
            'currency' => 'EUR',
            'price_unit' => 'saatlik',
            'country_code' => 'DE',
            'city' => fake()->city(),
            'is_remote' => fake()->boolean(),
            'status' => 'aktif',
            'views_count' => 0,
        ];
    }
}
