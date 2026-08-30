<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Ülke Rehberi — Birleşik Krallık'ın GERÇEK konsolosluk-hizmet noktalarını
 * kurar (elle çalıştırılır, deploy zincirinde DEĞİL):
 *
 *     php artisan db:seed --class=RehberBirlesikKrallikSeeder --force
 *
 * NEDEN GEREKLİ: Evrensel iskelet seeder Birleşik Krallık için yalnız tek
 * bir temsilcilik (Londra Büyükelçiliği) oluşturmuştu. 2026-08-27 araştırması
 * Londra Büyükelçiliği'nin KENDİ konsolosluk şubesi olmadığını (bu 8 hizmeti
 * hiç sunmadığını), gerçek muhatapların Londra/Edinburgh/Manchester
 * Başkonsoloslukları olduğunu doğruladı — Almanya'daki Berlin ayrımıyla
 * aynı desen. Bu seeder RehberAlmanyaSeeder ile AYNI desenle o 3 eksik
 * temsilciliği ve TASLAK işlem iskeletini kurar. `firstOrCreate`: ikinci
 * koşu zararsız, panel düzenlemelerini EZMEZ.
 */
class RehberBirlesikKrallikSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = $this->temsilcilikleriEkle();
        $turler = IslemTuru::query()->get()->keyBy('slug');
        $this->islemIskeletiniKur($temsilcilikler, $turler);
    }

    /** @return array<int, Temsilcilik> */
    protected function temsilcilikleriEkle(): array
    {
        $kayitlar = [
            ['ad' => 'Londra Başkonsolosluğu', 'slug' => 'londra-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Londra', 'resmi_url' => 'https://londra-bk.mfa.gov.tr', 'sort_order' => 1],
            ['ad' => 'Edinburgh Başkonsolosluğu', 'slug' => 'edinburgh-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Edinburgh', 'resmi_url' => 'https://edinburgh-bk.mfa.gov.tr', 'sort_order' => 2],
            ['ad' => 'Manchester Başkonsolosluğu', 'slug' => 'manchester-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Manchester', 'resmi_url' => 'https://manchester-bk.mfa.gov.tr', 'sort_order' => 3],
        ];

        return array_map(
            fn (array $k): Temsilcilik => Temsilcilik::query()->firstOrCreate(
                ['country_code' => 'GB', 'slug' => $k['slug']],
                [...$k, 'country_code' => 'GB', 'is_active' => true],
            ),
            $kayitlar,
        );
    }

    /**
     * @param  array<int, Temsilcilik>  $temsilcilikler
     * @param  Collection<string, IslemTuru>  $turler
     */
    protected function islemIskeletiniKur(array $temsilcilikler, $turler): void
    {
        foreach ($temsilcilikler as $temsilcilik) {
            foreach ($turler as $slug => $tur) {
                TemsilcilikIslemi::query()->firstOrCreate(
                    ['temsilcilik_id' => $temsilcilik->id, 'islem_turu_id' => $tur->id],
                    [
                        'evraklar' => RehberAlmanyaSeeder::genelEvraklar($slug),
                        'notlar' => RehberAlmanyaSeeder::genelNot($slug),
                        'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
                        'status' => TemsilcilikIslemi::STATUS_TASLAK,
                    ],
                );
            }
        }
    }
}
