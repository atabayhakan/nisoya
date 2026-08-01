<?php

namespace Database\Seeders;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Database\Seeder;

/**
 * Ülke Rehberi F1 — Almanya tohumu (elle çalıştırılır, deploy zincirinde DEĞİL):
 *
 *     php artisan db:seed --class=RehberAlmanyaSeeder --force
 *
 * OgrenciRehberiSeeder deseni: `firstOrCreate` (ikinci koşu zararsız, panel
 * düzenlemelerini EZMEZ) + içerik TASLAK doğar. Üç katman tohumlanır:
 *
 * 1. TEMSİLCİLİKLER (aktif): Almanya'daki Türk dış temsilcilikleri kamuya
 *    açık, yavaş değişen gerçeklerdir. resmi_url alanları Dışişleri'nin
 *    standart alan adı deseniyle ({sehir}.bk.mfa.gov.tr) üretildi — sahip
 *    panelden teyit etmeli (yanlışsa tek alan düzeltilir, sayfa çalışmaya
 *    devam eder).
 *
 * 2. İŞLEM TÜRLERİ (aktif): ülke-bağımsız şablon (~15 tür) — yeni ülke
 *    eklerken yeniden kurulmaz.
 *
 * 3. İŞLEM İÇERİKLERİ (TASLAK): temsilcilik × tür matrisi, tür başına
 *    GENEL bir başlangıç içeriğiyle doğar. K7 sözleşmesi: hiçbiri sahip
 *    resmî kaynaktan doğrulayıp yayına almadan sitede GÖRÜNMEZ. Genel
 *    içerik "boş sayfayla başlama" derdini çözer; doğruluk sözü vermez —
 *    o söz doğrulama adımında verilir.
 */
class RehberAlmanyaSeeder extends Seeder
{
    public function run(): void
    {
        $temsilcilikler = $this->temsilcilikleriEkle();
        $turler = $this->islemTurleriniEkle();
        $this->islemIskeletiniKur($temsilcilikler, $turler);
    }

