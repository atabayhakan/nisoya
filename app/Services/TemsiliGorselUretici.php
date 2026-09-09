<?php

namespace App\Services;

use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Services\Ai\FotografUretici;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * İlanlar için TEMSİLÎ görsel üretir — 2026-09-09 itibarıyla TÜM tiplerde
 * (hizmet/ürün/emlak/vasıta).
 *
 * ---------------------------------------------------------------------------
 * ÖNCEKİ SINIR VE NEDEN KALDIRILDI (bilinçli sahip kararı — panelde tartışıldı)
 *
 * Bu sınıf başlangıçta YALNIZ hizmet ilanlarında çalışıyordu: "ürün ilanında
 * fotoğraf bir İDDİADIR — satılan şey budur; oraya üretilmiş bir görsel
 * koymak yanıltıcıdır" gerekçesiyle. Bu gerekçe hâlâ GEÇERLİ ve hâlâ gerçek
 * bir risk — kaldırılmadı, sahip bunu görüp BİLEREK göz göre göre kapsamı
 * genişletmeyi seçti (görselsiz ilanların siteyi kötü göstermesi karşı
 * tartısı ağır bastı). Bu yüzden mitigasyon KOŞULSUZ: `is_representative`
 * damgası ve göründüğü her yerdeki "Temsilî" rozeti hiçbir tipte
 * kaldırılamaz/kapatılamaz — ürün/emlak/vasıtada bu rozet, hizmettekinden
 * daha çok değil, EN AZ o kadar zorunlu. Bkz. istem() — ürün tipi için ekstra
 * "belirli bir nesneyi ANIMSATMA" kısıtı bu yüzden var.
 *
 * ---------------------------------------------------------------------------
 * ÜRETİLEN GÖRSELDE OLMAYACAKLAR (istemde ve testte)
 *
 *   - İNSAN / YÜZ. Üretilmiş bir yüz, gerçek bir işletmenin yanında duran
 *     sahte bir kişidir. "Bu bizim ekibimiz" diye okunur.
 *   - YAZI. Üretilen yazı zaten bozuk çıkar; düzgün çıksa daha kötü olurdu
 *     (fiyat, telefon, vaat).
 *   - LOGO / MARKA. Marka ihlali riski ve sahte bağlantı izlenimi.
 *
 * Sahibin kuralı burada da geçerli: sitedeki her bilgi gerçek. Temsilî görsel
 * bir BİLGİ değil, açıkça etiketlenmiş bir yer tutucudur — kural bu yüzden
 * ancak etiket görünür kaldığı sürece çiğnenmemiş olur.
 */
class TemsiliGorselUretici
{
    public function __construct(private readonly FotografUretici $uretici) {}

    public function isEnabled(): bool
    {
        return (bool) config('ai.features.service_image') && $this->uretici->isConfigured();
    }

