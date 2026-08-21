<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Yalnızca CANLIDA HER DEPLOY'DA GÜVENLE çalıştırılabilen referans/taksonomi
 * verileri. Hepsi idempotenttir — mevcut kayıtları bozmaz, yeni kod ile gelen
 * referans verilerini (yeni ülke/kategori/para birimi/alan vb.) canlıya
 * otomatik taşır. deploy.sh bunu `db:seed --class=ReferenceDataSeeder
 * --force` ile çağırır. ZoneSeeder firstOrCreate kullanır (updateOrCreate
 * DEĞİL) — admin panelinden düzenlenen alan içeriğinin üzerine yazmaz.
 *
 * DİKKAT: Buraya ASLA AdminUserSeeder (admin parolasını sıfırlar),
 * DemoSeeder (sahte veri ekler) veya SiteSettingSeeder / StaticPagesSeeder
 * (admin panelden yönetilen içeriği geri getirebilir) EKLENMEZ.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
            CategorySeeder::class,
            ProductCategorySeeder::class,
            PropertyCategorySeeder::class,
            VehicleCategorySeeder::class,
            JobCategorySeeder::class,
            ZoneSeeder::class,
            NavigationLinkSeeder::class,
            HomeHighlightSeeder::class,

            // Yaşam Rehberi'nin 8 sabit kategorisi — ülkeden bağımsız
            // taksonomi, aynı firstOrCreate sözleşmesi.
            YasamKategorileriSeeder::class,

            // Temsilcilikler referans veridir (ülke/şehir gibi): kamuya açık,
            // yavaş değişen gerçekler. Seeder MUHAFAZAKÂR davranır — var olan
            // kaydın yalnız ÖLÇÜLMÜŞ kırık adresini onarır, başka alana
            // dokunmaz; panelden yapılan düzeltmeler deploy'da ezilmez.
            RehberTemsilcilikleriSeeder::class,

            // Rehber İÇERİĞİ. Aynı muhafazakâr sözleşme: yalnız
            // `dogrulanma_tarihi` BOŞ olan (yani hâlâ genel taslak olan)
            // kayıtları doldurur. Sahip panelden bir sayfayı doğruladıysa
            // deploy onu EZMEZ.
            Rehber\VefatCenazeSeeder::class,
            Rehber\ApostilSeeder::class,
        ]);
    }
}
