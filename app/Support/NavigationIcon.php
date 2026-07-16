<?php

namespace App\Support;

/**
 * Header mega menüsünde (bkz. App\Models\NavigationLink) kullanılabilecek
 * ikonların beyaz listesi. Admin panelden serbest metin yerine bu listeden
 * seçim yapılır — geçersiz bir heroicon adı <x-dynamic-component> üzerinden
 * tüm site genelinde header'ı kıran bir hataya yol açabileceği için (bkz.
 * App\Support\CategoryIcon ile aynı savunma deseni) render sırasında da
 * bu listeye karşı doğrulanır.
 */
class NavigationIcon
{
    public const OPTIONS = [
        'shopping-bag' => 'Çanta (ilanlar)',
        'user-group' => 'Topluluk / yetenek',
        'briefcase' => 'İş / kariyer',
        'home' => 'Ev / emlak',
        'truck' => 'Araç / nakliye',
        'gift' => 'Hediye / etkinlik',
        'map' => 'Harita',
        'question-mark-circle' => 'Yardım',
        'squares-2x2' => 'Genel / kategori',
    ];

    public static function heroicon(?string $key): string
    {
        return $key && array_key_exists($key, self::OPTIONS) ? $key : 'squares-2x2';
    }
}
