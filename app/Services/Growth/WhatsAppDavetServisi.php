<?php

namespace App\Services\Growth;

use App\Models\Listing;
use App\Models\OutreachTarget;

/**
 * Türk esnafına WhatsApp üzerinden tek tıkla doğrudan vitrin sahiplenme
 * daveti göndermek için URL ve mesaj şablonu üretir.
 */
class WhatsAppDavetServisi
{
    /**
     * Telefon numarasını WhatsApp uluslararası formatına (sadece rakamlar) dönüştürür.
     */
    public function temizleTelefon(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        // Boşluk, parantez, tire, nokta vb. temizle
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (blank($digits)) {
            return null;
        }

        // Çift sıfır ile başlıyorsa kaldır (0031 -> 31)
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Samimi, profesyonel ve dönüşüm odaklı Türkçe WhatsApp davet mesajı.
     */
    public function mesaj(string $isletmeAdi, string $sehir, string $listingUrl, string $claimUrl): string
    {
        $signature = (string) (config('growth.whatsapp_signature') ?: 'Hakan · nisoya.com');

        return "Selamlar,\n\n"
            ."Nisoya'da ({$sehir}) bölgenizdeki Türkçe konuşan topluluk için {$isletmeAdi} adına özel bir tanıtım vitrini hazırladık:\n"
            ."{$listingUrl}\n\n"
            ."Vitrininizi inceleyip 15 saniyede ücretsiz olarak sahiplenebilirsiniz:\n"
            ."{$claimUrl}\n\n"
            ."Nisoya'da komisyon veya üyelik ücreti yoktur, platformumuz tamamen ücretsizdir.\n\n"
            ."Bol kazançlar ve iyi çalışmalar dileriz!\n"
            .$signature;
    }

    /**
     * WhatsApp doğrudan sohbet bağlantısı (wa.me) üretir.
     */
    public function url(?string $phone, string $isletmeAdi, string $sehir, string $listingUrl, string $claimUrl): string
    {
        $clean = $this->temizleTelefon($phone);
        $text = rawurlencode($this->mesaj($isletmeAdi, $sehir, $listingUrl, $claimUrl));

        return $clean ? "https://wa.me/{$clean}?text={$text}" : "https://wa.me/?text={$text}";
    }

    /**
     * OutreachTarget kaydından doğrudan WhatsApp davet bağlantısı üretir.
     */
    public function adayIcinUrl(OutreachTarget $aday): string
    {
        $listing = $aday->listing;
        $isletmeAdi = $aday->name;
        $sehir = $aday->city ?? 'Avrupa';

        $listingUrl = $listing
            ? route('listings.show', [$listing->id, $listing->slug])
            : url('/ilanlar');

        $claimUrl = ($listing && $listing->isClaimable())
            ? url('/sahiplen/'.$listing->claim_token)
            : url('/panel/ilan/yeni');

        $phone = null;
        if ($listing) {
            $phone = $listing->claim_phone;
        }
        if (blank($phone) && isset($aday->detection_signals['phone'])) {
            $phone = (string) $aday->detection_signals['phone'];
        }

        return $this->url($phone, $isletmeAdi, $sehir, $listingUrl, $claimUrl);
    }

    /**
     * Listing kaydından doğrudan WhatsApp davet bağlantısı üretir.
     */
    public function listingIcinUrl(Listing $listing): string
    {
        $listingUrl = route('listings.show', [$listing->id, $listing->slug]);
        $claimUrl = $listing->isClaimable()
            ? url('/sahiplen/'.$listing->claim_token)
            : $listingUrl;

        return $this->url(
            $listing->claim_phone,
            $listing->title,
            $listing->city ?? 'Avrupa',
            $listingUrl,
            $claimUrl
        );
    }
}
