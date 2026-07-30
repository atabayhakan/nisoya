<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Services\Kahya\Eylem\Eylem;
use App\Support\Settings;

/**
 * Sitenin varsayılan SEO başlığını ve açıklamasını BİRLİKTE günceller —
 * metinleri sohbetteki model yazar, sahip onaylar.
 *
 * ---------------------------------------------------------------------------
 * "SEO'YU KÂHYA OTOMATİK YAPSIN" BURADA NASIL ÇALIŞIR
 *
 * Metin üretimi bu sınıfın İÇİNDE değil: sohbet modeli, yönergedeki
 * "Site kimliği" bölümünden (site adı, kategoriler, mevcut metinler)
 * beslenerek başlık ve açıklamayı PARAMETRE olarak doldurur. Böylece:
 *
 *   1. sahip onay ekranında TAM OLARAK yayınlanacak metni görür — eylem
 *      uygulandıktan sonra üretilen bir sürprizi değil;
 *   2. eylemin içinde ikinci bir yapay zekâ çağrısı gerekmez.
 *
 * ---------------------------------------------------------------------------
 * AYAR-DOLDUR VARKEN NEDEN AYRI EYLEM
 *
 * ayar-doldur tek anahtar yazar ve sahibin SÖYLEDİĞİ metni taşır. Burada iki
 * alan birlikte tazelenir ve metni model YAZAR — okunmamış makine metninin
 * sitenin arama sonuçlarındaki yüzü olması onaysız olmaz; bu yüzden risk
 * yüksek, ayar-doldur ise düşük kalır.
 */
class SeoDoldur extends Eylem
{
    public function ad(): string
    {
        return 'seo-doldur';
    }

    public function baslik(): string
    {
        return 'SEO başlık & açıklama yaz';
    }

    public function aciklama(): string
    {
        return 'Sitenin varsayılan SEO başlığını ve açıklamasını birlikte günceller. '
            .'Metinleri SEN yazarsın: "Site kimliği" bölümündeki site adını, kategorileri ve '
            .'pazaryerinin amacını (yurtdışındaki Türkler için ücretsiz Türkçe pazaryeri) kullan. '
            .'Başlık en çok 60, açıklama en çok 155 karakter olsun; ikisi de doğal Türkçe, '
            .'anahtar kelime yığını değil. Sahip onaylamadan yayına girmez. '
            .'Yalnız başlığı ya da yalnız açıklamayı değiştirmek için ayar-doldur kullan.';
    }

    public function sema(): array
    {
        return [
            'baslik' => 'Yeni SEO başlığı (Türkçe, ~60 karakter). Tarayıcı sekmesi ve Google sonucu başlığı.',
            'aciklama' => 'Yeni SEO açıklaması (Türkçe, ~155 karakter). Google sonucunda başlığın altındaki metin.',
        ];
    }

    public function kurallar(): array
    {
        return [
            // Tavanlar SeoAyarlari ekranındaki alan sınırlarıyla aynı: panel
            // elle neyi kabul ediyorsa Kâhya da onu yazabilmeli, fazlasını değil.
            'baslik' => ['required', 'string', 'min:10', 'max:70'],
            'aciklama' => ['required', 'string', 'min:40', 'max:200'],
        ];
    }

    public function risk(): EylemRiski
    {
        // Geri alması ucuz ama sitenin arama motorlarındaki YÜZÜ değişiyor ve
        // metin sahibin değil modelin kaleminden çıkıyor — önce okutulur.
        return EylemRiski::Yuksek;
    }

    public function onizleme(array $p): string
    {
        $eskiBaslik = (string) Settings::get('seo.default_title', '');
        $eskiAciklama = (string) Settings::get('seo.default_description', '');

        return 'SEO metinleri güncellenecek. '
            ."Başlık: \"{$eskiBaslik}\" → \"{$p['baslik']}\". "
            ."Açıklama: \"{$eskiAciklama}\" → \"{$p['aciklama']}\".";
    }

    public function uygula(array $p): array
    {
        /*
         * İZ, HAM TABLO DEĞERLERİNİ SAKLAR (AyarDoldur ile aynı gerekçe):
         * varsayılana düşmüş değer izlenirse, geri alma config metnini tabloya
         * kopyalar ve alan config güncellemelerini takip etmez olurdu.
         */
        $tablo = Settings::all();

        $iz = [
            'baslik' => $tablo['seo.default_title'] ?? null,
            'aciklama' => $tablo['seo.default_description'] ?? null,
        ];

        Settings::setMany([
            'seo.default_title' => (string) $p['baslik'],
            'seo.default_description' => (string) $p['aciklama'],
        ]);

        return [
            'sonuc' => "SEO başlığı ve açıklaması güncellendi. Başlık: \"{$p['baslik']}\"",
            'geri_alma' => $iz,
        ];
    }

    public function geriAl(array $iz): string
    {
        // "Hiç yoktu" boş dizeyle temsil edilir; Settings::get boş değeri yok
        // sayıp varsayılana düşer — sitenin gösterdiği metin birebir geri gelir.
        Settings::setMany([
            'seo.default_title' => (string) ($iz['baslik'] ?? ''),
            'seo.default_description' => (string) ($iz['aciklama'] ?? ''),
        ]);

        return 'SEO başlığı ve açıklaması eski hâline döndürüldü.';
    }

    public function ornekler(): array
    {
        return [
            'SEO ayarlarını sen doldur',
            'sitenin arama motoru başlığını ve açıklamasını yeniden yaz',
            'SEO metinlerini tazele',
        ];
    }
}
