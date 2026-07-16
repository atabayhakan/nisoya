<?php

namespace Database\Seeders;

use App\Models\NavigationLink;
use Illuminate\Database\Seeder;

/**
 * Header menüsündeki mevcut linkleri (İlanlar, İş İlanları, Emlak, Vasıta,
 * Anılar & Davetiyeler, Harita, Nasıl Çalışır?, Yetenek Havuzu) ilk kurulumda
 * oluşturur. firstOrCreate kullanılır — admin panelden yapılan
 * ekleme/silme/yeniden adlandırma her deploy'da korunur.
 * NOT: Admin bir linkin etiketini (label) mevcut isimlerden birine değiştirirse
 * bir sonraki deploy'da o isimle yeni bir satır oluşabilir — bu, label'ın
 * eşleştirme anahtarı olarak kullanılmasının kabul edilen küçük bir sınırı.
 * NOT 2: firstOrCreate mevcut satırları GÜNCELLEMEZ — canlıda zaten var olan
 * satırların group_key/icon/description'ı bu dosya değil,
 * 2026_07_16_100000_add_group_fields_to_navigation_links_table migration'ı
 * tarafından backfill edilir. Buradaki değerler yalnızca sıfırdan kurulum
 * (fresh install) ve yeni satırlar (Anılar & Davetiyeler) için geçerlidir.
 */
class NavigationLinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            ['label' => 'İlanlar', 'url' => '/ilanlar', 'group_key' => NavigationLink::GROUP_KESFET, 'icon' => 'shopping-bag', 'description' => 'Hizmet ve ev ürünü ilanları', 'sort_order' => 1],
            ['label' => 'Yetenek Havuzu', 'url' => '/adaylar', 'group_key' => NavigationLink::GROUP_KESFET, 'icon' => 'user-group', 'description' => 'Yeteneğini göster, iş bul', 'sort_order' => 2],
            ['label' => 'İş İlanları', 'url' => '/isler', 'group_key' => NavigationLink::GROUP_KESFET, 'icon' => 'briefcase', 'description' => 'Kariyer fırsatları', 'sort_order' => 3],
            ['label' => 'Emlak', 'url' => '/emlak', 'group_key' => NavigationLink::GROUP_KESFET, 'icon' => 'home', 'description' => 'Satılık ve kiralık ilanlar', 'sort_order' => 4],
            ['label' => 'Vasıta', 'url' => '/vasita', 'group_key' => NavigationLink::GROUP_KESFET, 'icon' => 'truck', 'description' => 'Satılık ve kiralık araç', 'sort_order' => 5],
            ['label' => 'Anılar & Davetiyeler', 'url' => '/mutlu-anlar', 'group_key' => NavigationLink::GROUP_KESFET, 'icon' => 'gift', 'description' => 'Etkinlik anılarını keşfet', 'sort_order' => 6],
            ['label' => 'Harita', 'url' => '/harita', 'icon' => 'map', 'sort_order' => 7],
            ['label' => 'Nasıl Çalışır?', 'url' => '/nasil-calisir', 'icon' => 'question-mark-circle', 'sort_order' => 8],
        ];

        foreach ($links as $link) {
            NavigationLink::query()->firstOrCreate(['label' => $link['label']], $link);
        }
    }
}
