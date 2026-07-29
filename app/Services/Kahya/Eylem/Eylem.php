<?php

namespace App\Services\Kahya\Eylem;

use App\Enums\EylemRiski;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Kâhya'nın yapabileceği TEK bir işin tanımı.
 *
 * ---------------------------------------------------------------------------
 * NEDEN SINIF, NEDEN SERBEST YAZMA DEĞİL
 *
 * Bir dil modeli için "Japonya'yı ülkelere ekle" ile "bütün ilanları sil" aynı
 * şekilli cümledir. Güvenliği cümleyi doğru anlamak kuramaz — cümleyi yanlış
 * anlama ihtimali asla sıfırlanmaz. Güvenliği YAPILABİLECEK İŞLERİN LİSTESİ
 * kurar: modelin elinde SQL yok, model yok, kaydetme yok; yalnızca bu
 * katalogdan bir ad ve onun parametreleri var.
 *
 * Model "ilanları sil" derse ve katalogda öyle bir eylem yoksa, olan tek şey
 * "böyle bir işi yapamam" cevabıdır.
 *
 * ---------------------------------------------------------------------------
 * HER EYLEM GERİ ALINABİLİR OLMALI
 *
 * `uygula()` işi yapar VE kendisini geri alacak izi döndürür. Geri alınamayan
 * bir iş bu katalogda yer alamaz — {@see EylemKatalogu} bunu ayrıca sınar.
 * Bu kural, demo veri makinesindeki defterle aynı düşüncenin ürünü: bir işi
 * yapmak onu geri almaktan hep kolaydır, o yüzden geri alma yolu işi yaparken
 * yazılır, sonradan değil.
 *
 * ---------------------------------------------------------------------------
 * `aciklama()` VE `ornekler()` YAPAY ZEKÂNIN DERS KİTABIDIR
 *
 * "Kâhya paneli öğrensin" isteğinin gerçek karşılığı bu iki metottur. Kâhya
 * paneli gezerek öğrenmiyor — ona ne yapabileceğini biz SÖYLÜYORUZ. Bu daha
 * sıkıcı ama çok daha güvenilir: keşfederek öğrenen bir ajan, keşfettiğini
 * yanlış anlayabilir; bildirilmiş bir yetenek listesi yanlış anlaşılamaz.
 *
 * Bu yüzden açıklamalar Türkçe, somut ve sahibin kullandığı kelimelerle
 * yazılır.
 */
abstract class Eylem
{
    /** Katalogdaki benzersiz ad — yapay zekâ bu adı seçer. Ör. `ulke-ekle`. */
    abstract public function ad(): string;

    /** Panelde görünen kısa başlık. */
    abstract public function baslik(): string;

    /** Yapay zekânın okuyacağı açıklama: NE yapar, NE YAPMAZ, ne zaman uygun. */
    abstract public function aciklama(): string;

    /**
     * Parametre şeması — yapay zekâya "hangi alanları doldurmalıyım" der.
     *
     * @return array<string, string> alan => insan-okur açıklama
     */
    abstract public function sema(): array;

    /**
     * Laravel doğrulama kuralları. ŞEMAYA GÜVENİLMEZ: yapay zekânın
     * doldurduğu değerler sunucuda ayrıca doğrulanır.
     *
     * @return array<string, mixed>
     */
    abstract public function kurallar(): array;

    abstract public function risk(): EylemRiski;

    /**
     * "Şu olacak" cümlesi — sahibin okuyup onaylayacağı metin.
     * Yüksek riskli eylemlerde gösterilen tek şey budur, bu yüzden BELİRSİZ
     * OLAMAZ: hangi kayıt, hangi değer, kaç tane.
     *
     * @param  array<string, mixed>  $p
     */
    abstract public function onizleme(array $p): string;

    /**
     * İşi yapar.
     *
     * @param  array<string, mixed>  $p  doğrulanmış parametreler
     * @return array{sonuc: string, geri_alma: array<string, mixed>}
     */
    abstract public function uygula(array $p): array;

    /**
     * İşi geri alır.
     *
     * @param  array<string, mixed>  $iz  `uygula()`'nın döndürdüğü geri_alma
     * @return string insan-okur sonuç
     */
    abstract public function geriAl(array $iz): string;

    /**
     * Sahibin bu eylemi tetikleyebileceği örnek cümleler — yapay zekânın
     * eşleştirme yaparken en çok işine yarayan şey.
     *
     * @return list<string>
     */
    public function ornekler(): array
    {
        return [];
    }

    /**
     * Parametreleri doğrular ve temizlenmiş hâlini döndürür.
     *
     * @param  array<string, mixed>  $p
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    final public function dogrula(array $p): array
    {
        return Validator::make($p, $this->kurallar())->validate();
    }
}
