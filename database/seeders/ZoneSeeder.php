<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

/**
 * Sitedeki önceden kablolanmış "alan"ları (bkz. <x-zone zone-key="...">
 * çağrıları) kayıt olarak tanımlar. Bilinçli olarak firstOrCreate kullanılır
 * (updateOrCreate DEĞİL) — `blocks`/`is_active`/`starts_at`/`ends_at` admin
 * panelinden düzenlenen içeriktir, her deploy'da üzerine yazılmamalı. Yeni bir
 * alan eklendiğinde bu seeder yalnızca YENİ kaydı ekler, var olanlara dokunmaz.
 */
class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $adSlot = fn (string $slotId, string $format) => [
            ['type' => 'reklam', 'data' => ['slot_id' => $slotId, 'format' => $format]],
        ];

        $zones = [
            [
                'key' => 'anasayfa_ust',
                'name' => 'Anasayfa — Ülkeler listesi altı',
                'location_note' => 'Anasayfada "Ülkeler" bölümünün hemen altında, "Yeni ilanlar" bölümünden önce görünür.',
                'blocks' => $adSlot('1111111111', 'horizontal'),
                'is_active' => true,
            ],
            [
                'key' => 'anasayfa_alt',
                'name' => 'Anasayfa — sayfa sonu',
                'location_note' => 'Anasayfanın en altında, son CTA (çağrı) bölümünden sonra, footer\'dan önce görünür.',
                'blocks' => [],
                'is_active' => false,
            ],
            [
                'key' => 'ilan_listesi_ust',
                'name' => 'İlan listesi — üst',
                'location_note' => '/ilanlar sayfasında, sonuç listesinin en üstünde (filtrelerin yanında) görünür.',
                'blocks' => $adSlot('2222222222', 'horizontal'),
                'is_active' => true,
            ],
            [
                'key' => 'ilan_detay_yan',
                'name' => 'İlan detayı — yan panel altı',
                'location_note' => 'Bir ilanın detay sayfasında, sağ yan panelin (satıcı bilgisi) en altında görünür.',
                'blocks' => $adSlot('3333333333', 'rectangle'),
                'is_active' => true,
            ],
            [
                'key' => 'is_ilanlari_liste_ust',
                'name' => 'İş ilanları — liste üstü',
                'location_note' => '/isler sayfasında, filtre formunun altında, ilan kartlarından önce görünür.',
                'blocks' => [],
                'is_active' => false,
            ],
            [
                'key' => 'is_ilani_detay_alt',
                'name' => 'İş ilanı detayı — açıklama altı',
                'location_note' => 'Bir iş ilanının detay sayfasında, "İş tanımı" bölümünün altında, başvuru kutusundan önce görünür.',
                'blocks' => [],
                'is_active' => false,
            ],
            [
                'key' => 'footer_ust_serit',
                'name' => 'Footer üstü şerit (sitewide)',
                'location_note' => 'Sitenin HER sayfasında, footer\'ın hemen üstünde görünen yatay şerit.',
                'blocks' => [],
                'is_active' => false,
            ],
            [
                'key' => 'emlak_liste_ust',
                'name' => 'Emlak — liste üstü',
                'location_note' => '/emlak sayfasında, sonuç listesinin en üstünde görünür.',
                'blocks' => [],
                'is_active' => false,
            ],
            [
                'key' => 'vasita_liste_ust',
                'name' => 'Vasıta — liste üstü',
                'location_note' => '/vasita sayfasında, sonuç listesinin en üstünde görünür.',
                'blocks' => [],
                'is_active' => false,
            ],
        ];

        foreach ($zones as $zone) {
            Zone::query()->firstOrCreate(['key' => $zone['key']], $zone);
        }
    }
}
