<?php

namespace App\Support;

/**
 * Bir metnin vize/hukuk ya da para konularına değinip değinmediğini sezer
 * (Kâhya Telegram — tasarım kararı: "serbest sohbet + hassas konularda bekçi").
 *
 * NEDEN VAR: Kâhya'nın Telegram grubunda serbest cevap vermesi onaylandı,
 * ama vize/deport/mahkeme gibi ciddi bir soruya modelin kendi tahmininden
 * yanlış bir "hukuki tavsiye" üretmesi gerçek zarar verebilir (bkz. Rusya
 * grubunda gözlemlenen gerçek bir deport vakası). Bu sınıf CEVABI
 * ENGELLEMEZ — yalnız işaretler: cevaba bir çekince notu eklenir ve satır
 * panelde "İnceleme" olarak görünür.
 *
 * Kalıp DETERMİNİSTİK ve UCUZ (regex) — TurkishBusinessDetector'daki
 * "önce ucuz ön-eleme" ilkesiyle aynı: her Telegram mesajı için ayrı bir
 * LLM çağrısı yapmadan sınıflandırma.
 */
final class HassasKonuBekcisi
{
    private const KATEGORILER = [
        'vize_hukuk' => [
            'vize', 'viza', 'deport', 'sınır dışı', 'mahkeme', 'dava', 'avukat',
            'iltica', 'oturma izni', 'ikamet', 'sözleşme', 'kontrat', 'vks',
            'çalışma izni', 'pasaport', 'gözaltı', 'ceza',
        ],
        'para' => [
            'para', 'ödeme', 'ödemedi', 'borç', 'kredi', 'iban', 'dolandır',
            'maaş', 'kapora', 'depozito', 'komisyon',
        ],
    ];

    /** Metinde hassas bir kategori yakalanırsa adını döndürür, yoksa null. */
    public function tespit(?string $metin): ?string
    {
        $metin = mb_strtolower((string) $metin, 'UTF-8');

        if (trim($metin) === '') {
            return null;
        }

        foreach (self::KATEGORILER as $kategori => $kelimeler) {
            foreach ($kelimeler as $kelime) {
                if (str_contains($metin, $kelime)) {
                    return $kategori;
                }
            }
        }

        return null;
    }

    /** Kategoriye göre cevabın sonuna eklenecek çekince notu. */
    public function uyariNotu(string $kategori): string
    {
        return match ($kategori) {
            'vize_hukuk' => "\n\n⚠️ Bu konuda kesin bilgi veremem — durumun resmî ve kişiye özel, mutlaka ilgili konsolosluk/göçmenlik bürosuna ya da bir avukata danış.",
            'para' => "\n\n⚠️ Para/ödeme konusunda dikkatli ol — bir anlaşma yapmadan önce karşı tarafı doğrula, görmeden ödeme yapma.",
            default => '',
        };
    }
}
