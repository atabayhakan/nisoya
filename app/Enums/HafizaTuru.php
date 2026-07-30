<?php

namespace App\Enums;

/**
 * Bir Kâhya hafıza kaydının türü (F1 — Kâhya 2.0 tasarımı §2.3).
 *
 * Tür, kaydın YÖNERGEDEKİ AĞIRLIĞINI belirler: kurallar ve gerçekler her
 * sohbete girer (davranışı/kararı doğrudan yönlendirir), dersler ve notlar
 * yer kalırsa girer, gerisi tablo-sorgula ile aranır.
 */
enum HafizaTuru: string
{
    /** Davranış talimatı: "SEO metnini yayınlamadan önce hep göster." */
    case Kural = 'kural';

    /** Site/iş bilgisi: "hedef kitle önce Avrupa, Körfez değil." */
    case Gercek = 'gercek';

    /** Düzeltmelerden çıkan neden-sonuç — F5'te kahya-cikarimi kaynağıyla dolar. */
    case Ders = 'ders';

    /** Serbest not. */
    case Not = 'not';

    public function etiket(): string
    {
        return match ($this) {
            self::Kural => 'Kural',
            self::Gercek => 'Gerçek',
            self::Ders => 'Ders',
            self::Not => 'Not',
        };
    }

    /** Yönergeye her sohbette giren türler (öncelikli çekirdek). */
    public function cekirdekMi(): bool
    {
        return $this === self::Kural || $this === self::Gercek;
    }
}
