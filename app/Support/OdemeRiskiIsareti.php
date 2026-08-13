<?php

namespace App\Support;

/**
 * Sohbette PARA KAYBETTİREN kalıpları sezer.
 *
 * ---------------------------------------------------------------------------
 * NEDEN AYRI BİR SEZGİ — ÖLÇÜMLE BULUNDU (2026-08-13)
 *
 * [[PlatformDisiIsaret]] zaten "WhatsApp'a geçelim" ve telefon/IBAN paylaşımını
 * yakalıyor. Canlıda gerçek mesajlarla ölçünce şu çıktı:
 *
 *   IBAN paylaşımı .................. UYARIYOR   (oysa burada NORMAL)
 *   telefon paylaşımı ............... UYARIYOR   (oysa burada NORMAL)
 *   Western Union / MoneyGram ....... sessiz
 *   hediye kartı .................... sessiz
 *   kripto .......................... sessiz
 *   PayPal "arkadaş ve aile" ........ sessiz
 *   doğrulama kodu isteme ........... sessiz
 *   "yurt dışındayım, parayı yolla" . sessiz
 *
 * Yani uyarı dürüst çoğunlukta çalışıyor, paranın gerçekten kaybedildiği
 * kalıplarda susuyordu. Nisoya ödemeye aracılık etmediği için IBAN/telefon
 * paylaşmak satıcının TEK yolu; asıl tehlike parayı GERİ ALINAMAZ bir
 * kanaldan göndertmek.
 *
 * ---------------------------------------------------------------------------
 * YÜKSEK İSABET, DAR AĞ
 *
 * Uyarı yorgunluğu bu özelliğin en büyük düşmanı: her mesajda uyarı çıkarsa
 * kimse okumaz ve gerçek uyarı da görünmez olur. Bu yüzden yalnız yerel bir
 * alışverişte NEREDEYSE HİÇ meşru karşılığı olmayan kalıplar var.
 *
 * "Kapora" TEK BAŞINA burada YOK — kiralamada sıradan bir uygulama.
 * Yalnız "göremezsin, önce gönder" çerçevesiyle birlikte anlamlı.
 */
final class OdemeRiskiIsareti
{
    /**
     * Tek desenle yeten kurallar.
     *
     * @var array<string, string>
     */
    private const TEKIL = [
        // Geri alınamaz para transferi kanalları.
        'geri_alinamaz_kanal' => '/western\s*union|money\s*gram|\bria\s+money|hediye\s*kart|gift\s*card|google\s*play\s*kart|steam\s*kart|itunes\s*kart/iu',

        // PayPal "Arkadaş/Aile": alıcı koruması çalışmaz.
        'paypal_arkadas' => '/arkada[sş]\s*(?:ve|\/|,|\s)\s*aile|friends?\s*(?:and|&|\/)\s*family|\bf\s*&\s*f\b|arkada[sş]a\s+g[öo]nder/iu',

        // Hesap ele geçirme: kod/şifre/kart bilgisi isteme.
        'hesap_ele_gecirme' => '/do[ğg]rulama\s*kod|onay\s*kod|sms\s*kod|gelen\s*kod(?:u)?\s*(?:bana|ilet|yaz|g[öo]nder)|tek\s*kullan[ıi]ml[ıi]k\s*[şs]ifre|\bcvv\b|kart\s*numaras|[şs]ifreni\s*(?:payla|s[öo]yle|yaz|g[öo]nder)/iu',
    ];

    /**
     * İKİ KOŞUL BİRDEN gerektiren kurallar — tek başına masum olabilecek
     * kelimeleri yanlış alarma çevirmemek için.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const CIFTLI = [
        // Kripto: "kripto danışmanlığı" meşru bir hizmet olabilir; tehlike
        // kriptoyla ÖDEME istenmesi.
        'kripto' => [
            '/bitcoin|\bbtc\b|\busdt\b|tether|ethereum|\beth\b|kripto|binance|c[üu]zdan\s*adres/iu',
            '/[öo]de(?:me)?|g[öo]nder|yat[ıi]r|transfer|havale/iu',
        ],

        // "Göremezsin, önce parayı yolla" — diaspora kiralama dolandırıcılığının
        // klasik kalıbı.
        'gormeden_odeme' => [
            '/yurt\s*d[ıi][şs][ıi]nday[ıi]m|[şs]ehir\s*d[ıi][şs][ıi]nday[ıi]m|ba[şs]ka\s*[şs]ehirdeyim|g[öo]remezsin|g[öo]rmeden|gelemem/iu',
            '/[öo]nce\s*(?:para|[öo]deme|kapora)|para(?:y[ıi])?\s*(?:yolla|g[öo]nder|yat[ıi]r)|kargo(?:yla|\s*ile)?\s*(?:g[öo]nderir|yollar)|anahtar[ıi].*kargo/iu',
        ],
    ];

    /** @var array<string, string> */
    private const METINLER = [
        'geri_alinamaz_kanal' => '⚠️ Western Union, MoneyGram ve hediye kartıyla gönderilen para GERİ ALINAMAZ. Dolandırıcılığın en sık yolu budur — bu kanalları kullanma.',
        'paypal_arkadas' => '⚠️ PayPal\'da “Arkadaş/Aile” ile gönderilen parada alıcı koruması ÇALIŞMAZ ve geri alınamaz. “Mal ve Hizmetler” seçeneğini kullan.',
        'hesap_ele_gecirme' => '⚠️ Doğrulama kodu, kart numarası ya da şifre isteniyor. Bunları kimseyle paylaşma — hesabın ele geçirilebilir. Nisoya da senden istemez.',
        'kripto' => '⚠️ Kripto ile yapılan ödeme geri alınamaz ve takip edilemez. Yerel bir alışverişte kripto istenmesi ciddi bir uyarı işaretidir.',
        'gormeden_odeme' => '⚠️ “Göremezsin, önce parayı gönder” klasik bir dolandırıcılık kalıbı. Görmeden, teslim almadan ödeme yapma.',
    ];

    /** Eşleşen kuralın anahtarı; yoksa null. */
    public static function tespit(?string $metin): ?string
    {
        $metin = trim((string) $metin);

        if ($metin === '') {
            return null;
        }

        foreach (self::TEKIL as $anahtar => $kalip) {
            if (preg_match($kalip, $metin) === 1) {
                return $anahtar;
            }
        }

        foreach (self::CIFTLI as $anahtar => [$birinci, $ikinci]) {
            if (preg_match($birinci, $metin) === 1 && preg_match($ikinci, $metin) === 1) {
                return $anahtar;
            }
        }

        return null;
    }

    public static function metin(string $anahtar): string
    {
        return self::METINLER[$anahtar] ?? '';
    }
}
