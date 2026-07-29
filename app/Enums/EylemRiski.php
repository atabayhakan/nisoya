<?php

namespace App\Enums;

/**
 * Bir Kâhya eyleminin risk sınıfı — onay kapısının anahtarı.
 *
 * SINIRI "ne kadar zor geri alınır" ÇİZER, "ne kadar önemli" DEĞİL.
 * Ülke eklemek önemlidir ama tek satırdır ve tek tıkla geri alınır; bir ilanı
 * reddetmek de tek satırdır ama sahibine bildirim gider ve o bildirim geri
 * alınamaz. İkincisi yüksek risktir.
 *
 * Ölçüt üç soru: kullanıcıya bir şey GİDİYOR mu, sitenin YÜZÜ değişiyor mu,
 * geri alınca eski hâl GERÇEKTEN geri geliyor mu?
 */
enum EylemRiski: string
{
    /** Geri alması ucuz, dışarıya hiçbir şey gitmiyor. Kâhya doğrudan yapar. */
    case Dusuk = 'dusuk';

    /** Kullanıcıya yansıyor ya da geri alınsa bile izi kalıyor. Önce sorulur. */
    case Yuksek = 'yuksek';

    public function etiket(): string
    {
        return match ($this) {
            self::Dusuk => 'Düşük risk',
            self::Yuksek => 'Yüksek risk',
        };
    }

    public function onayGerekir(): bool
    {
        return $this === self::Yuksek;
    }
}
