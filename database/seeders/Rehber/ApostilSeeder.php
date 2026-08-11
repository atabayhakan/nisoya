<?php

namespace Database\Seeders\Rehber;

/**
 * Apostil.
 *
 * ---------------------------------------------------------------------------
 * BU SAYFANIN DEĞERİ "HAYIR" DEMESİNDE
 *
 * İnsanlar apostil için konsolosluktan randevu alıyor ve boşuna gidiyor:
 * yurtdışındaki Türk temsilcilikleri apostil şerhi DÜZENLEMEZ. Apostili,
 * belgenin DÜZENLENDİĞİ ülkenin kendi yetkili makamı verir.
 *
 * Doğru cevabı vermek, listeyi uzatmaktan daha değerli — bu sayfa bir
 * randevuyu ve bir yol masrafını kurtarıyor.
 *
 * Kaynaklar: Lahey Apostil Sözleşmesi uygulaması; Almanya için Auswärtiges
 * Amt'ın "Apostille-Behörden in Deutschland" sayfası (idari belgelerde
 * Bezirksregierung yetkili); Türkiye'de valilik/kaymakamlık ve adli yargı
 * komisyonları.
 */
class ApostilSeeder extends RehberIcerikSeeder
{
    protected function slug(): string
    {
        return 'apostil';
    }

    protected function kaynak(): string
    {
        return 'https://www.germany.info/us-de/service/beurkundungen/apostille-behoerde-deutschland-1217158';
    }

    protected function dogrulamaTarihi(): string
    {
        return '2026-08-12';
    }

    protected function evraklar(): array
    {
        return [
            ['ad' => 'Apostil şerhi konulacak belgenin aslı', 'not' => 'Fotokopiye apostil verilmez'],
            ['ad' => 'Başvuranın kimlik belgesi'],
        ];
    }

    protected function sure(): string
    {
        // KISA ÇİP ALANI (string 200). Doğrulanmış süre yok; boş.
        return '';
    }

    protected function ucret(): string
    {
        // Harcı şerhi veren makam belirler, temsilcilik değil; çipte gösterilecek
        // tek bir tutar yok.
        return '';
    }

    protected function notlar(): string
    {
        return implode("\n\n", [
            'Önemli: Türk dış temsilcilikleri apostil şerhi düzenlemez. Bu işlem için konsolosluktan '
                .'randevu almana gerek yok.',

            'Apostili, belgenin DÜZENLENDİĞİ ülkenin yetkili makamı verir. Türkiye’de düzenlenmiş bir '
                .'belge (diploma, nüfus kaydı, mahkeme kararı vb.) için apostil Türkiye’deki valilik ya da '
                .'kaymakamlıklardan, adli belgeler içinse adli yargı komisyonlarından alınır. '
                .'Türkiye’deki bir yakınına vekalet vererek bu işlemi yaptırabilirsin — vekaletnameyi '
                .'temsilcilikten alabilirsin.',

            'Bulunduğun ülkede düzenlenmiş bir belge için apostil, o ülkenin kendi makamından alınır. '
                .'Almanya’da idari belgelerde bölge yönetimi (Bezirksregierung) yetkilidir; mahkeme ve '
                .'noter belgelerinde yetkili makam farklıdır. Hangi makamın yetkili olduğu belgeyi '
                .'düzenleyen kuruma göre değişir.',

            'Apostil yalnız Lahey Apostil Sözleşmesi’ne taraf ülkeler arasında geçerlidir. Taraf olmayan '
                .'bir ülke söz konusuysa apostil yerine konsolosluk tasdiki (legalizasyon) zinciri gerekir; '
                .'bu ayrı bir işlemdir.',

            'Süre ve harç, apostili veren makama göre değişir; bazı makamlar yalnız posta ile başvuru '
                .'kabul ettiği için işlem uzayabilir. Güncel süre ve tutarı ilgili makamın kendi sayfasından '
                .'teyit et.',

            'Konsolosluk tasdiki ile apostil aynı şey değildir. Temsilcilik bir belgenin suretini ya da '
                .'imzasını tasdik edebilir; bu tasdik apostil yerine geçmez.',
        ]);
    }
}
