<?php

namespace App\Support;

/**
 * Bir mesajın konuşmayı PLATFORM DIŞINA taşıma teklifi içerip içermediğini
 * sezer (açık işler envanteri: "platform-dışı çekme uyarısı").
 *
 * Neden: dolandırıcılığın ilk adımı neredeyse hep aynı — "WhatsApp'tan
 * devam edelim". Yazışma platformda kaldığı sürece kayıt kullanıcının
 * elindedir (itiraz, şikâyet, moderasyon); dışarı çıktığı an hepsi biter.
 * Rehber sayfaları bunu zaten öğütlüyor; bu sınıf öğüdü tam da gerektiği
 * ANDA, ilgili mesajın altında görünür kılar.
 *
 * ENGELLEMEZ, YALNIZ İŞARETLER: numara paylaşmak meşru da olabilir
 * (buluşma ayarlamak gibi). Bu yüzden mesaj bloklanmaz, alıcıya yumuşak
 * bir dikkat notu düşülür. Kalıplar bilinçli MUHAFAZAKÂR — yanlış alarm
 * yorgunluğu, uyarının kendisini görünmez yapar:
 *   - WhatsApp/Telegram/Instagram adları ve yaygın yazımları, wa.me/t.me
 *   - "wp" (Türkçe günlük dilde WhatsApp) ve "numaram"
 *   - telefon görünümlü rakam dizisi (ayraçlı ≥10 hane; NOKTA ayraç
 *     sayılmaz — "01.02.2026 15:30" gibi tarih-saatler tetiklememeli)
 */
final class PlatformDisiIsaret
{
    private const KALIPLAR = [
        '/whats\s?app|watsapp?|vatsap|wa\.me\//iu',
        '/telegram|t\.me\//iu',
        '/instagram/iu',
        '/\bwp\b/iu',
        '/\bnumaram\b/iu',
    ];

    public static function tespit(?string $metin): bool
    {
        $metin = (string) $metin;

        if (trim($metin) === '') {
            return false;
        }

        foreach (self::KALIPLAR as $kalip) {
            if (preg_match($kalip, $metin) === 1) {
                return true;
            }
        }

        // Telefon görünümlü dizi: rakamlar arasında en çok birer boşluk/
        // tire/parantez, toplam en az 10 hane. (IBAN da yakalanır — "görmeden
        // ödeme yapma" hatırlatması orada da yerindedir.)
        return preg_match('/\+?\d(?:[\s\-\(\)]?\d){9,}/', $metin) === 1;
    }
}
