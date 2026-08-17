<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Services\Kahya\Eylem\Eylem;
use App\Support\SaltOkunurBekci;
use App\Support\Settings;
use Closure;

/**
 * Site ayarlarından BİRİNİ doldurur ya da günceller.
 *
 * ---------------------------------------------------------------------------
 * SIR KORUMASI: ALLOW-LIST, DENY-LIST DEĞİL
 *
 * site_settings tablosunda mail.password, ai.api_anahtari,
 * growth.google_places_api_key gibi SIRLAR da duruyor ve bir yapay zekânın
 * bunlara yazabilmesi kabul edilemez. "Sır anahtarlarını engelle" diyen bir
 * deny-list burada işe yaramaz — {@see SaltOkunurBekci} ile aynı
 * gerekçe: yasaklıları saymak hep eksik kalır (yarın eklenecek sır anahtarını
 * bugünden bilemezsin), izinlileri saymak ise bilinmeyeni tanımadığı için
 * reddeder. Güvenli taraf budur.
 *
 * Bir anahtar ancak ÜÇ koşulu birden sağlarsa yazılabilir:
 *   1. config/site_defaults.php içinde TANIMLI — uydurma anahtar tabloya
 *      hayalet satır olarak giremez;
 *   2. IZINLI_ONEKLER'den biriyle başlıyor: seo. / duyuru. / iletisim. /
 *      nabiz. / bagis. — yani sahibin panelde elle yazdığı düz metin alanları.
 *      mail.*, ai.*, modul.* ve tarayıcıya kod enjekte edebilen
 *      header/footer.ozel_kod BİLEREK dışarıda;
 *   3. adında sır çağrıştıran parça yok (YASAK_PARCALAR). İzinli beş grupta
 *      bugün böyle bir alan yok; bu üçüncü kemer, izinli bir gruba yarın
 *      yanlışlıkla eklenecek bir sır alanına karşı.
 */
class AyarDoldur extends Eylem
{
    /**
     * Kâhya'nın doldurabileceği ayar grupları. Liste DAR tutuldu; genişletmeden
     * önce grubun kod alanı (type: code) ya da sır içermediğinden emin ol.
     */
    private const IZINLI_ONEKLER = ['seo.', 'duyuru.', 'iletisim.', 'nabiz.', 'bagis.'];

    /** İzinli bir grupta bile adında bunlardan biri geçen anahtar reddedilir. */
    private const YASAK_PARCALAR = ['password', 'parola', 'secret', 'gizli', 'api', 'anahtar', 'key', 'token'];

    public function ad(): string
    {
        return 'ayar-doldur';
    }

    public function baslik(): string
    {
        return 'Ayar doldur';
    }

    public function aciklama(): string
    {
        return 'Site ayarlarından BİRİNİ günceller: SEO başlık/açıklama, duyuru bandı, '
            .'iletişim sayfası bilgileri, Nabız hedefi ve bağış metinleri. Tek seferde '
            .'tek anahtar yazar. Posta (SMTP), yapay zekâ, modül ve görünüm ayarlarına '
            .'YAZAMAZ — bu istekler için sahibi yönetim panelindeki ilgili sayfaya yönlendir.';
    }

    public function sema(): array
    {
        return [
            'anahtar' => 'Güncellenecek ayarın tam anahtarı. Yalnız seo. / duyuru. / iletisim. / '
                .'nabiz. / bagis. ile başlayanlar yazılabilir. Ör. seo.default_title, '
                .'duyuru.metin, iletisim.eposta, nabiz.hedef_sayi, bagis.baslik.',
            'deger' => 'Yazılacak yeni değer, düz metin. Ör. "Nisoya — Ne İş Olursa Yaparız".',
        ];
    }

