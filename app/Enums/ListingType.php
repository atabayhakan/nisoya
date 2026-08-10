<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ListingType: string implements HasLabel
{
    case Hizmet = 'hizmet';
    case Urun = 'urun';
    case Emlak = 'emlak';
    case Vasita = 'vasita';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hizmet => 'Hizmet',
            self::Urun => 'Ürün',
            self::Emlak => 'Emlak',
            self::Vasita => 'Vasıta',
        };
    }

    /**
     * İlan detayındaki mesaj kutusunun üstünde çıkan hazır açılış cümleleri.
     *
     * Gerekçe: iletişim kutusu boş bir `required` textarea ve ilk teması
     * kuracak kişi "ne yazsam" diye takılıyor. Çipler yazmayı bitirmez,
     * sadece BAŞLATIR — metin kutusuna düşer, gönderilmeden önce
     * düzenlenebilir. Otomatik gönderim YOK; satıcıya giden her mesajı
     * kullanıcı görüp onaylar.
     *
     * @return list<string>
     */
    public function hizliMesajlar(): array
    {
        return match ($this) {
            self::Hizmet => [
                'Merhaba, bu hizmet hâlâ veriliyor mu?',
                'İlgileniyorum, biraz detay verebilir misiniz?',
                'Ne zaman müsait olursunuz?',
            ],
            self::Urun => [
                'Merhaba, hâlâ satılık mı?',
                'İlgileniyorum, biraz detay verebilir misiniz?',
                'Ürünün durumu hakkında bilgi alabilir miyim?',
            ],
            self::Emlak => [
                'Merhaba, hâlâ müsait mi?',
                'İlgileniyorum, biraz detay verebilir misiniz?',
                'Yerinde görmek için gelebilir miyim?',
            ],
            self::Vasita => [
                'Merhaba, hâlâ müsait mi?',
                'İlgileniyorum, biraz detay verebilir misiniz?',
                'Aracı görmek için gelebilir miyim?',
            ],
        };
    }
}
