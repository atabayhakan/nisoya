<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Diaspora odaklı ev yapımı / el emeği ürün kategorileri (üst => alt).
        // sort_order, hizmet kategorilerinden sonra gelsin diye 100+'dan başlar.
        $tree = [
            ['El Yapımı Yiyecek', '🍯', ['Reçel & Turşu', 'Baklava & Tatlı', 'Kurabiye & Kek', 'Ev Makarnası & Erişte', 'Baharat & Karışım']],
            ['Örgü & Dikiş', '🧶', ['Bebek Örgüsü', 'Kazak & Atkı & Bere', 'Nakış & El İşi', 'Dikiş Ürünleri']],
            ['Takı & Aksesuar', '💍', ['El Yapımı Takı', 'Çanta & Cüzdan', 'Saç Aksesuarı']],
            ['Ev & Dekor', '🏠', ['El Yapımı Dekor', 'Mum & Kokulu Ürün', 'Seramik & Çini', 'Tablo & Duvar Süsü']],
            ['Sanat & Hediyelik', '🎨', ['Resim & Tablo', 'Hat & Ebru', 'Kişiye Özel Hediye']],
            ['Doğal Kozmetik', '🧼', ['Doğal Sabun', 'Krem & Bakım', 'Bitkisel Ürün']],
            ['Bebek & Çocuk Ürünleri', '🧸', ['El Yapımı Bebek Ürünü', 'Oyuncak', 'Çocuk Kıyafeti']],
            ['Diğer Ürünler', '📦', ['Koleksiyon', 'İkinci El', 'Çeşitli']],
        ];

        foreach ($tree as $i => [$name, $icon, $children]) {
            $parent = Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon, 'type' => 'urun', 'sort_order' => 100 + $i, 'is_active' => true, 'parent_id' => null],
            );

            foreach ($children as $j => $childName) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    ['name' => $childName, 'type' => 'urun', 'sort_order' => $j, 'is_active' => true, 'parent_id' => $parent->id],
                );
            }
        }
    }
}
