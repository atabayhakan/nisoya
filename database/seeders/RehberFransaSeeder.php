<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Ülke Rehberi — Fransa'nın GERÇEK konsolosluk-hizmet noktalarını kurar
 * (elle çalıştırılır, deploy zincirinde DEĞİL):
 *
 *     php artisan db:seed --class=RehberFransaSeeder --force
 *
 * NEDEN GEREKLİ: Evrensel iskelet seeder Fransa için yalnız tek bir
 * temsilcilik (Paris Büyükelçiliği) oluşturmuştu. 2026-08-27 araştırması
 * konsolosluk.gov.tr'nin Fransa "Temsilcilik Seçiniz" listesinde
 * "Büyükelçilik" seçeneğinin HİÇ OLMADIĞINI, yalnız 6 başkonsolosluğun
 * (Paris, Lyon, Marsilya, Strazburg, Bordo, Nant) var olduğunu doğruladı —
 * Almanya'daki Berlin ayrımıyla aynı desen, hatta daha keskin (Büyükelçilik
 * sistemde hiç görünmüyor). Bu seeder RehberAlmanyaSeeder ile AYNI desenle
 * o 6 eksik temsilciliği ve TASLAK işlem iskeletini kurar. `firstOrCreate`:
 * ikinci koşu zararsız, panel düzenlemelerini EZMEZ.
 */
class RehberFransaSeeder extends Seeder
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
            ['ad' => 'Paris Başkonsolosluğu', 'slug' => 'paris-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Paris', 'resmi_url' => 'https://paris-bk.mfa.gov.tr', 'sort_order' => 1],
            ['ad' => 'Lyon Başkonsolosluğu', 'slug' => 'lyon-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Lyon', 'resmi_url' => 'https://lyon-bk.mfa.gov.tr', 'sort_order' => 2],
            ['ad' => 'Marsilya Başkonsolosluğu', 'slug' => 'marsilya-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Marsilya', 'resmi_url' => 'https://marsilya-bk.mfa.gov.tr', 'sort_order' => 3],
            ['ad' => 'Strazburg Başkonsolosluğu', 'slug' => 'strazburg-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Strazburg', 'resmi_url' => 'https://strazburg-bk.mfa.gov.tr', 'sort_order' => 4],
            ['ad' => 'Bordo Başkonsolosluğu', 'slug' => 'bordo-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Bordeaux', 'resmi_url' => 'https://bordo-bk.mfa.gov.tr', 'sort_order' => 5],
            ['ad' => 'Nant Başkonsolosluğu', 'slug' => 'nant-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Nantes', 'resmi_url' => 'https://nant-bk.mfa.gov.tr', 'sort_order' => 6],
        ];

        return array_map(
            fn (array $k): Temsilcilik => Temsilcilik::query()->firstOrCreate(
                ['country_code' => 'FR', 'slug' => $k['slug']],
                [...$k, 'country_code' => 'FR', 'is_active' => true],
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
