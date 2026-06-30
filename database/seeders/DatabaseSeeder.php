<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
            CategorySeeder::class,
            ProductCategorySeeder::class,
            AdminUserSeeder::class,
            SiteSettingSeeder::class,
        ]);
    }
}
