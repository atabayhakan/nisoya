<?php

namespace App\Services;

/**
 * MP4/QuickTime videolarındaki KONUM metadata'sını YERİNDE siler.
 *
 * Fotoğrafların EXIF'i WebP'ye dönüştürülürken zaten atılıyor (ImageService);
 * videolar ise bilinçli olarak dönüştürülmeden saklanıyor — bu da telefon
 * kayıtlarındaki GPS koordinatlarını herkese açık dosyada bırakıyordu
 * (açık işler envanteri: "MP4'lerde GPS temizlenmiyor"). Misafirin düğün
 * videosu, çekildiği evin koordinatını taşımamalı.
 *
 * Silinen alanlar (hepsi moov ağacında):
 *   - ©xyz  — QuickTime/Android konum dizesi (udta içinde, trak altında da olabilir)
 *   - loci  — 3GPP konum kutusu (ad + sabit-noktalı enlem/boylam/yükseklik)
 *   - meta→keys/ilst — Apple'ın com.apple.quicktime.location.* anahtarlarının değerleri
 *
 * YÖNTEM — YERİNDE ÜZERİNE YAZMA, YENİDEN PAKETLEME DEĞİL: ffmpeg remux'u
 * bilinen çözüm ama sunucuya ikili bağımlılık ekler ve test ortamlarında
 * garanti bulunmaz. Bunun yerine hedef kutuların payload'ı AYNI UZUNLUKTA
 * sıfır baytla ezilir. Dosya boyutu ve kutu ofsetleri değişmediği için
 * stco/co64 parça ofset tabloları bozulMAZ — oynatma etkilenmez.
 *
 * GÜVENLİK SÖZLEŞMESİ: bu sınıf bir dosyayı ASLA bozmaz. Yapı beklenmedikse
 * (bozuk kutu boyu, sınır aşımı) o alt ağaç olduğu gibi atlanır; yazılan her
 * aralık, sınırları doğrulanmış bir kutunun İÇİNDE kalır. MP4 olmayan bir
 * dosyada (WebM dahil) ilk kutu başlığı doğrulanamaz ve dosyaya hiç
 * dokunulmaz. WebM tarafında telefonların konum yazdığı yerleşik bir alan
 * pratikte yok — bilinçli kapsam dışı.
 */
class VideoKonumTemizleyici
{
    /** Konteyner olarak İÇİNE girilen kutular (meta ayrı ele alınır). */
    private const KONTEYNERLER = ['moov', 'udta', 'trak'];

    /** Konum verisi bulunup silindiyse true; parse edilemeyen dosyada false (dokunulmaz). */
    public function temizle(string $dosyaYolu): bool
    {
        $boyut = @filesize($dosyaYolu);

        if (! is_int($boyut) || $boyut < 16) {
            return false;
        }

        $f = @fopen($dosyaYolu, 'r+b');

        if ($f === false) {
            return false;
        }

        try {
            $silindi = false;

            foreach ($this->kutular($f, 0, $boyut) as [$tip, $icOfset, $icBoyut]) {
                if ($tip === 'moov') {
                    $silindi = $this->konteynerTemizle($f, $icOfset, $icOfset + $icBoyut) || $silindi;
                }
            }

            return $silindi;
        } finally {
            fclose($f);
        }
    }

    /**
     * [$bas, $son) aralığındaki kutuları sırayla verir: [tip, payloadOfset, payloadBoyut].
     * İlk tutarsızlıkta durur — bozuk yapıda hiçbir şey "tahmin edilmez".
     *
     * @param  resource  $f
     * @return iterable<int, array{string, int, int}>
     */
    private function kutular($f, int $bas, int $son): iterable
    {
        $ofs = $bas;

        while ($ofs + 8 <= $son) {
            fseek($f, $ofs);
            $baslik = fread($f, 8);

            if (! is_string($baslik) || strlen($baslik) < 8) {
                return;
            }

            $boy = (int) unpack('N', substr($baslik, 0, 4))[1];
            $tip = substr($baslik, 4, 4);
            $baslikBoyu = 8;

            if ($boy === 1) {
                // 64-bit "largesize" — başlıktan sonra 8 bayt daha.
                $ek = fread($f, 8);

                if (! is_string($ek) || strlen($ek) < 8) {
                    return;
                }

                $boy = (int) unpack('J', $ek)[1];
                $baslikBoyu = 16;
            } elseif ($boy === 0) {
                // "Dosya sonuna kadar" — yalnız son kutuda geçerli.
                $boy = $son - $ofs;
            }

            if ($boy < $baslikBoyu || $ofs + $boy > $son) {
                return;
            }

            yield [$tip, $ofs + $baslikBoyu, $boy - $baslikBoyu];

            $ofs += $boy;
        }
    }