    /**
     * Bu ilana temsilî görsel önerilebilir mi?
     *
     * İki koşul da zorunlu:
     *   görseli yok → gerçek fotoğrafı olanın yanına üretilmiş görsel konmaz
     *   özellik açık → anahtar yoksa düğme hiç görünmesin, komut hiç üretmesin
     *
     * Tip kapısı YOK — bkz. sınıf docblock'undaki "önceki sınır" notu.
     */
    public function uygunMu(Listing $listing): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        return $listing->images()->count() === 0;
    }

    /**
     * Görseli üretir, medya boru hattından geçirir ve ilana bağlar.
     *
     * Başarısızlıkta null — çağıran taraf kullanıcıya "üretilemedi" der.
     * İstisna sızdırmaz: bu düğme bir kolaylık, ilanı bozması söz konusu
     * olamaz.
     */
    public function uret(Listing $listing): ?ListingImage
    {
        if (! $this->uygunMu($listing)) {
            return null;
        }

        $bayt = $this->uretici->uret($this->istem($listing));

        if ($bayt === null) {
            return null;
        }

        // Ham baytı geçici dosyaya yaz: medya boru hattı (ImageService) yol
        // bekliyor. Aynı hattan geçmek şart — küçültme, WebP dönüşümü ve
        // varyantlar orada; kendi dosyamızı elle yazsaydık üretilen görsel
        // sitedeki tek "boru hattı dışı" görsel olurdu.
        $gecici = 'temsili/'.Str::uuid()->toString().'.png';
        Storage::disk('local')->put($gecici, $bayt);

        try {
            $sonuc = app(ImageService::class)->storeOptimizedFromPath(
                Storage::disk('local')->path($gecici),
                'listings',
            );
        } catch (\Throwable $e) {
            Log::warning('Temsilî görsel işlenemedi', [
                'listing_id' => $listing->id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        } finally {
            Storage::disk('local')->delete($gecici);
        }

        /*
         * MODERASYONA SOKULMUYOR — bilerek.
         *
         * Yükleme yolundaki AI moderasyonu bir görseli işaretlediğinde ilanı
         * Beklemede'ye alıyor. Sitenin KENDİ ürettiği bir görsel yüzünden
         * satıcının yayındaki ilanının düşmesi, yardım düğmesini cezaya
         * çevirirdi. İstemi biz yazıyoruz, içinde kullanıcı verisi yok ve
         * görsel hiçbir gerçek nesneyi/kişiyi göstermiyor; risk yükleme
         * yolundakiyle aynı değil.
         */
        $gorsel = $listing->images()->create([
            'path_thumb' => $sonuc['thumb'],
            'path_medium' => $sonuc['medium'],
            'path_large' => $sonuc['large'],
            'width' => $sonuc['original_dimensions']['width'] ?? null,
            'height' => $sonuc['original_dimensions']['height'] ?? null,
            'sort_order' => 0,
            'is_cover' => true,
            // ASIL SATIR. Bu işaret düşerse görsel gerçek fotoğraftan
            // ayırt edilemez hâle gelir.
            'is_representative' => true,
        ]);

        try {
            $gorsel->update(['size_bytes' => Storage::disk('public')->size($gorsel->path_large)]);
        } catch (\Throwable) {
            // Boyut okunamazsa görseli kaybetmeye değmez.
        }

        return $gorsel;
    }

    /**
     * İstemi ilanın KENDİ alanlarından kurar; uydurma bilgi eklemez.
     *
     * Modele verilen tek bağlam tip, kategori ve başlık. "Şu mahallede, şu
     * yıldan beri" gibi şeyler bilinmiyor ve sorulmuyor — sorulsaydı model
     * uydurur, uydurduğu da görsele girerdi.
     */
    public function istem(Listing $listing): string
    {
        /*
         * `$listing->category` üzerinden GİTMİYORUZ: `category_id` migration'da
         * nullable (nullOnDelete) ama statik analiz ilişkiyi null-değil sanıyor
         * ve `?->` kullanımını "gereksiz" diye işaretliyor. Analizi susturmak
         * için nullsafe'i sökseydik, kategorisi silinmiş bir ilanda ölümcül
         * hata alırdık. İlişki sorgusu üzerinden okumak hem doğru türü verir
         * hem de gerçeği yansıtır.
         */
        $kategoriAdi = $listing->category()->value('name');
        $kategori = is_string($kategoriAdi) && $kategoriAdi !== '' ? $kategoriAdi : $this->genelKategoriAdi($listing->type);
        $baslik = Str::limit((string) $listing->title, 120, '');

        return implode("\n", [
            $this->tipAcilisi($listing->type),
            '',
            $this->tipEtiketi($listing->type).': '.$kategori,
            'İlan başlığı: '.$baslik,
            '',
            'ZORUNLU KURALLAR:',
            '- Görselde İNSAN veya YÜZ OLMASIN.',
            '- Görselde HİÇBİR YAZI, harf, rakam veya filigran OLMASIN.',
            '- Görselde HİÇBİR LOGO veya marka işareti OLMASIN.',
            '- Belirli bir işletmeyi, dükkânı, tabelayı ya da BELİRLİ BİR NESNEYİ',
            '  göstermeye ÇALIŞMA; jenerik, soyutlaşmış bir sahne/doku olsun —',
            '  hiç kimse bu görseli "satılan/kiralanan gerçek şey budur" diye',
            '  okumamalı.',
            '- Fotoğrafımsı, sade, iyi ışıklı, yatay (16:9) bir sahne.',
            '',
            $this->tipKapanisi($listing->type),
        ]);
    }

    private function tipEtiketi(ListingType $tip): string
    {
        return match ($tip) {
            ListingType::Hizmet => 'Hizmet türü',
            ListingType::Urun => 'Ürün kategorisi',
            ListingType::Emlak => 'Emlak türü',
            ListingType::Vasita => 'Araç türü',
        };
    }

    private function genelKategoriAdi(ListingType $tip): string
    {
        return match ($tip) {
            ListingType::Hizmet => 'genel hizmet',
            ListingType::Urun => 'genel ürün',
            ListingType::Emlak => 'genel emlak',
            ListingType::Vasita => 'genel araç',
        };
    }

    private function tipAcilisi(ListingType $tip): string
    {
        return match ($tip) {
            ListingType::Hizmet => 'Bir hizmet ilanı için TEMSİLÎ (jenerik) bir kapak görseli üret.',
            ListingType::Urun => 'Bir ürün ilanı için TEMSİLÎ (jenerik, SOYUT) bir kapak görseli üret. '
                .'Bu görsel ürünün KENDİSİ DEĞİL — ilan sahibi henüz gerçek fotoğraf eklemedi, '
                .'sen yalnız kategoriyi çağrıştıran nötr bir doku/arka plan üretiyorsun.',
            ListingType::Emlak => 'Bir emlak ilanı için TEMSİLÎ (jenerik) bir kapak görseli üret. '
                .'Belirli bir bina/mekân DEĞİL, emlak türünü çağrıştıran soyut/jenerik bir sahne.',
            ListingType::Vasita => 'Bir vasıta ilanı için TEMSİLÎ (jenerik) bir kapak görseli üret. '
                .'Belirli bir marka/model DEĞİL, araç türünü çağrıştıran soyut/jenerik bir sahne.',
        };
    }

    private function tipKapanisi(ListingType $tip): string
    {
        $ortak = 'Bu görsel gerçek bir fotoğraf değildir; ';

        return $ortak.match ($tip) {
            ListingType::Hizmet => 'hizmeti çağrıştıran nötr bir arka plandır.',
            ListingType::Urun => 'SATILAN GERÇEK ÜRÜNÜ GÖSTERMEZ — yalnız kategoriyi çağrıştıran soyut bir doku/renk paletidir. '
                .'Belirli bir ürün şekli/silueti çizmeye çalışma.',
            ListingType::Emlak => 'ilan konusu gerçek mülkü göstermez, emlak türünü çağrıştıran nötr bir sahnedir.',
            ListingType::Vasita => 'ilan konusu gerçek aracı göstermez, araç türünü çağrıştıran nötr bir sahnedir.',
        };
    }
}
