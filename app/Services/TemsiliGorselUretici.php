<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Services\Ai\FotografUretici;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Hizmet ilanları için TEMSİLÎ görsel üretir.
 *
 * ---------------------------------------------------------------------------
 * NEDEN YALNIZ HİZMET — BU SINIFIN VAR OLUŞ SEBEBİ
 *
 * Ürün ilanında fotoğraf bir İDDİADIR: "satılan şey budur". Oraya üretilmiş
 * bir görsel koymak, alıcıya olmayan bir nesneyi göstermektir; en hafif
 * tabirle yanıltıcı, ağırı dolandırıcılık. Bu yüzden ürün/emlak/vasıta
 * ilanlarında bu özellik AÇILAMAZ — bayrakla değil, koddaki tip kapısıyla.
 *
 * Hizmette ortada gösterilecek tekil bir nesne yoktur. "Ev temizliği"nin
 * fotoğrafı diye bir şey yok; oradaki görsel dekordur, iddia değil. Yine de
 * dekor olduğu YAZILMAK zorunda — bkz. is_representative sütunu ve göründüğü
 * her yerdeki "Temsilî" rozeti.
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
     * Üç koşul da zorunlu ve üçü de ayrı bir şeyi koruyor:
     *   tip=hizmet  → yukarıdaki iddia/dekor ayrımı
     *   görseli yok → gerçek fotoğrafı olanın yanına üretilmiş görsel konmaz
     *   özellik açık → anahtar yoksa düğme hiç görünmesin
     */
    public function uygunMu(Listing $listing): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        if ($listing->type->value !== 'hizmet') {
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
     * Modele verilen tek bağlam kategori ve başlık. "Şu mahallede, şu
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
        $kategori = is_string($kategoriAdi) && $kategoriAdi !== '' ? $kategoriAdi : 'genel hizmet';
        $baslik = Str::limit((string) $listing->title, 120, '');

        return implode("\n", [
            'Bir hizmet ilanı için TEMSİLÎ (jenerik) bir kapak görseli üret.',
            '',
            'Hizmet türü: '.$kategori,
            'İlan başlığı: '.$baslik,
            '',
            'ZORUNLU KURALLAR:',
            '- Görselde İNSAN veya YÜZ OLMASIN.',
            '- Görselde HİÇBİR YAZI, harf, rakam veya filigran OLMASIN.',
            '- Görselde HİÇBİR LOGO veya marka işareti OLMASIN.',
            '- Belirli bir işletmeyi, dükkânı ya da tabelayı gösterme; jenerik bir sahne olsun.',
            '- Fotoğrafımsı, sade, iyi ışıklı, yatay (16:9) bir sahne.',
            '',
            'Bu görsel gerçek bir ürünün fotoğrafı değildir; hizmeti çağrıştıran',
            'nötr bir arka plandır.',
        ]);
    }
}
