<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Temsilcilik;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Temsilcilik sayfasının boş hâli: "hazırlanıyor" mı, "yönlendirme" mi?
 *
 * Boş hâl tek bir cümleyle iki farklı durumu anlatıyordu:
 *   (a) Biz henüz yazmadık — geçici, "hazırlanıyor" doğru.
 *   (b) Temsilcilik işlem bilgisini HİÇ yayınlamıyor — kalıcı gerçek,
 *       "hazırlanıyor" demek YALAN.
 *
 * (b) Bişkek'te ölçüldü: bilgi notu indeksi tamamen boş. Ziyaretçiye
 * "hazırlanıyor" demek onu beklemeye iter; oysa yapması gereken merkezî
 * kaynağa gitmek.
 */
class RehberYonlendirmeKartiTest extends TestCase
{
    use RefreshDatabase;

    private function temsilcilik(?string $not): Temsilcilik
    {
        Country::firstOrCreate(['code' => 'KG'], ['name_tr' => 'Kırgızistan', 'emoji' => '🇰🇬', 'is_active' => true]);

        return Temsilcilik::create([
            'country_code' => 'KG',
            'ad' => 'Bişkek Büyükelçiliği',
            'slug' => 'biskek-buyukelciligi',
            'tur' => Temsilcilik::TUR_BUYUKELCILIK,
            'sehir' => 'Bişkek',
            'resmi_url' => 'https://biskek-be.mfa.gov.tr',
            'yonlendirme_notu' => $not,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function ekran(Temsilcilik $t): TestResponse
    {
        return $this->get(route('rehber.temsilcilik', ['kg', $t->slug]));
    }

    public function test_yonlendirme_notu_varken_hazirlaniyor_yazmaz(): void
    {
        $t = $this->temsilcilik('Bu temsilcilik işlem bilgilerini kendi sitesinde yayınlamıyor.');

        $yanit = $this->ekran($t);

        $yanit->assertOk();
        $yanit->assertSee('kendi sitesinde yayınlamıyor');
        $yanit->assertDontSee('hazırlanıyor');
    }

    public function test_yonlendirme_notu_varken_merkezi_portala_bag_verir(): void
    {
        $t = $this->temsilcilik('Kaynak yok.');

        // Ziyaretçinin yapması gereken şey bu: beklemek değil, portala gitmek.
        $this->ekran($t)->assertSee('konsolosluk.gov.tr', false);
    }

    public function test_notu_olmayan_temsilcilikte_hazirlaniyor_yazar(): void
    {
        // İçeriğini henüz yazmadığımız temsilcilikte "hazırlanıyor" DOĞRU cümle;
        // yönlendirme kartı oraya sızmamalı.
        $t = $this->temsilcilik(null);

        $yanit = $this->ekran($t);

        $yanit->assertSee('hazırlanıyor');
        $yanit->assertDontSee('yayınlamıyor');
    }
}
