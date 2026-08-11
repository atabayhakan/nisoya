<?php

namespace Database\Seeders\Rehber;

/**
 * "Vefat ve Cenaze İşlemleri" rehber içeriğini DOĞRULANMIŞ bilgiyle doldurur.
 *
 * ---------------------------------------------------------------------------
 * NEDEN AYRI BİR SEEDER
 *
 * `RehberAlmanyaSeeder` iskeleti kuruyor ama içerik olarak kendi kendini
 * "başlangıç taslağı" ilan eden bir yer tutucu bırakıyordu: evrak listesi üç
 * genel satır, süre ve ücret BOŞ, kaynak konsolosluk.gov.tr ANA SAYFASI,
 * doğrulanma tarihi yok. Kayıtların hepsi `taslak` durumundaydı, yani sahibin
 * gördüğü "sayfalar boş duruyor" tam olarak buydu.
 *
 * ---------------------------------------------------------------------------
 * İÇERİK NEREDEN GELDİ
 *
 * T.C. Dışişleri Bakanlığı kaynakları (konsolosluk.gov.tr "Ölüm Tescili ve
 * Gerekli Belgeler" ve temsilciliklerin "Cenaze Nakil Belgesi Başvurusu"
 * bilgi notları). UYDURULMADI. Doğrulayamadığım hiçbir sayı yazılmadı:
 * harç tutarı ve işlem süresi için rakam VERİLMEDİ, bunun yerine resmî
 * kaynaktan teyit yolu gösterildi. Bu, [[nisoya-gercek-bilgi-kurali]]'nın
 * gereği — sitedeki her bilgi gerçek olmalı.
 *
 * ---------------------------------------------------------------------------
 * İNSAN DOĞRULAMASINI ASLA EZMEZ
 *
 * Yalnız `dogrulanma_tarihi` BOŞ olan kayıtlar güncellenir. Sahip bir
 * temsilciliğin sayfasını panelden elle doğruladıysa (tarih dolar), bu seeder
 * ona dokunmaz. Tekrar tekrar çalıştırılabilir.
 */
class VefatCenazeSeeder extends RehberIcerikSeeder
{
    /** İçeriğin dayandığı resmî sayfa. */
    protected const KAYNAK = 'https://www.konsolosluk.gov.tr/Procedure/ShowProcedure/8';

    /** Bu turda doğrulanan bilginin tarihi. */
    protected const DOGRULAMA = '2026-08-11';

    /**
     * Cenaze nakil belgesi başvurusunda istenen belgeler.
     * Kaynak: temsilciliklerin "Cenaze Nakil Belgesi Başvurusu" bilgi notları.
     *
     * @return list<array{ad: string, not?: string}>
     */
    protected function slug(): string
    {
        return 'olum-ve-cenaze';
    }

    protected function kaynak(): string
    {
        return self::KAYNAK;
    }

    protected function dogrulamaTarihi(): string
    {
        return self::DOGRULAMA;
    }

    protected function evraklar(): array
    {
        return [
            ['ad' => 'Başvuranın T.C. kimlik kartı', 'not' => 'Aslı ve fotokopisi'],
            ['ad' => 'Yerel makamlarca düzenlenmiş ölüm belgesi', 'not' => 'Aslı ve fotokopisi — bulunduğun ülkenin resmî makamından alınır'],
            ['ad' => 'Vefat edenin pasaportu', 'not' => 'Aslı ve fotokopisi'],
            ['ad' => 'Cenaze bilgi formu', 'not' => 'Nakil vasıtası, gün ve saat, Türkiye’de cenazeyi teslim alacak kişi — cenaze nakil firması düzenler'],
        ];
    }

    protected function sure(): string
    {
        // KISA ÇİP ALANI (string 200) — "aynı gün" / "2-4 hafta" gibi.
        // Doğrulanmış bir süre yok; boş bırakılıyor, çip hiç basılmıyor.
        // Süreyi uzatan durum `notlar`da anlatılıyor.
        return '';
    }

    protected function ucret(): string
    {
        // Doğrulanmış harç tutarı yok; uydurmak yerine boş.
        return '';
    }

    /**
     * DÜZ METİN — markdown YOK.
     *
     * Bu alan görünümde `{{ $islemKaydi->notlar }}` ile, yani kaçırılarak ve
     * `whitespace-pre-line` ile basılıyor. İlk yazışta `**kalın**` kullandım
     * ve sayfada 11 adet ham yıldız göründü (ölçüldü). Rehberin mevcut
     * üslubu da zaten düz akıcı metin.
     */
    protected function notlar(): string
    {
        return implode("\n\n", [
            'Sıra önemli: önce yerel makam, sonra temsilcilik. Vefat önce bulunduğun ülkenin resmî '
                .'makamlarına bildirilir; oradan alınan ölüm belgesi temsilciliğe verilerek ölüm Türkiye '
                .'nüfusuna tescil edilir.',

            'Başvuruyu o ülkedeki herhangi bir temsilciliğe yapabilirsin. Cenaze nakil belgesinde yetki '
                .'ilke olarak vefatın gerçekleştiği bölgenin temsilciliğindedir; ancak başvurular görev '
                .'bölgesi ayrımı gözetilmeksizin o ülkedeki tüm dış temsilciliklere yapılabilir. En yakın '
                .'temsilcilik uzaksa bu bilgi zaman kazandırır.',

            'Cenaze nakil firması zorunlu bir halka: "cenaze bilgi formu"nu firma düzenler. Nakil vasıtası, '
                .'gün ve saat ile Türkiye’de cenazeyi teslim alacak kişi bu formda yazar.',

            'Cenaze fonuna üyeysen önce fonu ara. Yurt dışındaki Türk toplumunda cenaze nakil masraflarını '
                .'karşılayan yardımlaşma fonları yaygındır (örneğin DİTİB’in cenaze fonu). Üyeysen nakil '
                .'masrafları kapsanabilir ve fon süreci senin yerine yürütebilir.',

            'Süre ve harç: belgeler tamamsa işlem temsilcilikte yürütülür. Vefat edenin vatandaşlık '
                .'durumu özel ise (sığınmacıyken başka bir vatandaşlığa geçmiş ya da vatandaşlıktan '
                .'çıkarılmış) Türkiye’ye defin için İçişleri Bakanlığı izni gerekir ve süreç uzar. Harç '
                .'tutarları işleme ve temsilciliğe göre değişir; başvuru öncesi temsilciliğin kendi '
                .'sayfasından ya da Konsolosluk Çağrı Merkezi’nden teyit et.',

            'Nisoya bu işlemi yapmaz; bu sayfa yalnız yol gösterir. Başvuru ve belgeler temsilcilik '
                .'üzerinden yürür. Konsolosluk Çağrı Merkezi 7/24 açıktır: +90 312 292 29 29.',
        ]);
    }
}
