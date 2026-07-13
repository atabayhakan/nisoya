<?php

namespace Database\Seeders;

use App\Models\NavigationLink;
use Illuminate\Database\Seeder;

/**
 * Header menüsündeki mevcut linkleri (İlanlar, İş İlanları, Harita, Nasıl
 * Çalışır?, Yetenek Havuzu) ilk kurulumda oluşturur. firstOrCreate kullanılır —
 * admin panelden yapılan ekleme/silme/yeniden adlandırma her deploy'da korunur.
 * NOT: Admin bir linkin etiketini (label) mevcut isimlerden birine değiştirirse
 * bir sonraki deploy'da o isimle yeni bir satır oluşabilir — bu, label'ın
 * eşleştirme anahtarı olarak kullanılmasının kabul edilen küçük bir sınırı.
 */
class NavigationLinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            ['label' => 'İlanlar', 'url' => '/ilanlar', 'sort_order' => 1],
            ['label' => 'Emlak', 'url' => '/emlak', 'sort_order' => 2],
            ['label' => 'İş İlanları', 'url' => '/isler', 'sort_order' => 3],
            ['label' => 'Harita', 'url' => '/harita', 'sort_order' => 4],
            ['label' => 'Nasıl Çalışır?', 'url' => '/nasil-calisir', 'sort_order' => 5],
            ['label' => 'Yetenek Havuzu', 'url' => '/adaylar', 'sort_order' => 6],
        ];

        foreach ($links as $link) {
            NavigationLink::query()->firstOrCreate(['label' => $link['label']], $link);
        }
    }
}
