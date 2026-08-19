<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Temsilcilik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Dünya geneli temsilcilik İSKELETİ (docs/03-buyume-fikirleri.md, öneri 1,
 * 2026-08-19 — "her ülkeye en azından iskelet kaydı").
 *
 * ---------------------------------------------------------------------------
 * ÖLÇÜLEN BOŞLUK
 *
 * Production'da 57 aktif ülkenin temsilcilik kaydı HİÇ yoktu — bu yüzden
 * Rehber'in ülke sayfası (`/{ulke}`) "hazırlanıyor" diyor ve RehberDogalDilArama
 * (Nisoya AI arama) o ülkelerde önerecek TEK BİR KAYIT bile bulamıyordu.
 * Bu seeder o 57'den 55'ini (Kıbrıs Rum Kesimi ve KKTC BİLEREK dışarıda —
 * ikisi de ayrı, dikkatli bir karar gerektiriyor) gerçek büyükelçilik
 * şehriyle dolduruyor.
 *
 * ---------------------------------------------------------------------------
 * KAPSAM BİLEREK DAR — İŞLEM DETAYI YOK
 *
 * Bu, Almanya/ABD gibi işlem-seviyesi (vekaletname, pasaport...) doldurulmuş
 * bir seeder DEĞİL. Yalnız temsilciliğin VAR OLDUĞUNU ve NEREDE olduğunu
 * kaydediyor — `yonlendirme_notu` ile (Bişkek'teki desenin GENELLEŞTİRİLMİŞ
 * hâli, ama Bişkek'in "bu temsilcilik kendi sitesinde yayınlamıyor" gibi
 * SPESİFİK bir iddiasını TEKRARLAMIYOR — o iddia yalnız Bişkek için
 * doğrulanmıştı, 55 ofisin her biri için doğrulanmadı). Amaç: (a) Rehber
 * ülke sayfası artık "hazırlanıyor" yerine gerçek bir temsilcilik gösterir,
 * (b) Nisoya AI arama artık her ülkede önerecek bir şey bulur (bkz.
 * RehberDogalDilArama::temsilciklerleBul), (c) Google'da en azından
 * "[ülke] Türk büyükelçiliği" türü temel aramalar için gerçek bir sayfa
 * vardır.
 *
 * ---------------------------------------------------------------------------
 * KAYNAK (2026-08-19, WebSearch ile doğrulandı — özet/tahmin değil)
 *
 * Türkiye Cumhuriyeti Dışişleri Bakanlığı'nın resmî temsilcilik listesi
 * (mfa.gov.tr) + Türkçe Vikipedi'nin "Türkiye'nin diplomatik temsilcilikleri
 * listesi" sayfası, birkaç ülke için mfa.gov.tr alt alan adlarıyla (ör.
 * kanberra-be.mfa.gov.tr, wellington.be.mfa.gov.tr, atina-be.mfa.gov.tr,
 * ljubljana-be.mfa.gov.tr) çapraz doğrulandı.
 *
 * ÜÇ MİNİK DEVLET AYRI: San Marino, Andorra, Lihtenştayn'ın KENDİ
 * büyükelçiliği yok — komşu ülkedeki büyükelçilik oradan akredite (Roma,
 * Madrid, Bern). `sehir`/`ad` bunu AÇIKÇA yazıyor, gerçek olmayan bir
 * "kendi büyükelçiliği var" izlenimi vermiyor.
 *
 * ---------------------------------------------------------------------------
 * BİLEREK DIŞARIDA: KIBRIS (RUM KESİMİ) VE KKTC
 *
 * İkisi de bu partiden ayrı tutuldu — siyasi çerçeve bu oturumda daha önce
 * (ülke listesi eklenirken) sahiple birlikte tek tek karara bağlanmıştı;
 * aynı özen burada da gerekiyor, tek taraflı yazılmadı.
 */
class RehberDunyaTemsilcilikIskeletiSeeder extends Seeder
{
    private const YONLENDIRME_NOTU = 'Bu ülkedeki konsolosluk işlemleri (vekaletname, pasaport, nüfus kayıtları ve diğerleri) için güncel evrak listesi ve randevu bilgisi, Dışişleri Bakanlığı\'nın merkezî konsolosluk portalında yer alır.';

