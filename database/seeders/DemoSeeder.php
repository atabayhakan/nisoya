<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Yerel/demo amaçlı örnek ilanlar. Üretimde ÇALIŞTIRMAYIN.
 * Çalıştırmak için: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@nisoya.test')->first();

        $demoUsers = collect([
            ['Elif Kaya', 'elif@nisoya.test', 'DE', 'Berlin', 'EUR'],
            ['Mehmet Demir', 'mehmet@nisoya.test', 'NL', 'Amsterdam', 'EUR'],
            ['Zeynep Şahin', 'zeynep@nisoya.test', 'GB', 'Londra', 'GBP'],
            ['Can Yıldız', 'can@nisoya.test', 'CH', 'Zürih', 'CHF'],
        ])->map(fn ($u) => User::updateOrCreate(
            ['email' => $u[1]],
            ['name' => $u[0], 'username' => Str::slug($u[0]), 'password' => bcrypt('nisoya1234'),
                'email_verified_at' => now(), 'country_code' => $u[2], 'city' => $u[3],
                'preferred_currency' => $u[4], 'is_verified' => true],
        ));

        $pool = $demoUsers->when($admin, fn ($c) => $c->push($admin));

        $samples = [
            ['Online İngilizce konuşma dersi', 'yabanci-dil-dersi', 'DE', 'Berlin', 25, 'EUR', 'saatlik', true],
            ['Almanca A1-B2 özel ders', 'yabanci-dil-dersi', 'DE', 'Münih', 30, 'EUR', 'saatlik', true],
            ['Ev taşıma ve nakliyat yardımı', 'nakliyat-tasinma', 'NL', 'Amsterdam', 120, 'EUR', 'is_basina', false],
            ['Ev yapımı baklava ve börek', 'ev-yemegi', 'DE', 'Köln', null, 'EUR', 'gorusulur', false],
            ['Düğün & nişan fotoğrafçılığı', 'fotograf-video', 'GB', 'Londra', 450, 'GBP', 'paket', false],
            ['Profesyonel web sitesi tasarımı', 'web-sitesi', 'CH', 'Zürih', 800, 'CHF', 'paket', true],
            ['Resmi evrak & başvuru tercümesi', 'tercume', 'DE', 'Frankfurt', 40, 'EUR', 'is_basina', true],
            ['Güvenilir bebek bakıcılığı', 'bebek-bakiciligi', 'NL', 'Rotterdam', 15, 'EUR', 'saatlik', false],
            ['Kadın kuaförü — evde hizmet', 'kuafor-berber', 'GB', 'Manchester', 35, 'GBP', 'is_basina', false],
            ['Havalimanı transfer hizmeti', 'havalimani-transferi', 'DE', 'Berlin', 50, 'EUR', 'is_basina', false],
            ['Tadilat, boya ve montaj', 'tadilat-boya', 'CH', 'Cenevre', 60, 'CHF', 'saatlik', false],
            ['Sosyal medya yönetimi', 'sosyal-medya-yonetimi', 'GB', 'Londra', 300, 'GBP', 'paket', true],
        ];

        foreach ($samples as $i => $s) {
            $category = Category::query()->where('slug', $s[1])->first();
            $user = $pool->get($i % $pool->count());

            Listing::query()->updateOrCreate(
                ['slug' => Str::slug($s[0]), 'user_id' => $user->id],
                [
                    'type' => 'hizmet',
                    'title' => $s[0],
                    'description' => $s[0].' — '.fake('tr_TR')->paragraph(4).' Deneyimliyim, referans verebilirim. Detaylar için mesaj atın.',
                    'category_id' => $category?->id,
                    'price' => $s[4],
                    'currency' => $s[5],
                    'price_unit' => $s[6],
                    'country_code' => $s[2],
                    'city' => $s[3],
                    'is_remote' => $s[7],
                    'status' => 'aktif',
                    'views_count' => fake()->numberBetween(0, 250),
                ],
            );
        }

        $products = [
            ['Ev yapımı fıstıklı baklava (1 kg)', 'baklava-tatli', 'DE', 'Berlin', 18, 'EUR', 'adet', 25],
            ['El örgüsü bebek battaniyesi', 'bebek-orgusu', 'NL', 'Amsterdam', 35, 'EUR', 'adet', 8],
            ['Doğal zeytinyağı sabunu (3lü paket)', 'dogal-sabun', 'GB', 'Londra', 12, 'GBP', 'paket', 50],
            ['El yapımı gümüş kolye', 'el-yapimi-taki', 'CH', 'Zürih', 60, 'CHF', 'adet', 5],
            ['Ev yapımı karışık reçel', 'recel-tursu', 'DE', 'Köln', 8, 'EUR', 'adet', 30],
        ];

        foreach ($products as $i => $s) {
            $category = Category::query()->where('slug', $s[1])->first();
            $user = $pool->get($i % $pool->count());

            Listing::query()->updateOrCreate(
                ['slug' => Str::slug($s[0]), 'user_id' => $user->id],
                [
                    'type' => 'urun',
                    'title' => $s[0],
                    'description' => $s[0].' — '.fake('tr_TR')->paragraph(3).' El emeği, taze ve özenle hazırlanır. Kargo/teslimat için mesaj atın.',
                    'category_id' => $category?->id,
                    'price' => $s[4],
                    'currency' => $s[5],
                    'price_unit' => $s[6],
                    'country_code' => $s[2],
                    'city' => $s[3],
                    'is_remote' => false,
                    'stock' => $s[7],
                    'status' => 'aktif',
                    'views_count' => fake()->numberBetween(0, 200),
                ],
            );
        }

        $this->command->info('Demo: '.(count($samples) + count($products)).' ilan + '.$demoUsers->count().' üye hazır.');
    }
}
