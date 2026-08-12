<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Support\AcilNumaralar;
use Database\Seeders\CountrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Acil yardım menüsü.
 *
 * ---------------------------------------------------------------------------
 * BU GÜVENLİK VERİSİ
 *
 * Buradaki numaralar gerçekten acil durumdaki birinin arayacağı numaralar.
 * En önemli test `test_aktif_her_ulkenin_acil_numarasi_var`: yeni bir ülke
 * açıldığında acil numarası UNUTULAMAZ, çünkü test kırmızıya döner. Aksi
 * hâlde o ülkedeki kullanıcı, acil durumda numara yerine boş bir kutu görür
 * ve bunu kimse fark etmez.
 *
 * ---------------------------------------------------------------------------
 * 2026-08-12 — İKİ KURAL DEĞİŞTİ (sahibin telefonda gördüğü hatadan sonra)
 *
 *  1. HAYATÎ KATMAN PAZARYERİNE BAĞLANAMAZ. Bileşenin tamamı
 *     `@if ($categories->isNotEmpty())` ile sarılıydı: panelden "Acil Yardım"
 *     alt kategorileri pasife çekilse 112 ve konsolosluk numarası da siteden
 *     kaybolurdu. Kapı kaldırıldı.
 *
 *  2. BOŞ VAAT VERİLMEZ. 3. katman (ilan bağlantıları) canlıda HER ülkede
 *     "0 ilan bulundu" döndürüyordu; acil durumdaki kişi üç büyük düğme
 *     görüp üçünde de boş sayfaya düşüyordu. Artık yalnız gerçekten ilanı
 *     olan kategori basılır.
 */
class AcilMenusuTest extends TestCase
{
    use RefreshDatabase;

    /** Acil kategori ağacı — ilan YOK (3. katman kapalı kalır). */
    private function acilKategorisi(): Category
    {
        $ana = Category::query()->create([
            'name' => 'Acil Yardım', 'slug' => Category::EMERGENCY_SLUG,
            'type' => 'hizmet', 'is_active' => true, 'sort_order' => 0,
        ]);

        $cocuk = Category::query()->create([
            'parent_id' => $ana->id, 'name' => 'Nöbetçi doktor', 'slug' => 'nobetci-doktor',
            'type' => 'hizmet', 'is_active' => true, 'sort_order' => 1,
        ]);

        Cache::forget(Category::EMERGENCY_CACHE_KEY);

        return $cocuk;
    }

    private function ilanEkle(Category $kategori, bool $demo = false): Listing
    {
        $this->seed(CountrySeeder::class);

        return Listing::factory()->create([
            'category_id' => $kategori->id,
            'status' => 'aktif',
            'is_demo' => $demo,
        ]);
    }

    public function test_aktif_her_ulkenin_acil_numarasi_var(): void
    {
        /*
         * ASIL BEKÇİ. Ülke listesi büyüdükçe (Körfez, Türk dünyası…) acil
         * numarası eklemeyi unutmak çok kolay; bu test onu imkânsız kılar.
         */
        $harita = AcilNumaralar::harita();
        $eksik = [];

        foreach (Country::query()->where('is_active', true)->pluck('code') as $kod) {
            if (! isset($harita[$kod]) || blank($harita[$kod]['genel'] ?? null)) {
                $eksik[] = $kod;
            }
        }

        $this->assertSame([], $eksik,
            'Bu ülkelerin acil numarası yok: '.implode(', ', $eksik).
            ' — config/acil.php dosyasına DOĞRULANMIŞ numara ekle.');
    }

