<?php

namespace App\Support;

/**
 * Bir sohbet mesajının altına düşecek dikkat notu — TEK KAYNAK.
 *
 * ---------------------------------------------------------------------------
 * NEDEN BU SINIF VAR
 *
 * Uyarı metni ÜÇ yerde birden yazılıydı: sunucu render'ı (bubble.blade.php),
 * JS render'ı (show.blade.php → dikkatNode) ve dolaylı olarak testler. İki
 * dosyanın başında da "birebir aynı kalmalı" uyarısı vardı — yani kırılganlık
 * biliniyordu ama çözülmemişti. İkinci bir uyarı türü eklerken bu üçe altı
 * olurdu.
 *
 * Artık metin YALNIZ burada. İki render de kendisine verilen dizeyi basıyor,
 * karar vermiyor. Yeni bir uyarı türü eklemek tek dosyaya dokunmak demek.
 *
 * ---------------------------------------------------------------------------
 * SIRA ÖNEMLİ
 *
 * Ödeme riski, platform-dışı çekme uyarısını EZER. İkisi birden basılsaydı
 * kullanıcı iki amber şerit görür ve ikisini birden okumazdı; üstelik para
 * kaybettiren asıl kalıp ikinci sıraya düşerdi. Bir mesajda tek uyarı.
 */
final class SohbetUyarisi
{
    private const PLATFORM_DISI = '⚠️ Konuşmayı platform dışına taşıma teklifi olabilir — yazışma burada kalırsa kaydı elinde olur; görmeden ödeme yapma.';

    /**
     * @return array{tur: string, metin: string}|null
     */
    public static function bul(?string $metin): ?array
    {
        if ($anahtar = OdemeRiskiIsareti::tespit($metin)) {
            return ['tur' => $anahtar, 'metin' => OdemeRiskiIsareti::metin($anahtar)];
        }

        if (PlatformDisiIsaret::tespit($metin)) {
            return ['tur' => 'platform_disi', 'metin' => self::PLATFORM_DISI];
        }

        return null;
    }
}
