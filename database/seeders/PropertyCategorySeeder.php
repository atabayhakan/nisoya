<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PropertyCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Emlak dikeyi kategori ağacı (bkz. docs/plans/2026-07-13-emlak-vasita-davetiye-tasarim.md).
        // sort_order 200+: hizmet (0+) ve ürün (100+) köklerinden sonra gelsin.
        // Alt kategoriler /emlak vitrininde sekme olarak gösterildiği için ikon taşır.
        $children = [
            ['Kiralık Konut', '🔑'],
            ['Satılık Konut', '🏷️'],
            ['Kısa Dönem & Tatil', '🧳'],
            ['Oda & Ev Arkadaşı', '🛏️'],
        ];

        $parent = Category::updateOrCreate(
            ['slug' => 'emlak'],
            ['name' => 'Emlak', 'icon' => '🏡', 'type' => 'emlak', 'sort_order' => 200, 'is_active' => true, 'parent_id' => null],
        );

        foreach ($children as $j => [$name, $icon]) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon, 'type' => 'emlak', 'sort_order' => $j, 'is_active' => true, 'parent_id' => $parent->id],
            );
        }
    }
}
