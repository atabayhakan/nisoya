<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Ülke Rehberi — Avusturya'nın GERÇEK konsolosluk-hizmet noktalarını kurar
 * (elle çalıştırılır, deploy zincirinde DEĞİL):
 *
 *     php artisan db:seed --class=RehberAvusturyaSeeder --force
 *
 * NEDEN GEREKLİ: Evrensel iskelet seeder Avusturya için yalnız tek bir
 * temsilcilik (Viyana Büyükelçiliği) oluşturmuştu. 2026-08-27 araştırması
 * Viyana Büyükelçiliği'nin kendi sitesinde "Büyükelçiliğimizde konsolosluk
 * hizmetleri verilmemektedir" ifadesini doğrudan buldu; gerçek muhataplar
 * Viyana/Salzburg/Bregenz Başkonsoloslukları — Almanya'daki Berlin ayrımıyla
 * aynı desen. Bu seeder RehberAlmanyaSeeder ile AYNI desenle o 3 eksik
 * temsilciliği ve TASLAK işlem iskeletini kurar. `firstOrCreate`: ikinci
 * koşu zararsız, panel düzenlemelerini EZMEZ.
 */
class RehberAvusturyaSeeder extends Seeder
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
            ['ad' => 'Viyana Başkonsolosluğu', 'slug' => 'viyana-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Viyana', 'resmi_url' => 'https://viyana-bk.mfa.gov.tr', 'sort_order' => 1],
            ['ad' => 'Salzburg Başkonsolosluğu', 'slug' => 'salzburg-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Salzburg', 'resmi_url' => 'https://salzburg-bk.mfa.gov.tr', 'sort_order' => 2],
            ['ad' => 'Bregenz Başkonsolosluğu', 'slug' => 'bregenz-bk', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Bregenz', 'resmi_url' => 'https://bregenz-bk.mfa.gov.tr', 'sort_order' => 3],
        ];

        return array_map(
            fn (array $k): Temsilcilik => Temsilcilik::query()->firstOrCreate(
                ['country_code' => 'AT', 'slug' => $k['slug']],
                [...$k, 'country_code' => 'AT', 'is_active' => true],
            ),
            $kayitlar,
        );
    }

    /**
     * @param  array<int, Temsilcilik>  $temsilcilikler
     * @param  \Illuminate\Support\Collection<string, IslemTuru>  $turler
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
