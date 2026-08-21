<?php

namespace Database\Seeders;

use App\Models\YasamKategorisi;
use Illuminate\Database\Seeder;

/**
 * Yaşam Rehberi'nin 8 sabit kategorisi (tasarım brainstorming'inde sahiple
 * birlikte belirlendi — bkz. docs/plans/2026-08-21-yasam-rehberi-tasarimi.md
 * §0.2). Referans/taksonomi verisi: ReferenceDataSeeder'a kayıtlı, her
 * deploy'da güvenle tekrar çalışır (firstOrCreate — panelden yapılan
 * düzenlemeleri ezmez).
 */
class YasamKategorileriSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriler = [
            ['ad' => 'Bankacılık & Finans', 'slug' => 'bankacilik-finans', 'ikon' => '🏦', 'sort_order' => 1],
            ['ad' => 'Barınma', 'slug' => 'barinma', 'ikon' => '🏠', 'sort_order' => 2],
            ['ad' => 'Sağlık & Sigorta', 'slug' => 'saglik-sigorta', 'ikon' => '🏥', 'sort_order' => 3],
            ['ad' => 'İş & Kariyer', 'slug' => 'is-kariyer', 'ikon' => '💼', 'sort_order' => 4],
            ['ad' => 'Eğitim', 'slug' => 'egitim', 'ikon' => '🎓', 'sort_order' => 5],
            ['ad' => 'Ulaşım', 'slug' => 'ulasim', 'ikon' => '🚗', 'sort_order' => 6],
            ['ad' => 'Gündelik Bürokrasi', 'slug' => 'gundelik-burokrasi', 'ikon' => '📋', 'sort_order' => 7],
            ['ad' => 'Kültür & Uyum', 'slug' => 'kultur-uyum', 'ikon' => '🌍', 'sort_order' => 8],
        ];

        foreach ($kategoriler as $kategori) {
            YasamKategorisi::query()->firstOrCreate(
                ['slug' => $kategori['slug']],
                $kategori,
            );
        }
    }
}