    /**
     * [ülke kodu, şehir, akredite eden şehir (varsa)].
     *
     * Üçüncü eleman yalnız San Marino/Andorra/Lihtenştayn için dolu —
     * "kendi büyükelçiliği yok, X şehrindeki büyükelçilikten akredite"
     * gerçeğini `ad` alanına taşımak için.
     *
     * @var list<array{0: string, 1: string, 2: ?string}>
     */
    private const ULKELER = [
        ['NL', 'Lahey', null],
        ['GB', 'Londra', null],
        ['FR', 'Paris', null],
        ['AT', 'Viyana', null],
        ['BE', 'Brüksel', null],
        ['CH', 'Bern', null],
        ['SE', 'Stockholm', null],
        ['NO', 'Oslo', null],
        ['DK', 'Kopenhag', null],
        ['CA', 'Ottawa', null],
        ['AU', 'Canberra', null],
        ['IT', 'Roma', null],
        ['ES', 'Madrid', null],
        ['PL', 'Varşova', null],
        ['AZ', 'Bakü', null],
        ['KZ', 'Astana', null],
        ['UZ', 'Taşkent', null],
        ['TM', 'Aşkabat', null],
        ['RU', 'Moskova', null],
        ['AE', 'Abu Dabi', null],
        ['QA', 'Doha', null],
        ['SA', 'Riyad', null],
        ['IS', 'Reykjavik', null],
        ['TH', 'Bangkok', null],
        ['IE', 'Dublin', null],
        ['FI', 'Helsinki', null],
        ['SG', 'Singapur', null],
        ['NZ', 'Wellington', null],
        ['KR', 'Seul', null],
        ['SI', 'Ljubljana', null],
        ['JP', 'Tokyo', null],
        ['MT', 'Valletta', null],
        ['LU', 'Lüksemburg', null],
        ['IL', 'Tel Aviv', null],
        ['CZ', 'Prag', null],
        ['GR', 'Atina', null],
        ['EE', 'Tallinn', null],
        ['BH', 'Manama', null],
        ['LT', 'Vilnius', null],
        ['PT', 'Lizbon', null],
        ['LV', 'Riga', null],
        ['HR', 'Zagreb', null],
        ['SK', 'Bratislava', null],
        ['CL', 'Santiago', null],
        ['HU', 'Budapeşte', null],
        ['AR', 'Buenos Aires', null],
        ['ME', 'Podgorica', null],
        ['UY', 'Montevideo', null],
        ['OM', 'Maskat', null],
        ['KW', 'Kuveyt', null],
        ['KH', 'Phnom Penh', null],
        ['ID', 'Cakarta', null],
        // Kendi büyükelçiliği yok — komşu ülkeden akredite (2026-08-19 doğrulandı).
        ['SM', 'Roma', 'San Marino\'ya akredite'],
        ['AD', 'Madrid', 'Andorra\'ya akredite'],
        ['LI', 'Bern', 'Lihtenştayn\'a akredite'],
    ];

    public function run(): void
    {
        $mevcutUlkeler = Country::query()->where('is_active', true)->pluck('code')->flip();
        $sirasi = (int) Temsilcilik::query()->max('sort_order');
        $atlanan = [];

        foreach (self::ULKELER as [$ulkeKodu, $sehir, $akrediteNotu]) {
            if (! isset($mevcutUlkeler[$ulkeKodu])) {
                // Ülke tablosunda yoksa (ileride kaldırılmışsa) sessizce atla —
                // var olmayan bir country_code'a kayıt bağlamak yanlış olur.
                $atlanan[] = $ulkeKodu;

                continue;
            }

            $ad = $akrediteNotu !== null
                ? "{$sehir} Büyükelçiliği ({$akrediteNotu})"
                : "{$sehir} Büyükelçiliği";

            Temsilcilik::query()->updateOrCreate(
                ['country_code' => $ulkeKodu, 'slug' => Str::slug($sehir)],
                [
                    'ad' => $ad,
                    'tur' => Temsilcilik::TUR_BUYUKELCILIK,
                    'sehir' => $sehir,
                    'is_active' => true,
                    'sort_order' => ++$sirasi,
                    'yonlendirme_notu' => self::YONLENDIRME_NOTU,
                ],
            );
        }

        if ($atlanan !== []) {
            $this->command?->warn('Ülke tablosunda bulunamayan kodlar atlandı: '.implode(', ', $atlanan));
        }

        $this->command?->info(count(self::ULKELER) - count($atlanan).' temsilcilik iskeleti oluşturuldu/güncellendi.');
    }
}
