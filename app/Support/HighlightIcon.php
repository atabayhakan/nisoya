<?php

namespace App\Support;

/**
 * Ana sayfa vurgu kartlarında (bkz. App\Models\HomeHighlight) kullanılabilecek
 * ikonların beyaz listesi — aynı gerekçe ve desen App\Support\NavigationIcon
 * ile birebir aynı: admin panelden serbest metin yerine bu listeden seçim
 * yapılır, geçersiz bir heroicon adı render sırasında da bu listeye karşı
 * doğrulanıp güvenli bir varsayılana düşürülür.
 */
class HighlightIcon
{
    public const OPTIONS = [
        'language' => 'Dil / çeviri',
        'shield-check' => 'Güven / güvenlik',
        'sparkles' => 'Parıltı / öne çıkan',
        'globe-alt' => 'Dünya / global',
        'heart' => 'Kalp',
        'star' => 'Yıldız',
        'check-badge' => 'Onay rozeti',
        'gift' => 'Hediye / kampanya',
    ];

    public static function heroicon(?string $key): string
    {
        return $key && array_key_exists($key, self::OPTIONS) ? $key : 'sparkles';
    }
}
