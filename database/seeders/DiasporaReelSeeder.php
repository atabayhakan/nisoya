<?php

namespace Database\Seeders;

use App\Models\DiasporaReel;
use Illuminate\Database\Seeder;

class DiasporaReelSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title' => 'Berlin Kreuzberg Türk Kültür Festivali',
                'caption' => 'Kreuzberg sokaklarında geleneksel lezzetler, el sanatları ve canlı müzik dolu unutulmaz bir hafta sonu.',
                'instagram_url' => 'https://www.instagram.com/reel/C8xABC12345/',
                'shortcode' => 'C8xABC12345',
                'instagram_username' => '@berlinturkleri',
                'country_code' => 'DE',
                'city' => 'Berlin',
                'thumbnail_url' => null,
                'video_url' => null,
                'is_featured' => true,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Bişkek Türk Girişimciler & Esnaf Buluşması',
                'caption' => "Kırgızistan'da faaliyet gösteren Türk işletmeciler ve profesyonellerin aylık dayanışma ve iş birliği buluşması.",
                'instagram_url' => 'https://www.instagram.com/reel/C9yDEF67890/',
                'shortcode' => 'C9yDEF67890',
                'instagram_username' => '@biskey.turkleri',
                'country_code' => 'KG',
                'city' => 'Bişkek',
                'thumbnail_url' => null,
                'video_url' => null,
                'is_featured' => false,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Köln Katedrali Meydanında Dayanışma Kermesi',
                'caption' => 'Gurbette birbirimize el uzatmanın en güzel örneği: Köln ve çevresindeki ailelerle ortak dayanışma kermesi.',
                'instagram_url' => 'https://www.instagram.com/reel/C7zGHI11223/',
                'shortcode' => 'C7zGHI11223',
                'instagram_username' => '@almanyadakiturkler',
                'country_code' => 'DE',
                'city' => 'Köln',
                'thumbnail_url' => null,
                'video_url' => null,
                'is_featured' => false,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Amsterdam Türk Gençlik & Kültür Buluşması',
                'caption' => 'Kanal boyunda demli çay eşliğinde samimi sohbetler: Hollanda’da yaşayan genç profesyoneller ve öğrenciler bir arada.',
                'instagram_url' => 'https://www.instagram.com/reel/C5wJKL33445/',
                'shortcode' => 'C5wJKL33445',
                'instagram_username' => '@hollandaturkleri',
                'country_code' => 'NL',
                'city' => 'Amsterdam',
                'thumbnail_url' => null,
                'video_url' => null,
                'is_featured' => false,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Londra Türk Lezzetleri & Esnaf Sohbetleri',
                'caption' => 'North London sokaklarında fırından yeni çıkan sıcak simit kokusu ve gurbet esnafının dayanışma hikayeleri.',
                'instagram_url' => 'https://www.instagram.com/reel/C4vMNO55667/',
                'shortcode' => 'C4vMNO55667',
                'instagram_username' => '@londraturkleri',
                'country_code' => 'GB',
                'city' => 'Londra',
                'thumbnail_url' => null,
                'video_url' => null,
                'is_featured' => false,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'title' => 'Frankfurt Kitap Fuarı Türkçe Eserler Standı',
                'caption' => 'Kendi dilimizde okumanın ve kültürel bağlarımızı yaşatmanın gururu: Frankfurt’ta Türkçe edebiyat coşkusu.',
                'instagram_url' => 'https://www.instagram.com/reel/C3uPQR77889/',
                'shortcode' => 'C3uPQR77889',
                'instagram_username' => '@frankfurt_etkinlik',
                'country_code' => 'DE',
                'city' => 'Frankfurt',
                'thumbnail_url' => null,
                'video_url' => null,
                'is_featured' => false,
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            DiasporaReel::query()->firstOrCreate(
                ['title' => $item['title']],
                $item,
            );
        }
    }
}
