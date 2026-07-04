<?php

namespace Database\Seeders;

use App\Models\JobCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Yazılım & Teknoloji', 'code-bracket'],
            ['Gastronomi & Mutfak', 'cake'],
            ['İnşaat & Tadilat', 'wrench'],
            ['Sağlık & Bakım', 'heart'],
            ['Eğitim & Öğretmenlik', 'academic-cap'],
            ['Lojistik & Nakliye', 'truck'],
            ['Perakende & Satış', 'shopping-bag'],
            ['Temizlik & Ev Hizmetleri', 'sparkles'],
            ['Güzellik & Kuaförlük', 'scissors'],
            ['Ofis & Yönetim', 'briefcase'],
            ['Pazarlama & Medya', 'megaphone'],
            ['Muhasebe & Finans', 'calculator'],
            ['Çeviri & Danışmanlık', 'language'],
            ['Otomotiv & Tamir', 'cog-6-tooth'],
            ['Diğer', 'ellipsis-horizontal'],
        ];

        foreach ($categories as $i => [$name, $icon]) {
            JobCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon, 'sort_order' => $i, 'is_active' => true],
            );
        }
    }
}
