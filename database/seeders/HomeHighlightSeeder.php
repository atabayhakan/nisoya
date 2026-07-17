<?php

namespace Database\Seeders;

use App\Models\HomeHighlight;
use Illuminate\Database\Seeder;

/**
 * Ana sayfa büyük/küçük kartlarının İLK mesajını, o kartların daha önce
 * setting() ile gösterdiği (admin panelden zaten özelleştirilmiş olabilecek)
 * metinlerden oluşturur — bkz. docs/plans/2026-07-17-anasayfa-vurgu-slider-design.md.
 * firstOrCreate + sort_order=0 anahtarı: bu ilk kayıt bir daha asla tekrar
 * oluşturulmaz/güncellenmez, admin sonradan ekleyeceği diğer mesajlarla
 * birlikte serbestçe düzenler/siler (Zone/NavigationLinkSeeder ile aynı
 * "canlıda düzenlenen içeriğin üzerine yazma" deseni).
 */
class HomeHighlightSeeder extends Seeder
{
    public function run(): void
    {
        HomeHighlight::query()->firstOrCreate(
            ['slot' => HomeHighlight::SLOT_BIG, 'sort_order' => 0],
            [
                'title' => setting('home.deger1_baslik', 'Tamamen Türkçe'),
                'text' => setting('home.deger1_metin', 'Kendi dilinde, kendi insanınla.'),
                'icon' => 'language',
                'is_active' => true,
            ],
        );

        HomeHighlight::query()->firstOrCreate(
            ['slot' => HomeHighlight::SLOT_SMALL, 'sort_order' => 0],
            [
                'title' => setting('home.deger3_baslik', 'Ücretsiz ilan'),
                'text' => setting('home.deger3_metin', 'İlan vermek tamamen ücretsiz.'),
                'icon' => 'sparkles',
                'is_active' => true,
            ],
        );
    }
}
