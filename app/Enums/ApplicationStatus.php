<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** İş başvurusu durumu (işveren tarafından güncellenir). */
enum ApplicationStatus: string implements HasColor, HasLabel
{
    case Gonderildi = 'gonderildi';
    case Incelendi = 'incelendi';
    case Gorusme = 'gorusme';
    case Kabul = 'kabul';
    case Red = 'red';

    public function getLabel(): string
    {
        return match ($this) {
            self::Gonderildi => 'Gönderildi',
            self::Incelendi => 'İncelendi',
            self::Gorusme => 'Görüşmeye çağrıldı',
            self::Kabul => 'Kabul edildi',
            self::Red => 'Olumsuz',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Gonderildi => 'gray',
            self::Incelendi => 'info',
            self::Gorusme => 'warning',
            self::Kabul => 'success',
            self::Red => 'danger',
        };
    }

    /**
     * Bu duruma geçmek adaya e-posta gönderilmesini gerektirir mi?
     *
     * Kanban panosu durum değiştirmeyi ÇOK ucuzlaştırıyor (kartı sürükle,
     * bitti). "Gönderildi" ve "İncelendi" işverenin kendi iç triyaj adımları:
     * aday için haber değeri yok, buna karşılık her elemeyi tek tek gezen bir
     * işveren onlarca gereksiz e-posta üretebilir. Adayın hayatını gerçekten
     * etkileyen üç durum bildirilir: görüşmeye çağrıldı, kabul, olumsuz.
     *
     * Bu "görünmez sihir" olmamalı — pano sütun başlıkları hangi sütunun
     * sessiz olduğunu zil/üstü çizili zil ikonuyla açıkça gösterir.
     */
    public function bildirimGerektirir(): bool
    {
        return match ($this) {
            self::Gonderildi, self::Incelendi => false,
            self::Gorusme, self::Kabul, self::Red => true,
        };
    }
}