    /** @return array<int, Temsilcilik> */
    protected function temsilcilikleriEkle(): array
    {
        $kayitlar = [
            ['ad' => 'Berlin Büyükelçiliği', 'slug' => 'berlin-buyukelciligi', 'tur' => Temsilcilik::TUR_BUYUKELCILIK, 'sehir' => 'Berlin', 'resmi_url' => 'https://berlin.be.mfa.gov.tr', 'sort_order' => 0],
            ['ad' => 'Berlin Başkonsolosluğu', 'slug' => 'berlin', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Berlin', 'resmi_url' => 'https://berlin.bk.mfa.gov.tr', 'sort_order' => 1],
            ['ad' => 'Düsseldorf Başkonsolosluğu', 'slug' => 'duesseldorf', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Düsseldorf', 'resmi_url' => 'https://dusseldorf.bk.mfa.gov.tr', 'sort_order' => 2],
            ['ad' => 'Essen Başkonsolosluğu', 'slug' => 'essen', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Essen', 'resmi_url' => 'https://essen.bk.mfa.gov.tr', 'sort_order' => 3],
            ['ad' => 'Frankfurt Başkonsolosluğu', 'slug' => 'frankfurt', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Frankfurt', 'resmi_url' => 'https://frankfurt.bk.mfa.gov.tr', 'sort_order' => 4],
            ['ad' => 'Hamburg Başkonsolosluğu', 'slug' => 'hamburg', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Hamburg', 'resmi_url' => 'https://hamburg.bk.mfa.gov.tr', 'sort_order' => 5],
            ['ad' => 'Hannover Başkonsolosluğu', 'slug' => 'hannover', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Hannover', 'resmi_url' => 'https://hannover.bk.mfa.gov.tr', 'sort_order' => 6],
            ['ad' => 'Karlsruhe Başkonsolosluğu', 'slug' => 'karlsruhe', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Karlsruhe', 'resmi_url' => 'https://karlsruhe.bk.mfa.gov.tr', 'sort_order' => 7],
            ['ad' => 'Köln Başkonsolosluğu', 'slug' => 'koeln', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Köln', 'resmi_url' => 'https://koln.bk.mfa.gov.tr', 'sort_order' => 8],
            ['ad' => 'Mainz Başkonsolosluğu', 'slug' => 'mainz', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Mainz', 'resmi_url' => 'https://mainz.bk.mfa.gov.tr', 'sort_order' => 9],
            ['ad' => 'Münih Başkonsolosluğu', 'slug' => 'muenchen', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'München', 'resmi_url' => 'https://munih.bk.mfa.gov.tr', 'sort_order' => 10],
            ['ad' => 'Münster Başkonsolosluğu', 'slug' => 'muenster', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Münster', 'resmi_url' => 'https://munster.bk.mfa.gov.tr', 'sort_order' => 11],
            ['ad' => 'Nürnberg Başkonsolosluğu', 'slug' => 'nuernberg', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Nürnberg', 'resmi_url' => 'https://nurnberg.bk.mfa.gov.tr', 'sort_order' => 12],
            ['ad' => 'Stuttgart Başkonsolosluğu', 'slug' => 'stuttgart', 'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Stuttgart', 'resmi_url' => 'https://stuttgart.bk.mfa.gov.tr', 'sort_order' => 13],
        ];

        return array_map(
            fn (array $k): Temsilcilik => Temsilcilik::query()->firstOrCreate(
                ['country_code' => 'DE', 'slug' => $k['slug']],
                [...$k, 'country_code' => 'DE', 'is_active' => true],
            ),
            $kayitlar,
        );
    }

    /** @return array<string, IslemTuru> slug => model */
    protected function islemTurleriniEkle(): array
    {
        $turler = [
            ['slug' => 'vekaletname', 'ad' => 'Vekaletname', 'aciklama' => 'Türkiye\'deki işlerin için birine yetki ver — konsolosluk noterlik hizmeti.'],
            ['slug' => 'pasaport', 'ad' => 'Pasaport', 'aciklama' => 'Yeni pasaport başvurusu ve süre uzatma.'],
            ['slug' => 'kimlik-karti', 'ad' => 'T.C. Kimlik Kartı', 'aciklama' => 'Yeni kimlik kartı başvurusu ve yenileme.'],
            ['slug' => 'dogum-tescili', 'ad' => 'Doğum Bildirimi', 'aciklama' => 'Yurtdışında doğan çocuğun Türkiye nüfusuna kaydı.'],
            ['slug' => 'evlilik-tescili', 'ad' => 'Evlenme Bildirimi', 'aciklama' => 'Yurtdışında yapılan evliliğin Türkiye\'de tescili.'],
            ['slug' => 'olum-ve-cenaze', 'ad' => 'Vefat ve Cenaze İşlemleri', 'aciklama' => 'Vefat bildirimi ve cenazenin Türkiye\'ye nakli.'],
            ['slug' => 'askerlik', 'ad' => 'Askerlik İşlemleri', 'aciklama' => 'Dövizle askerlik ve erteleme başvuruları.'],
            ['slug' => 'mavi-kart', 'ad' => 'Mavi Kart', 'aciklama' => 'Vatandaşlıktan izinle çıkanlar için Mavi Kart başvurusu.'],
            ['slug' => 'vatandaslik', 'ad' => 'Vatandaşlık İşlemleri', 'aciklama' => 'Vatandaşlık başvurusu, izinle çıkma ve yeniden kazanma.'],
            ['slug' => 'noter-tasdik', 'ad' => 'İmza ve Suret Tasdiki', 'aciklama' => 'Belge sureti onayı ve imza doğrulama — konsolosluk noterlik hizmeti.'],
            ['slug' => 'apostil', 'ad' => 'Apostil', 'aciklama' => 'Belgenin uluslararası geçerliliği — nereden ve nasıl alınır.'],
            ['slug' => 'ehliyet', 'ad' => 'Sürücü Belgesi', 'aciklama' => 'Türk ehliyetiyle ilgili konsolosluk işlemleri.'],
            ['slug' => 'adres-kaydi', 'ad' => 'Yurtdışı Adres Kaydı', 'aciklama' => 'Adres Kayıt Sistemi\'ne (AKS) yurtdışı adres bildirimi.'],
            ['slug' => 'nufus-kayit-ornegi', 'ad' => 'Nüfus Kayıt Örneği', 'aciklama' => 'Nüfus kayıt örneği ve diğer nüfus belgeleri.'],
            ['slug' => 'tercume-tasdiki', 'ad' => 'Tercüme Tasdiki', 'aciklama' => 'Çevirinin aslına uygunluğunun onaylanması.'],
        ];

        $out = [];
        foreach ($turler as $i => $t) {
            $out[$t['slug']] = IslemTuru::query()->firstOrCreate(
                ['slug' => $t['slug']],
                [...$t, 'is_active' => true, 'sort_order' => $i],
            );
        }

        return $out;
    }

    /**
     * Temsilcilik × tür matrisi — TASLAK iskeleti. Sahip panelden ("İşlem
     * İçerikleri", taslak rozetiyle görünür) temsilciliğe özgü farkları
     * işleyip doğrulayarak yayına alır.
     *
     * @param  array<int, Temsilcilik>  $temsilcilikler
     * @param  array<string, IslemTuru>  $turler
     */
    protected function islemIskeletiniKur(array $temsilcilikler, array $turler): void
    {
        foreach ($temsilcilikler as $temsilcilik) {
            foreach ($turler as $slug => $tur) {
                TemsilcilikIslemi::query()->firstOrCreate(
                    ['temsilcilik_id' => $temsilcilik->id, 'islem_turu_id' => $tur->id],
                    [
                        'evraklar' => $this->genelEvraklar($slug),
                        'notlar' => $this->genelNot($slug),
                        'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
                        'status' => TemsilcilikIslemi::STATUS_TASLAK,
                    ],
                );
            }
        }
    }

    /**
     * Tür başına GENEL başlangıç evrak listesi — doğrulanmamış taslak
     * malzemesi, yayın öncesi sahip resmî kaynakla karşılaştırır.
     *
     * @return list<array{ad: string, not?: string}>
     */
    protected function genelEvraklar(string $slug): array
    {
        $kimlik = ['ad' => 'T.C. kimlik kartı veya geçerli pasaport'];
        $randevu = ['ad' => 'Konsolosluk randevusu', 'not' => 'www.konsolosluk.gov.tr üzerinden alınır'];

        return match ($slug) {
            'vekaletname' => [
                $kimlik, $randevu,
                ['ad' => 'Vekil kılınacak kişinin kimlik bilgileri', 'not' => 'Ad-soyad, T.C. kimlik no'],
                ['ad' => 'Vekaletname konusu bilgiler', 'not' => 'Tapu/araç/dava gibi işleme özgü bilgiler'],
                ['ad' => '2 adet biyometrik fotoğraf', 'not' => 'Bazı vekaletname türleri için'],
            ],
            'pasaport' => [
                $kimlik, $randevu,
                ['ad' => 'Mevcut pasaport'],
                ['ad' => '1 adet biyometrik fotoğraf', 'not' => 'Son 6 ay içinde çekilmiş'],
                ['ad' => 'Harç ödemesi', 'not' => 'Güncel tarife için resmî kaynağa bak'],
            ],
            'kimlik-karti' => [
                $kimlik, $randevu,
                ['ad' => '1 adet biyometrik fotoğraf'],
                ['ad' => 'Eski kimlik kartı', 'not' => 'Yenilemede'],
            ],
            'dogum-tescili' => [
                $kimlik, $randevu,
                ['ad' => 'Uluslararası doğum belgesi (Formül A)', 'not' => 'Alman nüfus dairesinden (Standesamt)'],
                ['ad' => 'Anne-babanın kimlik/pasaportları'],
                ['ad' => 'Evlilik cüzdanı', 'not' => 'Evli çiftlerde'],
            ],
            'evlilik-tescili' => [
                $kimlik, $randevu,
                ['ad' => 'Uluslararası evlenme belgesi (Formül B)', 'not' => 'Alman nüfus dairesinden (Standesamt)'],
                ['ad' => 'Eşlerin kimlik/pasaportları'],
            ],
            'olum-ve-cenaze' => [
                ['ad' => 'Vefat edenin kimlik/pasaportu'],
                ['ad' => 'Uluslararası ölüm belgesi (Formül C)'],
                ['ad' => 'Yakınlık gösteren belge'],
            ],
            'askerlik' => [
                $kimlik, $randevu,
                ['ad' => 'Oturma/çalışma izni belgeleri', 'not' => 'Dövizle askerlikte yurt dışında yaşama şartının kanıtı'],
                ['ad' => 'Döviz ödeme dekontu', 'not' => 'Dövizle askerlik başvurusunda'],
            ],
            'mavi-kart' => [
                $kimlik, $randevu,
                ['ad' => 'Vatandaşlıktan çıkma belgesi'],
                ['ad' => 'Alman vatandaşlık belgesi (Einbürgerungsurkunde)'],
                ['ad' => '1 adet biyometrik fotoğraf'],
            ],
            'vatandaslik' => [
                $kimlik, $randevu,
                ['ad' => 'Başvuru türüne göre değişen belgeler', 'not' => 'İzinle çıkma / yeniden kazanma / evlilik yoluyla — resmî kaynağa bak'],
            ],
            'noter-tasdik' => [
                $kimlik, $randevu,
                ['ad' => 'Tasdik edilecek belgenin aslı'],
            ],
            'apostil' => [
                ['ad' => 'Apostil KONSOLOSLUKTAN ALINMAZ', 'not' => 'Alman resmî belgeleri için Almanya\'daki yetkili makam (Regierungspräsidium/mahkeme) apostil verir'],
                ['ad' => 'Apostillenecek belgenin aslı'],
            ],
            'ehliyet' => [
                $kimlik, $randevu,
                ['ad' => 'Türk sürücü belgesi'],
            ],
            'adres-kaydi' => [
                $kimlik,
                ['ad' => 'Almanya adres kaydı (Meldebescheinigung)'],
            ],
            'nufus-kayit-ornegi' => [
                $kimlik,
                ['ad' => 'Başvuru dilekçesi', 'not' => 'Çoğu belge e-Devlet üzerinden de alınabilir'],
            ],
            'tercume-tasdiki' => [
                $kimlik, $randevu,
                ['ad' => 'Belgenin aslı ve tercümesi', 'not' => 'Yeminli tercüman çevirisi'],
            ],
            default => [$kimlik, $randevu],
        };
    }

    protected function genelNot(string $slug): string
    {
        $ortak = 'Bu içerik genel bir başlangıç taslağıdır; bu temsilciliğe özgü farklar '
            .'ve güncel harç tutarları resmî kaynaktan doğrulanmadan yayınlanmamalıdır.';

        return match ($slug) {
            'apostil' => 'Apostil, belgeyi düzenleyen ülkenin makamlarından alınır: Alman belgeleri için '
                .'Almanya\'daki yetkili makamlar, Türk belgeleri için Türkiye\'deki kaymakamlık/valilik. '
                .$ortak,
            'askerlik' => 'Dövizle askerlik şartları ve ücreti yıl içinde değişebilir; başvurudan önce '
                .'mutlaka resmî kaynaktan güncel tutarı kontrol et. '.$ortak,
            default => $ortak,
        };
    }
}
