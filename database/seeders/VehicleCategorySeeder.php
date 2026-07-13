<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VehicleCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Vasıta dikeyi kategori ağacı (bkz. docs/plans/2026-07-13-emlak-vasita-davetiye-tasarim.md).
        // sort_order 210: emlak kökünden (200) hemen sonra.
        $children = [
            ['Satılık Araç', '🏷️'],
            ['Kiralık Araç', '🔑'],
        ];

        $parent = Category::updateOrCreate(
            ['slug' => 'vasita'],
            ['name' => 'Vasıta', 'icon' => '🚗', 'type' => 'vasita', 'sort_order' => 210, 'is_active' => true, 'parent_id' => null],
        );

        foreach ($children as $j => [$name, $icon]) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon, 'type' => 'vasita', 'sort_order' => $j, 'is_active' => true, 'parent_id' => $parent->id],
            );
        }
    }
}
