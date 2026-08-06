<?php

namespace App\Support;

/**
 * Türkçe para biçimi.
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR (2026-08-06)
 *
 * İlan detayında fiyat `number_format($price, 2)` ile basılıyordu; PHP'nin
 * varsayılan ayraçları İNGİLİZCEdir, yani Türkçe bir sitede "60,000.00 KGS"
 * çıkıyordu. Türkçe okuyucu için binlik ayraç NOKTA, ondalık ayraç VİRGÜL —
 * "60,000.00" en iyi ihtimalle yabancı, en kötü ihtimalle iki anlamlıdır.
 *
 * İkinci sorun tutarsızlıktı: aynı fiyat detay sayfasında "60,000.00",
 * kenar çubuğundaki "Benzer ilanlar" kartında "60,000" görünüyordu. Aynı
 * sayının iki farklı yazımı, sayfanın derlenmiş değil yamalanmış olduğunu
 * söyler.
 *
 * İlginç olan şu: bu depo Türkçe biçimi ZATEN BİLİYORDU — kilometre
 * `number_format($km, 0, ',', '.')` ile doğru basılıyordu. Eksik olan kural
 * değil, kuralın tek bir yerde durmasıydı.
 *
 * ---------------------------------------------------------------------------
 * KURUŞ YALNIZ VARSA
 *
 * "60.000,00" değil "60.000". Tam sayı bir fiyata iki sıfır eklemek bilgi
 * vermez, yalnız satırı uzatır; kuruş gerçekten varsa (1.250,50) gösterilir.
 */
class Para
{
    /**
     * @param  int|float|string|null  $tutar  Eloquent `decimal:2` cast'i string döndürür, o yüzden string de kabul edilir.
     * @return string|null Tutar yoksa null — çağıran taraf "Görüşülür" gibi kendi metnini basar.
     */
    public static function bicimle(int|float|string|null $tutar): ?string
    {
        if ($tutar === null || $tutar === '') {
            return null;
        }

        $sayi = (float) $tutar;

        /*
         * Kuruş kontrolü float karşılaştırmasıyla değil TAM SAYI kuruşla
         * yapılır: 0.1 + 0.2 !== 0.3 tuzağı burada "60.000,00" gibi yanlış
         * bir çıktıya dönüşürdü.
         */
        $kurus = (int) round($sayi * 100);

        return number_format($sayi, $kurus % 100 === 0 ? 0 : 2, ',', '.');
    }
}