    /** @param resource $f */
    private function konteynerTemizle($f, int $bas, int $son): bool
    {
        $silindi = false;

        foreach ($this->kutular($f, $bas, $son) as [$tip, $icOfset, $icBoyut]) {
            if ($tip === "\xA9xyz" || $tip === 'loci') {
                $this->payloadEz($f, $icOfset, $icBoyut);
                $silindi = true;
            } elseif (in_array($tip, self::KONTEYNERLER, true)) {
                $silindi = $this->konteynerTemizle($f, $icOfset, $icOfset + $icBoyut) || $silindi;
            } elseif ($tip === 'meta') {
                $silindi = $this->metaTemizle($f, $icOfset, $icBoyut) || $silindi;
            }
        }

        return $silindi;
    }

    /**
     * Apple meta kutusu: keys'te adı "location" içeren anahtarların 1-tabanlı
     * sırası bulunur, ilst'te aynı sıra numarasını taşıyan girdilerin değeri
     * ezilir. Anahtar ADI dosyada kalır — değeri sıfırlanmış bir anahtar
     * yalnızca "burada konum vardı" der, konumu söyleyemez.
     *
     * @param  resource  $f
     */
    private function metaTemizle($f, int $bas, int $boyut): bool
    {
        // meta bir fullbox'tır: çocuklardan önce 4 bayt version/flags gelir.
        // (QuickTime her zaman koyar; koymayan yazıcılar için +0 da denenir.)
        foreach ([4, 0] as $atla) {
            if ($boyut <= $atla) {
                continue;
            }

            $konumSiralari = [];
            $ilst = null;

            foreach ($this->kutular($f, $bas + $atla, $bas + $boyut) as [$tip, $icOfset, $icBoyut]) {
                if ($tip === 'keys') {
                    $konumSiralari = $this->konumAnahtarSiralari($f, $icOfset, $icBoyut);
                } elseif ($tip === 'ilst') {
                    $ilst = [$icOfset, $icBoyut];
                }
            }

            if ($konumSiralari !== [] && $ilst !== null) {
                return $this->ilstDegerleriniEz($f, $ilst[0], $ilst[1], $konumSiralari);
            }

            if ($ilst !== null || $konumSiralari !== []) {
                // Yapı bu ofsetle çözüldü ama konum anahtarı yok — bitti.
                return false;
            }
        }

        return false;
    }

    /**
     * keys payload'ı: [4B verflags][4B adet] + her girdi [4B boy][4B 'mdta'][ad].
     *
     * @param  resource  $f
     * @return list<int> adı "location" içeren anahtarların 1-tabanlı sıraları
     */
    private function konumAnahtarSiralari($f, int $bas, int $boyut): array
    {
        if ($boyut < 8) {
            return [];
        }

        fseek($f, $bas);
        $veri = fread($f, min($boyut, 65536));

        if (! is_string($veri) || strlen($veri) < 8) {
            return [];
        }

        $adet = (int) unpack('N', substr($veri, 4, 4))[1];
        $siralar = [];
        $ofs = 8;

        for ($i = 1; $i <= $adet && $ofs + 8 <= strlen($veri); $i++) {
            $girdiBoyu = (int) unpack('N', substr($veri, $ofs, 4))[1];

            if ($girdiBoyu < 8 || $ofs + $girdiBoyu > strlen($veri)) {
                break;
            }

            $ad = substr($veri, $ofs + 8, $girdiBoyu - 8);

            if (stripos($ad, 'location') !== false) {
                $siralar[] = $i;
            }

            $ofs += $girdiBoyu;
        }

        return $siralar;
    }

    /**
     * ilst çocukları anahtar SIRA numarasıyla adlandırılır (4 baytlık
     * big-endian sayı); eşleşen girdilerin tüm payload'ı ezilir.
     *
     * @param  resource  $f
     * @param  list<int>  $siralar
     */
    private function ilstDegerleriniEz($f, int $bas, int $boyut, array $siralar): bool
    {
        $silindi = false;

        foreach ($this->kutular($f, $bas, $bas + $boyut) as [$tip, $icOfset, $icBoyut]) {
            $sira = (int) unpack('N', $tip)[1];

            if (in_array($sira, $siralar, true)) {
                $this->payloadEz($f, $icOfset, $icBoyut);
                $silindi = true;
            }
        }

        return $silindi;
    }

    /** @param resource $f */
    private function payloadEz($f, int $ofs, int $boy): void
    {
        if ($boy <= 0) {
            return;
        }

        fseek($f, $ofs);
        fwrite($f, str_repeat("\0", $boy));
    }
}
