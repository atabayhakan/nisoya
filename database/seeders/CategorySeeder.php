<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Diaspora odaklı hizmet kategorileri (üst => alt kategoriler).
        // "Acil Yardım" bilinçli olarak listenin başında: hem kategori
        // ızgarasında öne çıksın hem de sort_order'ı en düşük (0) olsun —
        // header'daki "Acil" hızlı-erişim butonu bu kategorinin alt
        // kategorilerini (bkz. AppServiceProvider) sort_order'a göre listeler.
        $tree = [
            // Avukatlar burada, çünkü diasporada hukuki acil durum gerçek ve
            // sık: gözaltı, sınır dışı işlemi, iş kazası, ani tahliye. Rutin
            // hukuki danışmanlık arayan kişinin "Acil Yardım" altına bakmayacağı
            // doğru — o ihtiyaç büyürse "Çeviri & Resmi İşler" altına ayrı bir
            // "Hukuki Danışmanlık" açılır (kategori tek üstlü, ikisi bir arada olmaz).
            // Cenaze Hizmetleri buraya AİT: diasporada vefat, ailenin en çaresiz
            // kaldığı andır — yabancı bir sistemde, yabancı bir dilde, saatler
            // içinde karar vermek gerekir. Rehberdeki "Vefat ve Cenaze İşlemleri"
            // sayfası resmî süreci anlatır; burası o süreçte YANINDA olacak
            // kişiyi aramak içindir. İkisi birbirinin yerine geçmez.
            ['Acil Yardım', '🚨', [['Doktorlar', '🩺'], ['Çilingirler', '🔑'], ['Avukatlar', '⚖️'], ['Cenaze Hizmetleri', '🕊️']]],
            ['Eğitim & Ders', '📚', ['Yabancı Dil Dersi', 'Çocuklara Türkçe', 'Müzik Dersi', 'Özel Ders & Okul Desteği', 'Sınav Hazırlık', 'Yazılım & Kodlama Eğitimi']],
            ['Ev & Tamir', '🔧', ['Nakliyat & Taşınma', 'Tadilat & Boya', 'Mobilya Montaj', 'Tesisat & Elektrik', 'Ev Temizliği', 'Bahçe & Peyzaj']],
            ['Yemek & İkram', '🍽️', ['Ev Yemeği', 'Pasta & Tatlı', 'Catering', 'Özel Gün Menüleri']],
            ['Güzellik & Bakım', '💇', ['Kuaför & Berber', 'Makyaj', 'Cilt & Tırnak Bakımı', 'Gelin Hazırlık']],
            ['Çocuk & Aile', '👶', ['Bebek Bakıcılığı', 'Yaşlı Bakımı', 'Ev İçi Yardım']],
            ['Etkinlik & Medya', '📷', ['Fotoğraf & Video', 'DJ & Müzik', 'Organizasyon', 'Davetiye & Tasarım']],
            ['Dijital & Tasarım', '💻', ['Web Sitesi', 'Grafik & Logo', 'Sosyal Medya Yönetimi', 'İçerik & Metin Yazarlığı']],
            ['Çeviri & Resmi İşler', '📝', ['Tercüme', 'Evrak & Başvuru Desteği', 'Danışmanlık']],
            // "Sürücü Kursu & Ehliyet" Eğitim değil Oto altında: ehliyet arayan
            // kişi ulaşım kafasıyla geziyor, ders kafasıyla değil. ABD'deki
            // ticari ehliyet (CDL/tır) talebi bu kategorinin en güçlü alt konusu.
            ['Oto & Ulaşım', '🚗', ['Havalimanı Transferi', 'Şoförlük', 'Eşya Taşıma', 'Sürücü Kursu & Ehliyet']],
            ['Diğer Hizmetler', '🧰', ['Terzi & Tadilat', 'Tamir & Onarım', 'Kişisel Asistan']],
        ];

        foreach ($tree as $i => [$name, $icon, $children]) {
            $parent = Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon, 'type' => 'hizmet', 'sort_order' => $i, 'is_active' => true, 'parent_id' => null],
            );

            foreach ($children as $j => $child) {
                // Alt kategoriler normalde kendi ikonunu taşımaz (kartlarda üst
                // kategorinin ikonuna düşer) — ama "Acil" butonu gibi alt
                // kategorinin doğrudan gösterildiği yerlerde ayırt edici bir
                // ikon gerekebilir, bu yüzden [isim, ikon] çifti de destekleniyor.
                [$childName, $childIcon] = is_array($child) ? $child : [$child, null];

                Category::updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    ['name' => $childName, 'icon' => $childIcon, 'type' => 'hizmet', 'sort_order' => $j, 'is_active' => true, 'parent_id' => $parent->id],
                );
            }
        }
    }
}