    public function kurallar(): array
    {
        return [
            'anahtar' => ['required', 'string', 'max:100', function (string $attribute, mixed $value, Closure $fail): void {
                $anahtar = (string) $value;

                // Yapay zekâ anahtar uydurabilir; tanımsız anahtar tabloya
                // yazılırsa hiçbir panel ekranında görünmeyen hayalet satır olur.
                if (! array_key_exists($anahtar, config('site_defaults.fields', []))) {
                    $fail("'{$anahtar}' diye tanımlı bir site ayarı yok; hiçbir şey yazılmadı.");

                    return;
                }

                $izinli = array_filter(self::IZINLI_ONEKLER, fn (string $onek): bool => str_starts_with($anahtar, $onek));

                if ($izinli === []) {
                    $fail("'{$anahtar}' Kâhya'nın yazabileceği bir alan değil; yalnız "
                        .implode(' ', self::IZINLI_ONEKLER).' ile başlayan ayarlar doldurulabilir. '
                        .'Diğerlerini sahibi yönetim panelinden değiştirmeli.');

                    return;
                }

                foreach (self::YASAK_PARCALAR as $parca) {
                    if (str_contains(mb_strtolower($anahtar), $parca)) {
                        $fail("'{$anahtar}' adında '{$parca}' geçiyor — sır olabilecek ayarlara Kâhya asla yazmaz.");

                        return;
                    }
                }
            }],
            'deger' => ['required', 'string', 'max:2000'],
        ];
    }

    public function risk(): EylemRiski
    {
        // Tek anahtar değişir, önceki değer izde durur, kimseye bildirim
        // gitmez; geri alma sitede görüneni birebir eski hâline getirir.
        return EylemRiski::Dusuk;
    }

    public function onizleme(array $p): string
    {
        $anahtar = (string) $p['anahtar'];
        $etiket = config('site_defaults.fields')[$anahtar]['label'] ?? $anahtar;

        // Settings::get bilerek: tablo boşsa sitede görünen şey config
        // varsayılanıdır ve sahibin "önceki değer" diye bileceği metin odur.
        $onceki = Settings::get($anahtar);
        $oncekiMetin = ($onceki === null || $onceki === '') ? 'boş' : "\"{$onceki}\"";

        return "\"{$etiket}\" ({$anahtar}) güncellenecek: önceki değer {$oncekiMetin}, "
            ."yeni değer \"{$p['deger']}\".";
    }

    public function uygula(array $p): array
    {
        $anahtar = (string) $p['anahtar'];

        /*
         * İZ, HAM TABLO DEĞERİNİ SAKLAR — Settings::get'in varsayılana düşmüş
         * hâlini DEĞİL. Varsayılan izlenseydi, hiç doldurulmamış bir alanın
         * geri alması config'teki metni tabloya kopyalar ve alan bir daha
         * config güncellemelerini takip etmezdi. (null = satır hiç yoktu.)
         */
        $onceki = Settings::all()[$anahtar] ?? null;

        Settings::setMany([$anahtar => (string) $p['deger']]);

        return [
            'sonuc' => "{$anahtar} güncellendi.",
            'geri_alma' => ['anahtar' => $anahtar, 'onceki' => $onceki],
        ];
    }

    public function geriAl(array $iz): string
    {
        $anahtar = (string) ($iz['anahtar'] ?? '');

        if ($anahtar === '') {
            return 'İzde anahtar yok; geri alınacak bir şey bulunamadı.';
        }

        $onceki = $iz['onceki'] ?? null;

        /*
         * Satırı silen bir API yok; "hiç yoktu" durumu boş dizeyle temsil
         * edilir. Settings::get boş değeri zaten yok sayıp config
         * varsayılanına düştüğü için sitenin gösterdiği metin birebir eski
         * hâline döner — tablo satırının kendisi değil, davranışı geri gelir.
         */
        Settings::setMany([$anahtar => (string) ($onceki ?? '')]);

        if ($onceki === null || $onceki === '') {
            return "{$anahtar} boşaltıldı; site bu alan için yeniden varsayılan metni gösteriyor.";
        }

        return "{$anahtar} eski değerine döndürüldü: \"{$onceki}\"";
    }

    public function ornekler(): array
    {
        return [
            'SEO başlığını "Nisoya — Gurbetçinin Pazaryeri" yap',
            'duyuru bandının metnini "Bayram haftası boyunca yeni ilanlar öne çıkar" olarak değiştir',
            'iletişim sayfasındaki telefonu +49 170 000 00 00 yap',
            'bu ayki Nabız hedefini 50 yeni üye yap',
        ];
    }
}
