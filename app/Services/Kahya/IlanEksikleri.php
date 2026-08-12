<?php

namespace App\Services\Kahya;

use App\Models\Listing;
use App\Services\IlanCevirmeni;
use App\Services\TemsiliGorselUretici;

/**
 * Bir ilanın gözle görülür eksikleri — satıcıya söylenecek şeyler.
 *
 * ---------------------------------------------------------------------------
 * NEDEN YAPAY ZEKÂ YOK
 *
 * Sekiz maddelik AI planının son adımı bu ama tespit BİLEREK deterministik.
 * "Görseli var mı", "açıklama kaç karakter", "şehir yazılmış mı" soruları
 * kesin cevabı olan sorular; modele sormak parayı, gecikmeyi ve uydurma
 * riskini bedavaya satın almak olurdu. Zaten bildiğimiz bir şeyi söylemesi
 * için jeton harcamıyoruz.
 *
 * Yapay zekâ ÇÖZÜM tarafında: eksik görselde temsilî görsel önerisi
 * (TemsiliGorselUretici), eksik yerel dilde çeviri önerisi (IlanCevirmeni).
 *
 * ---------------------------------------------------------------------------
 * NE ÖLÇÜLMEZ
 *
 * Fiyatın boş olması EKSİK DEĞİLDİR — sitede "Görüşülür" geçerli bir cevap
 * ve pek çok hizmette doğrusu da o. Boş bırakılması bilinçli bir tercih
 * olabilecek hiçbir alan buraya girmez; aksi hâlde bildirim, satıcının
 * kararını hata gibi gösterir.
 */
class IlanEksikleri
{
    /** Bu uzunluğun altındaki açıklama alıcıya soru sordurur. */
    public const KISA_ACIKLAMA = 80;

    public function __construct(
        private readonly TemsiliGorselUretici $gorselUretici,
        private readonly IlanCevirmeni $cevirmen,
    ) {}

    /**
     * İlanın eksiklerini döndürür; eksik yoksa boş dizi.
     *
     * @return list<array{anahtar: string, metin: string}>
     */
    public function tara(Listing $listing): array
    {
        $eksikler = [];

        if ($listing->images()->count() === 0) {
            $eksikler[] = [
                'anahtar' => 'gorsel',
                // Temsilî görsel YALNIZ hizmet ilanlarında önerilebilir;
                // ürün fotoğrafı bir iddiadır (bkz. TemsiliGorselUretici).
                'metin' => $this->gorselUretici->uygunMu($listing)
                    ? 'Görselin yok. Kendi fotoğrafın en iyisi; yoksa ilan düzenleme sayfasından tek tıkla temsilî bir görsel oluşturabilirsin.'
                    : 'Görselin yok. Görselli ilanlar belirgin biçimde daha çok tıklanıyor.',
            ];
        }

        if (mb_strlen(trim((string) $listing->description)) < self::KISA_ACIKLAMA) {
            $eksikler[] = [
                'anahtar' => 'aciklama',
                'metin' => 'Açıklaman çok kısa. Ne yaptığını, nasıl çalıştığını ve kimlere uygun olduğunu birkaç cümleyle anlatmak soruları azaltır.',
            ];
        }

        if (trim((string) $listing->city) === '') {
            $eksikler[] = [
                'anahtar' => 'sehir',
                'metin' => 'Şehir yazmamışsın. Arayanların çoğu şehirle filtreliyor; şehirsiz ilan o listelerde hiç çıkmıyor.',
            ];
        }

        // Yerel dil önerisi YALNIZ çevrilebilir ülkelerde ve çevirisi
        // olmayan ilanlarda — dili tanımsız ülkede öneri boş vaat olurdu.
        if ($this->cevirmen->uygunMu($listing) && $this->cevirmen->guncelCeviri($listing) === null) {
            $dil = $this->cevirmen->dilAdi((string) $this->cevirmen->hedefDil($listing));
            $eksikler[] = [
                'anahtar' => 'ceviri',
                'metin' => $dil.' çevirin yok. İlan düzenleme sayfasından tek tıkla ekleyebilirsin; çevrendeki Türkçe bilmeyen müşteriler de seni bulur.',
            ];
        }

        return $eksikler;
    }
}
