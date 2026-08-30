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
            JobCategorySeeder::class,
            AdminUserSeeder::class,
            SiteSettingSeeder::class,
            StaticPagesSeeder::class,
            SssSorulariSeeder::class,

            // DİKKAT: bu liste ReferenceDataSeeder'ınkiyle AYNI DEĞİL (o
            // deploy'da koşar ve Zone/NavigationLink/HomeHighlight/Property-
            // Vehicle kategorilerini de içerir). Yani `migrate:fresh --seed`
            // üretimden FARKLI bir veritabanı üretir — mevcut bir tuzak.
            // Temsilcilikler en azından ikisinde de var: onlarsız yerel
            // rehber içeriği aktarımı sessizce yarısını atlıyordu.
            RehberTemsilcilikleriSeeder::class,
            FootballSeeder::class,
        ]);
    }
}