    public function test_her_kayitta_dogrulama_tarihi_var(): void
    {
        // Eskiyen veriyi fark edebilmek için tarih zorunlu.
        foreach (config('acil.ulkeler') as $kod => $kayit) {
            $this->assertArrayHasKey('dogrulandi', $kayit, "{$kod}: dogrulandi tarihi yok");
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $kayit['dogrulandi'], "{$kod}: tarih biçimi bozuk");
        }
    }

    public function test_dogrulama_tarihi_istemciye_sizmaz(): void
    {
        // Bakım alanı; arayüze taşınırsa gereksiz veri ve gürültü olur.
        $this->assertStringNotContainsString('dogrulandi', json_encode(AcilNumaralar::harita()));
    }

    public function test_hayati_katmanlar_acil_kategorisi_hic_yokken_de_basilir(): void
    {
        /*
         * KURAL 1'İN BEKÇİSİ — bu testin var olma sebebi gerçek bir tehlike.
         *
         * Eskiden bileşenin tamamı acil kategorisinin varlığına bağlıydı.
         * Sahip panelden o kategorileri pasife çekseydi, sitedeki TEK acil
         * numarası ve konsolosluk çağrı merkezi sessizce kaybolurdu — üstelik
         * hiçbir hata vermeden, kimse fark etmeden.
         *
         * Burada acil kategorisi BİLEREK hiç oluşturulmuyor.
         */
        $this->get('/')
            ->assertOk()
            ->assertSee('Acil yardım — hızlı erişim', false)   // düğmenin kendisi
            ->assertSee('Acil servis')                          // katman 1
            ->assertSee('Konsolosluk')                          // katman 2
            ->assertSee('+90 312 292 29 29')
            ->assertSee('Konumumu kopyala');
    }

    public function test_ucuncu_katman_ilan_olmasa_da_basilir(): void
    {
        /*
         * KURAL DEĞİŞTİ (sahip kararı, 2026-08-12 akşamı).
         *
         * Sabah bu testin tersi vardı: ilanı olmayan kategori HİÇ basılmıyordu,
         * çünkü büyük dokunulabilir satırlar acil durumdaki kişiyi "0 ilan
         * bulundu" sayfasına götürüyordu.
         *
         * Sahip düğmeleri geri istedi ve VAADİ değiştirdi: artık "burada
         * yardım var" değil, "şehrinde ara" deniyor. Dürüstlük başka yerden
         * geliyor — düğmeler küçük ve ikincil (hayatî katmanla karışmıyor) ve
         * altlarında "henüz kayıtlı kimse olmayabilir" yazıyor.
         *
         * İddia bölüm başlığı üzerinden kuruluyor; kategori ADI'na bakmak
         * yanlış ölçüm olurdu (aynı ad ana sayfanın başka yüzeylerinde de
         * geçiyor, panelle ilgisi olmadan).
         */
        $this->acilKategorisi();

        $this->get('/')
            ->assertOk()
            ->assertSee('Acil servis')                 // hayatî katman duruyor
            ->assertSee('Türkçe konuşan yardım')       // düğmeler envanterden bağımsız
            ->assertSee('henüz kayıtlı kimse olmayabilir'); // ama beklenti dürüst
    }

    public function test_hayati_katman_yardim_dugmelerinin_ustunde_kaliyor(): void
    {
        /*
         * DÜĞMELER GERİ GELDİ AMA SIRA DEĞİŞMEDİ. Acil numaraları ve
         * konsolosluk, dizin aramasının ÜSTÜNDE kalmalı: panelin ilk ekranı
         * hayat kurtaran katman olmalı, pazaryeri gezintisi değil.
         */
        $this->acilKategorisi();

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Acil servis', 'Konsolosluk', 'Türkçe konuşan yardım']);
    }

    public function test_panelin_yuksekligi_sinirli_ve_govdesi_kayar(): void
    {
        /*
         * SAHİBİN TELEFONUNDA ÖLÇÜLEN HATA — bu testin var olma sebebi.
         *
         * Panelin yükseklik sınırı yoktu. Kapsayıcı `items-end` olduğu için
         * içerik ekrandan uzun olunca panel AŞAĞIYA değil YUKARIYA taşıyordu:
         * başlık ve "Kapat" düğmesi ekranın üstünden çıkıyor, kapsayıcı
         * `fixed inset-0` olduğu için oraya kaydırılamıyor, panel tüm ekranı
         * kapladığı için arkaplana dokunup kapatacak boşluk da kalmıyordu.
         * Panel açıldığında ÇIKIŞ YOKTU.
         *
         * Üç şart birden aranıyor, çünkü biri düşerse hata geri gelir.
         */
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('max-height: 85dvh', $html,
            'Panelin yükseklik sınırı kalkmış — içerik uzayınca başlık ekran dışına taşar.');
        $this->assertStringContainsString('overflow-y-auto', $html,
            'Gövdenin kendi kaydırması yok — uzun içeriğe erişilemez.');
        $this->assertStringContainsString('aria-label="Kapat"', $html,
            'Kapat düğmesi yok.');
    }

    public function test_konsolosluk_cagri_merkezi_aranabilir(): void
    {
        // Yurt dışındaki Türk için en değerli tek numara; tıklanınca aramalı.
        $this->get('/')
            ->assertOk()
            ->assertSee('tel:+903122922929', false)
            ->assertSee('+90 312 292 29 29');
    }

    public function test_numara_haritasi_ve_rehberli_ulkeler_sayfaya_gecer(): void
    {
        // Alpine ülke değiştikçe numarayı bunlardan okur.
        Country::query()->where('code', 'DE')->update(['is_active' => true]);

        $html = $this->get('/')->assertOk()->getContent();

        /*
         * `@js()` çıktısı `JSON.parse('{"DE":…}')` biçimindedir:
         * tırnak ne düz `"` ne de `&quot;` — Blade JS bağlamı için `"`
         * kaçışı üretir. (Bu satır iki kez yanlış yazıldı: önce `&quot;`
         * beklendi, sonra düz `"`. İkisinde de test kırmızı verdi. Doğrusu
         * tahminle değil gerçek çıktıya bakılarak bulundu.)
         */
        $tirnak = '\u'.'0022';

        $this->assertStringContainsString($tirnak.'DE'.$tirnak, $html, 'Numara haritası sayfaya basılmamış');
        $this->assertStringContainsString($tirnak.'112'.$tirnak, $html, 'Almanya numarası haritada yok');
        $this->assertStringContainsString('rehberliUlkeler', $html);
    }

    public function test_alpine_ifadesi_html_ozniteligini_erken_kapatmiyor(): void
    {
        /*
         * ÖLÇÜLEN HATA — bu testin var olma sebebi, ve en sinsi olanı.
         *
         * `x-data="{ … }"` bir HTML ÖZNİTELİĞİDİR. İçindeki bir yorum satırına
         * çift tırnak yazıldığında ("izin verilmedi" gibi), öznitelik orada
         * KAPANIR; geri kalan JavaScript serbest metne dönüşür ve Alpine
         * "Invalid or unexpected token" ile ölür. Panelin TAMAMI çalışmaz:
         * ülke seçilemez, numara gösterilemez, modal açılamaz.
         *
         * EN TEHLİKELİ YANI: hiçbir test bunu görmez. Sunucu tarafı HTML'i
         * kusursuz basar, `assertSee` çağrılarının hepsi geçer. Bu bir kez
         * oldu ve 2000+ testin hepsi yeşil kaldı; hata yalnız tarayıcıda
         * Alpine'ın başlayıp başlamadığı ölçülünce görüldü.
         *
         * Test, özniteliği ilk çift tırnakta keserek okur ve ifadenin SON
         * üyesinin hâlâ içeride olduğunu zorlar. Erken kapanma olursa son
         * üye dışarıda kalır ve test kırılır.
         */
        $html = $this->get('/')->assertOk()->getContent();

        preg_match_all('/x-data="([^"]*)"/', $html, $eslesmeler);

        $acil = null;
        foreach ($eslesmeler[1] as $parca) {
            if (str_contains($parca, 'konumuKopyala')) {
                $acil = $parca;
                break;
            }
        }

        $this->assertNotNull($acil, 'Acil bileşeninin x-data ifadesi sayfada bulunamadı.');
        $this->assertStringContainsString('odagiTut', $acil,
            'x-data özniteliği erken kapanmış — içinde çift tırnak var. '.
            'Alpine ifadesi bozulur ve acil paneli tamamen çalışmaz.');
        $this->assertStringEndsWith('}', rtrim((string) $acil),
            'x-data ifadesi kapanış süslü parantezine ulaşmadan bitmiş.');
    }

    public function test_her_ulkenin_genel_numarasi_ne_oldugunu_soyluyor(): void
    {
        /*
         * `genel_etiket` ZORUNLU. Almanya'da panelde görünen tek adlandırılmış
         * hat "Polis 110" idi; "Ambulans" kelimesi ekranda HİÇ geçmiyordu.
         * Panik hâlindeki insan kelime tarar, rakam taramaz.
         */
        foreach (config('acil.ulkeler') as $kod => $kayit) {
            $this->assertArrayHasKey('genel_etiket', $kayit,
                "{$kod}: genel numaranın NEYE ulaştığı yazılmamış (genel_etiket).");
            $this->assertNotSame('', trim((string) $kayit['genel_etiket']),
                "{$kod}: genel_etiket boş.");
        }
    }

    public function test_turkmenistan_bolgesel_kalibi_geri_almaz(): void
    {
        /*
         * BEKÇİ — bir ölçülmüş hatanın geri gelmesini engelliyor.
         *
         * TM, "Sovyet mirası numaralandırma" diye AZ/KZ/KG/UZ/RU ile aynı
         * kalıptan (112/102/103/101) doldurulmuştu ve DÖRDÜ DE YANLIŞTI:
         * Türkmenistan üç haneli sisteme hiç geçmedi, 112'yi hiç almadı.
         * Kalıp, yanlış olduğu tek yerde de "doğrulanmış" göründü.
         *
         * Biri o beş ülkeyi kopyalayıp TM'ye yapıştırırsa bu test kırılır.
         */
        $tm = config('acil.ulkeler.TM');

        $this->assertNotSame('112', $tm['genel'],
            'TM genel numarası yine 112 yapılmış — Türkmenistan\'da 112 çalışmıyor.');
        $this->assertStringContainsString('112 çalışmaz', (string) $tm['not'],
            'TM notundaki "112 çalışmaz" uyarısı kaldırılmış.');
    }

    public function test_adlandirilmis_hatlar_genel_numaradan_farkli(): void
    {
        /*
         * `polis`/`ambulans`/`itfaiye` YALNIZ genel numaradan farklıysa
         * yazılır (config/acil.php sözleşmesi). Aynısı yazılırsa arayüzde
         * "112 / Polis 112" gibi anlamsız bir ikilem çıkar ve acil durumdaki
         * kişiye gereksiz seçim yaptırır.
         */
        foreach (config('acil.ulkeler') as $kod => $kayit) {
            foreach (['polis', 'ambulans', 'itfaiye'] as $alan) {
                if (isset($kayit[$alan])) {
                    $this->assertNotSame($kayit['genel'], $kayit[$alan],
                        "{$kod}: {$alan} genel numarayla aynı ({$kayit['genel']}) — ya kaldır ya düzelt.");
                }
            }
        }
    }

    public function test_not_metni_dugmedeki_numarayi_tekrar_etmez(): void
    {
        /*
         * Not alanı, düğmelerin ANLATAMADIĞI artık bilgi içindir. Düğmede
         * zaten yazan bir numarayı notta tekrar etmek paneli şişirir ve
         * gözün numarayı bulmasını zorlaştırır.
         */
        foreach (config('acil.ulkeler') as $kod => $kayit) {
            $not = $kayit['not'] ?? '';
            if ($not === '') {
                continue;
            }

            foreach (['polis', 'ambulans', 'itfaiye'] as $alan) {
                if (isset($kayit[$alan])) {
                    $this->assertStringNotContainsString($kayit[$alan], $not,
                        "{$kod}: '{$alan}' numarası ({$kayit[$alan]}) hem düğmede hem notta yazıyor.");
                }
            }
        }
    }

    public function test_yerel_konsolosluk_numaralari_bicimli_ve_gecerli_ulkede(): void
    {
        /*
         * +90 312 dünyanın her yerinden çalışır ama yurt dışından ULUSLARARASI
         * tarifeden ücretlendirilir — panelin kitlesi ise tam olarak yurt
         * dışındakiler. Yerel erişim numaraları bu yüzden var.
         */
        $harita = AcilNumaralar::konsoloslukYerelHaritasi();
        $this->assertNotEmpty($harita, 'Yerel konsolosluk haritası boş.');

        foreach ($harita as $kod => $kayit) {
            // `tel:` bağlantısına gireceği için E.164: yalnız + ve rakam.
            $this->assertMatchesRegularExpression('/^\+[1-9]\d{7,14}$/', $kayit['numara'],
                "{$kod}: numara E.164 biçiminde değil ({$kayit['numara']}) — tel: bağlantısı bozulur.");

            $this->assertArrayHasKey('gosterim', $kayit, "{$kod}: gösterim biçimi yok.");
            $this->assertContains($kayit['tarife'], ['ucretsiz', 'yerel'],
                "{$kod}: tarife 'ucretsiz' ya da 'yerel' olmalı.");

            // Bu ülke acil numara listesinde de olmalı; yoksa panelde ülke
            // seçilebilir ama konsolosluk satırı hiç görünmez.
            $this->assertArrayHasKey($kod, config('acil.ulkeler'),
                "{$kod}: konsolosluk numarası var ama ülke acil listesinde yok.");
        }
    }

    public function test_ucretsiz_iddiasi_yalnizca_ucretsiz_oneklerde(): void
    {
        /*
         * BU TESTİN VAR OLMA SEBEBİ BİR SÖZ.
         *
         * Bakanlığın sayfası bu numaraların çoğunu "ücretsiz" diye
         * ETİKETLEMİYOR; yalnız ABD/Kanada (888) ve Avusturya (0800) ücretsiz
         * aralıktan. Almanya (+49 30 …), İngiltere (+44 20 …), Hollanda
         * (+31 10 …), Fransa (+33 1 80 …) coğrafi/şehir numarası — yurt içi
         * tarife, ücretsiz DEĞİL.
         *
         * Ücretsiz olmayan bir hatta "ücretsiz" demek, acil durumdaki birine
         * verilmiş yanlış bir sözdür. Tersi (ucuz olanı pahalı sanmak) yalnız
         * fırsat kaçırtır. İki hata aynı ağırlıkta değil, o yüzden kural tek
         * yönlü: `ucretsiz` yalnız bilinen ücretsiz öneklerde kullanılabilir.
         */
        $ucretsizOnekler = ['+1800', '+1833', '+1844', '+1855', '+1866', '+1877', '+1888', '+43800', '+44800', '+49800', '+31800', '+33800'];

        foreach (AcilNumaralar::konsoloslukYerelHaritasi() as $kod => $kayit) {
            if ($kayit['tarife'] !== 'ucretsiz') {
                continue;
            }

            $eslesti = false;
            foreach ($ucretsizOnekler as $onek) {
                if (str_starts_with($kayit['numara'], $onek)) {
                    $eslesti = true;
                    break;
                }
            }

            $this->assertTrue($eslesti,
                "{$kod}: 'ucretsiz' denmiş ama numara ({$kayit['numara']}) bilinen bir ücretsiz "
                ."aralıkta değil. Kaynak açıkça ücretsiz demiyorsa 'yerel' yaz.");
        }
    }

    public function test_yerel_konsolosluk_haritasi_sayfaya_gecer(): void
    {
        // Alpine ülke değiştikçe bunu okur.
        $html = $this->get('/')->assertOk()->getContent();

        $tirnak = '\u'.'0022';

        $this->assertStringContainsString('konsolosluklar', $html);
        $this->assertStringContainsString($tirnak.'+4930568373099'.$tirnak, $html,
            'Almanya yerel konsolosluk numarası sayfaya basılmamış.');
    }

    public function test_platform_sinirini_soyleyen_uyari_duruyor(): void
    {
        /*
         * Nisoya acil servis DEĞİL. Bu satır kaldırılırsa arayüz, olmadığı
         * bir şey gibi davranmaya başlar — kaldırılmasını engelliyoruz.
         */
        $this->get('/')
            ->assertOk()
            ->assertSee('acil servis değildir');
    }

    protected function tearDown(): void
    {
        Cache::forget(Category::EMERGENCY_CACHE_KEY);
        Cache::forget(Country::ACTIVE_LIST_CACHE_KEY);
        parent::tearDown();
    }
}
