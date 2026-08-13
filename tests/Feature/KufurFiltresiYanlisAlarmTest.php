<?php

namespace Tests\Feature;

use App\Services\ProfanityFilterService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Küfür filtresi DÜRÜST KULLANICIYI engellemesin.
 *
 * ---------------------------------------------------------------------------
 * CANLIDA BULUNDU (2026-08-13)
 *
 * Mesajlaşma akışını denerken filtreyi gerçek cümlelerle ölçtüm:
 *
 *   "Köpek bakıcısı arıyorum, haftada iki gün" -> ENGELLENDİ
 *   "Çocuklar için top satıyorum"               -> ENGELLENDİ
 *
 * Filtre yalnız sohbette değil İLAN OLUŞTURURKEN de çalışıyor
 * (ListingRequest), yani "köpek bakıcılığı yapıyorum" diye ilan VERİLEMİYORDU.
 * Site bakım hizmetleri pazaryeri; bu bir hizmet kategorisini fiilen kapatmak
 * demekti.
 *
 * ---------------------------------------------------------------------------
 * BU TEST İKİ YÖNLÜ
 *
 * Yalnız "masum cümle geçiyor mu" diye sorsaydım, filtreyi tamamen boşaltmak
 * da testi geçerdi. Ağır küfrün HÂLÂ engellendiği de ölçülüyor.
 */
class KufurFiltresiYanlisAlarmTest extends TestCase
{
    private function filtre(): ProfanityFilterService
    {
        return app(ProfanityFilterService::class);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function masumCumleler(): array
    {
        return [
            ['Köpek bakıcısı arıyorum, haftada iki gün'],
            ['Köpek maması ve tasma satıyorum'],
            ['Çocuklar için top satıyorum, az kullanılmış'],
            ['Sitede top model kıyafet var'],
            // Kısa kökler yalnız tek başına eşleşmeli — bunlar hep geçmeliydi,
            // burada da sabitleniyor ki eşleştirme mantığı bozulmasın.
            ['Toplam 50 euro tutuyor'],
            ['Ocak ayında müsaitim'],
            ['Tamam anlaştık, yarın görüşürüz'],
            ['Dolabı taşımak için yardım lazım'],
            ['Amcam da gelecek'],
        ];
    }

    #[DataProvider('masumCumleler')]
    public function test_masum_cumle_engellenmiyor(string $cumle): void
    {
        $this->assertSame([], $this->filtre()->findProfanities($cumle),
            "Dürüst bir cümle küfür sayıldı: {$cumle}");
    }

    public function test_agir_kufur_hala_engelleniyor(): void
    {
        /*
         * TERS YÖN. Bu olmasaydı "listeyi tamamen boşalt" çözümü de yukarıdaki
         * testleri geçerdi.
         */
        foreach (['orospu cocugu', 'siktir git', 'amk ya', 'yarrak kafa'] as $cumle) {
            $this->assertNotSame([], $this->filtre()->findProfanities($cumle),
                "Ağır küfür geçmiş: {$cumle}");
        }
    }

    public function test_ilan_metninde_de_ayni_kural(): void
    {
        // Filtre ListingRequest'te de çalışıyor; asıl zarar oradaydı.
        $this->assertNull($this->filtre()->validateText(
            'Evde köpek bakıcılığı yapıyorum, gezdirme ve mama dahil.'
        ));
    }
}
