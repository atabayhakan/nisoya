<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Ülke Rehberi — Hollanda'nın GERÇEK konsolosluk-hizmet noktalarını kurar
 * (elle çalıştırılır, deploy zincirinde DEĞİL):
 *
 *     php artisan db:seed --class=RehberHollandaSeeder --force
 *
 * NEDEN GEREKLİ: Evrensel iskelet seeder Hollanda için yalnız tek bir
 * temsilcilik (Lahey Büyükelçiliği) oluşturmuştu. 2026-08-27 araştırması
 * Lahey'in KENDİ konsolosluk şubesi olmadığını, gerçek muhatapların
 * Amsterdam/Rotterdam/Deventer Başkonsoloslukları olduğunu doğruladı — tıpkı
 * Almanya'daki Berlin Büyükelçiliği/Başkonsolosluğu ayrımı gibi. Bu seeder
 * RehberAlmanyaSeeder ile AYNI desenle o 3 eksik temsilciliği ve TASLAK
 * işlem iskeletini kurar.
 *
 * `firstOrCreate` deseni: ikinci koşu zararsız, panel düzenlemelerini EZMEZ.
 */
class RehberHollandaSeeder extends Seeder
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
            ['ad' => 'Amsterdam Başkonsolosluğu', 'slug' => 'amsterdam-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Amsterdam', 'resmi_url' => 'https://amsterdam-bk.mfa.gov.tr', 'sort_order' => 1],
            ['ad' => 'Rotterdam Başkonsolosluğu', 'slug' => 'rotterdam-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Rotterdam', 'resmi_url' => 'https://rotterdam-bk.mfa.gov.tr', 'sort_order' => 2],
            ['ad' => 'Deventer Başkonsolosluğu', 'slug' => 'deventer-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Deventer', 'resmi_url' => 'https://deventer-bk.mfa.gov.tr', 'sort_order' => 3],
        ];

        return array_map(
            fn (array $k): Temsilcilik => Temsilcilik::query()->firstOrCreate(
                ['country_code' => 'NL', 'slug' => $k['slug']],
                [...$k, 'country_code' => 'NL', 'is_active' => true],
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
