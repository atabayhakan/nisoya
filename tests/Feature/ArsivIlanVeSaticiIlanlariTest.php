<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\UserStatus;
use App\Models\Listing;
use App\Models\User;
use App\Support\Para;
use App\Support\Settings;
use App\Support\Tema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Arşiv kipi + satıcının güncel/geçmiş ilanları + Türkçe fiyat (2026-08-06).
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Sahip ilan detayında "satıcının güncel ve eski ilanlarına gidebileceğim
 * düğmeler olsun" dedi. Eski ilanları LİSTELEMEK, o ilanların AÇILABİLMESİ
 * demektir — yoksa her kart 404'e giderdi. Yani bu bir görünüm işi değil,
 * bir ERİŞİM değişikliğiydi ve testleri de öyle yazıldı.
 *
 * ---------------------------------------------------------------------------
 * SINIR NEREDE
 *
 * Açılan yalnız `Pasif`. Taslak / onay bekleyen / reddedilen ilanlar sahibi
 * dışında kimseye görünmez ve bu testler tam olarak bunu zorlar.
 */
class ArsivIlanVeSaticiIlanlariTest extends TestCase
{
    use RefreshDatabase;

    /** Vitrin ve klasik temanın İKİSİNDE de aynı davranış beklenir. */
    public static function temalar(): array
    {
        return ['klasik' => ['klasik'], 'vitrin' => ['vitrin']];
    }

    private function temayiKur(string $tema): void
    {
        Settings::setMany(['gorunum.tema' => $tema]);
        Cache::flush();
        // Temanın GERÇEKTEN değiştiğini doğrula: bu satır olmadan iki tema
        // varyantı da klasik temayı ölçer ve test hiçbir şey kanıtlamaz.
        $this->assertSame($tema === 'vitrin', Tema::vitrinMi());
    }

    /**
     * ARŞİV = ÜYENİN KENDİ KALDIRDIĞI ilan. Pasif üretirken `unpublished_at`
     * doldurulur; yönetimin kaldırdığı hâl için `yonetimKaldirdi()` var.
     */
    private function ilan(User $satici, ListingStatus $durum = ListingStatus::Aktif): Listing
    {
        return Listing::factory()->for($satici)->create([
            'status' => $durum,
            'unpublished_at' => $durum === ListingStatus::Pasif ? now() : null,
        ]);
    }

    /** Pasif ama `unpublished_at` NULL — yönetim/sistem kaldırdı. */
    private function yonetimKaldirdi(User $satici): Listing
    {
        return Listing::factory()->for($satici)->create([
            'status' => ListingStatus::Pasif,
            'unpublished_at' => null,
        ]);
    }

    // -----------------------------------------------------------------
    // ERİŞİM SINIRI
    // -----------------------------------------------------------------

    #[DataProvider('temalar')]
    public function test_yayindan_kalkmis_ilan_misafire_acilir_ama_arsiv_der(string $tema): void
    {
        $this->temayiKur($tema);
        $ilan = $this->ilan(User::factory()->create(), ListingStatus::Pasif);

        $this->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertSee('Bu ilan artık yayında değil')
            ->assertSee('mesaj gönderilemez');
    }

    #[DataProvider('temalar')]
    public function test_arsiv_sayfasi_arama_motoruna_kapali(string $tema): void
    {
        // Ölü bir sayfanın arama sonucunda çıkması, tıklayan herkese
        // karşılıksız bir söz vermek olurdu.
        $this->temayiKur($tema);
        $ilan = $this->ilan(User::factory()->create(), ListingStatus::Pasif);

        $this->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertSee('noindex', false);
    }

    #[DataProvider('temalar')]
    public function test_aktif_ilan_noindex_almaz(string $tema): void
    {
        // Yukarıdaki testin ters yönü: noindex'in HER sayfaya sızmadığını
        // kanıtlamazsak, o test her zaman geçer ve hiçbir şey ölçmez.
        $this->temayiKur($tema);
        $ilan = $this->ilan(User::factory()->create());

        $this->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertDontSee('noindex', false);
    }

    public function test_taslak_beklemede_ve_reddedilen_hala_404(): void
    {
        /*
         * ARŞİV KAPISI YALNIZ PASİF İÇİN AÇILDI. Bu üçü sızarsa: biri henüz
         * yayınlanmamış bir taslak, biri moderasyondan geçmemiş, biri kasten
         * reddedilmiş içerik olurdu.
         */
        $satici = User::factory()->create();

        foreach ([ListingStatus::Taslak, ListingStatus::Beklemede, ListingStatus::Reddedildi] as $durum) {
            $ilan = $this->ilan($satici, $durum);

            $this->get(route('listings.show', [$ilan, $ilan->slug]))
                ->assertNotFound();
        }
    }

    // -----------------------------------------------------------------
    // MODERASYON DELİĞİ — arşiv kapısı yönetimin kararını geri açmamalı
    // -----------------------------------------------------------------

    #[DataProvider('temalar')]
    public function test_yonetimin_kaldirdigi_ilan_arsivde_acilmaz(string $tema): void
    {
        /*
         * KRİTİK. Arşiv ilk yazışta `status === Pasif` diyordu ve öyle canlıya
         * çıktı (PR #115/#116). Ama `UserObserver::suspendActiveListings()`
         * ASKIYA ALINAN hesabın ilanlarını da Pasif'e çekiyor — yani yönetimin
         * sustuduğu içerik, herkese açık profildeki "Geçmiş" sekmesinden geri
         * sızıyordu.
         *
         * Ayrım `unpublished_at`: dolu = üye kendi kaldırdı, NULL = yönetim.
         */
        $this->temayiKur($tema);
        $ilan = $this->yonetimKaldirdi(User::factory()->create());

        $this->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertNotFound();
    }

    public function test_yonetimin_kaldirdigi_ilan_gecmis_listesinde_gorunmez(): void
    {
        $satici = User::factory()->create();
        $uyeKaldirdi = $this->ilan($satici, ListingStatus::Pasif);
        $yonetimKaldirdi = $this->yonetimKaldirdi($satici);

        $this->get(route('profiles.show', ['user' => $satici->username, 'durum' => 'gecmis']))
            ->assertOk()
            ->assertSee($uyeKaldirdi->title)
            ->assertDontSee($yonetimKaldirdi->title);
    }

    public function test_yonetimin_kaldirdigi_ilan_gecmis_sayisina_katilmaz(): void
    {
        // Sayı da aynı kapıdan geçmeli; yoksa "Geçmiş ilanları (3)" der ama
        // liste 1 kayıt gösterir — üstelik saklanan 2'yi ele verir.
        $satici = User::factory()->create();
        $bakilan = $this->ilan($satici);
        $this->ilan($satici, ListingStatus::Pasif);
        $this->yonetimKaldirdi($satici);
        $this->yonetimKaldirdi($satici);

        $this->get(route('listings.show', [$bakilan, $bakilan->slug]))
            ->assertOk()
            ->assertSee('Geçmiş ilanları (1)');
    }

    public function test_askiya_alinan_hesabin_ilanlari_arsive_dusmez(): void
    {
        /*
         * Uçtan uca: gerçek askıya alma akışı çalıştırılır (UserObserver),
         * sonra o ilanın herkese açık arşivde OLMADIĞI doğrulanır. Kolonu
         * elle kurmak yerine tetikleyiciyi kullanmak bilinçli — bu depoda
         * "yardımcı metodu test etmek tetikleyiciyi test etmek değildir"
         * dersi tam da bu observer'dan çıktı.
         */
        $satici = User::factory()->create(['status' => UserStatus::Aktif]);
        $ilan = $this->ilan($satici);

        $satici->update(['status' => UserStatus::Askida]);

        $ilan->refresh();
        $this->assertSame(ListingStatus::Pasif, $ilan->status, 'Askıya alma ilanı susturmalı.');
        $this->assertNull($ilan->unpublished_at, 'Yönetim kaldırdığında damga basılmamalı.');
        $this->assertFalse($ilan->arsivdeMi());

        $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertNotFound();
    }

    public function test_yonetici_ilani_geri_acinca_damga_silinir(): void
    {
        /*
         * KENAR DURUM — deliğin geri açılabildiği dizi:
         * üye kaldırır (damga basılır) → yönetici panelden Aktif yapar →
         * yönetici sonra Pasif'e çeker. Damga silinmezse ilan "üye kaldırdı"
         * gibi görünür ve herkese açık arşive düşerdi.
         */
        $satici = User::factory()->create();
        $ilan = $this->ilan($satici, ListingStatus::Pasif);
        $this->assertNotNull($ilan->unpublished_at);

        // Yönetim yolu: doğrudan durum değişikliği (Filament'in yaptığı gibi).
        $ilan->update(['status' => ListingStatus::Aktif]);
        $this->assertNull($ilan->fresh()->unpublished_at, 'Aktife dönerken damga silinmeli.');

        $ilan->update(['status' => ListingStatus::Pasif]);
        $ilan->refresh();

        $this->assertNull($ilan->unpublished_at);
        $this->assertFalse($ilan->arsivdeMi(), 'Yönetimin kaldırdığı ilan arşive düşmemeli.');
        $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertNotFound();
    }

    public function test_sahibi_kendi_pasif_ilanini_arsiv_bandi_olmadan_gorur(): void
    {
        $satici = User::factory()->create();
        $ilan = $this->ilan($satici, ListingStatus::Pasif);

        $this->actingAs($satici)
            ->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertSee('yalnızca sen görüyorsun');
    }

    public function test_arsiv_ilana_mesaj_gonderilemez(): void
    {
        /*
         * ASIL KAPI BU. Formu gizlemek POST'u engellemez; arşiv sayfaları
         * herkese açıldığı için sınırın controller'da olması ŞART.
         */
        $ilan = $this->ilan(User::factory()->create(), ListingStatus::Pasif);
        $alici = User::factory()->create();

        $this->actingAs($alici)
            ->post(route('messages.start', $ilan), ['body' => 'Bu hâlâ satılık mı?'])
            ->assertRedirect();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_arsiv_goruntulemesi_sayaci_sismez(): void
    {
        // Ölü ilanın görüntülenme sayısı bir talep göstergesi olarak
        // okunamaz hâle gelmesin.
        $ilan = $this->ilan(User::factory()->create(), ListingStatus::Pasif);
        $oncekiSayi = $ilan->views_count;

        $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk();

        $this->assertSame($oncekiSayi, $ilan->fresh()->views_count);
    }

    // -----------------------------------------------------------------
    // SATICININ İLANLARI DÜĞMELERİ
    // -----------------------------------------------------------------

    #[DataProvider('temalar')]
    public function test_ilan_detayinda_guncel_ve_gecmis_dugmeleri_gercek_sayiyla_cikar(string $tema): void
    {
        $this->temayiKur($tema);
        $satici = User::factory()->create();

        $bakilan = $this->ilan($satici);              // güncel #1
        $this->ilan($satici);                          // güncel #2
        $this->ilan($satici, ListingStatus::Pasif);    // geçmiş #1

        $this->get(route('listings.show', [$bakilan, $bakilan->slug]))
            ->assertOk()
            ->assertSee('Güncel ilanları (2)')
            ->assertSee('Geçmiş ilanları (1)');
    }

    #[DataProvider('temalar')]
    public function test_mobil_satici_seridi_her_iki_temada_da_basilir(string $tema): void
    {
        /*
         * BU TESTİN OLMAMASI CANLIDA BİR EKSİK ÜRETTİ.
         *
         * Şerit ilk yazışta yalnız `vitrin/listings/show.blade.php` içine
         * gömülmüştü. Canlı ilan detayı KLASİK şablonu basıyor — şerit hiç
         * görünmedi. Düğmeler görünüyordu, çünkü onların iki temada koşan bir
         * testi vardı ve o test onları en baştan iki şablona da koydurmuştu.
         *
         * Yani eksik kod değil, EKSİK TESTTİ: test edilmeyen taraf kırıldı.
         */
        $this->temayiKur($tema);
        $ilan = $this->ilan(User::factory()->create(['name' => 'Ayşe Demir']));

        $this->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            // Şeridin kendisi (mobilde görünür, masaüstünde gizli).
            ->assertSee('lg:hidden', false)
            // Kimlik: satıcı adı ve kıdem metni (bkz. User::kidemMetni,
            // docs/03-buyume-fikirleri.md 2026-08-19 · YAP — taze hesap
            // için "yakın zamanda katıldı" döner, factory created_at'i
            // özelleştirmiyor).
            ->assertSee('Ayşe Demir')
            ->assertSee("Nisoya'da yakın zamanda katıldı")
            // İletişime kısayol ve hedefi.
            ->assertSee('Satıcıya mesaj yaz')
            ->assertSee('satici-iletisim', false);
    }

    #[DataProvider('temalar')]
    public function test_arsivde_mobil_seritte_mesaj_kisayolu_cikmaz(string $tema): void
    {
        // Cevabı gelmeyecek bir mesaja davet etmemeli.
        $this->temayiKur($tema);
        $ilan = $this->ilan(User::factory()->create(), ListingStatus::Pasif);

        $this->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertDontSee('Satıcıya mesaj yaz');
    }

    #[DataProvider('temalar')]
    public function test_gecmis_ilani_olmayan_saticida_dugme_hic_basilmaz(string $tema): void
    {
        // Sıfır gösteren düğme, tıklandığında boş sayfa açar — olmayan bir
        // şeyin sözünü verir.
        $this->temayiKur($tema);
        $ilan = $this->ilan(User::factory()->create());

        $this->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertDontSee('Geçmiş ilanları');
    }

    public function test_taslak_ilan_gecmis_sayisina_katilmaz(): void
    {
        /*
         * "Geçmiş" = yayından kalkmış demek, "henüz yayınlanmamış" değil.
         * Taslağı saymak, sayfada olmayan bir kartın sözünü verirdi.
         */
        $satici = User::factory()->create();
        $ilan = $this->ilan($satici);
        $this->ilan($satici, ListingStatus::Taslak);

        $this->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertDontSee('Geçmiş ilanları');
    }

    // -----------------------------------------------------------------
    // PROFİLDEKİ SEKME
    // -----------------------------------------------------------------

    public function test_profil_gecmis_sekmesi_yalniz_pasif_ilanlari_listeler(): void
    {
        $satici = User::factory()->create();
        $aktif = $this->ilan($satici);
        $pasif = $this->ilan($satici, ListingStatus::Pasif);
        $taslak = $this->ilan($satici, ListingStatus::Taslak);

        $this->get(route('profiles.show', ['user' => $satici->username, 'durum' => 'gecmis']))
            ->assertOk()
            ->assertSee($pasif->title)
            ->assertDontSee($aktif->title)
            ->assertDontSee($taslak->title);
    }

    #[DataProvider('temalar')]
    public function test_arsiv_karti_her_iki_temada_da_yayinda_degil_der(string $tema): void
    {
        /*
         * VİTRİN OVERRIDE TUZAĞI — bu depoda beşinci kez.
         *
         * Rozet önce yalnız `partials/listing-card.blade.php`'ye eklendi ve
         * canlı temada HİÇ GÖRÜNMEDİ: Vitrin o partial'ı override edip
         * `x-vitrin.listing-card` component'ine köprülüyor. Yerel tarayıcı
         * ölçümü yakaladı, kod okumak yakalamamıştı.
         *
         * Bu test iki temayı da zorlar; tek temada geçen bir iddia, temaya
         * göre değişen bir gerçeği gizler.
         */
        $this->temayiKur($tema);
        $satici = User::factory()->create();
        $this->ilan($satici, ListingStatus::Pasif);

        $this->get(route('profiles.show', ['user' => $satici->username, 'durum' => 'gecmis']))
            ->assertOk()
            ->assertSee('Yayında değil');
    }

    public function test_bilinmeyen_durum_degeri_guncele_duser(): void
    {
        // Beyaz liste: `?durum=taslak` ile durum sızdırmak mümkün olmamalı.
        $satici = User::factory()->create();
        $aktif = $this->ilan($satici);
        $taslak = $this->ilan($satici, ListingStatus::Taslak);

        $this->get(route('profiles.show', ['user' => $satici->username, 'durum' => 'taslak']))
            ->assertOk()
            ->assertSee($aktif->title)
            ->assertDontSee($taslak->title);
    }

    public function test_profildeki_aktif_ilan_karti_gecmis_sekmesinde_de_guncel_sayiyi_gosterir(): void
    {
        /*
         * TUZAK: sayı kartı önceden `$listings->total()` okuyordu. Sekme
         * "geçmiş"e alındığında o sayı geçmiş ilanları sayar ama etiket
         * "aktif ilan" der — sayfa sessizce yanlış bir şey söylerdi.
         */
        $satici = User::factory()->create();
        $this->ilan($satici);
        $this->ilan($satici);
        $this->ilan($satici, ListingStatus::Pasif);

        $this->get(route('profiles.show', ['user' => $satici->username, 'durum' => 'gecmis']))
            ->assertOk()
            // Sekme etiketleri KESİN: sayfadaki herhangi bir "2" değil, tam
            // olarak bu iki dize aranıyor.
            ->assertSee('Güncel (2)')
            ->assertSee('Geçmiş (1)')
            // Ve sayı kartı geçmiş sekmesinde bile GÜNCEL sayıyı söyler.
            ->assertSeeInOrder(['>2<', 'aktif ilan'], false);
    }

    // -----------------------------------------------------------------
    // TÜRKÇE FİYAT
    // -----------------------------------------------------------------

    public function test_fiyat_turkce_bicimde_yazilir(): void
    {
        // Ekran görüntüsündeki hata: "60,000.00 KGS" — İngilizce ayraçlar.
        $this->assertSame('60.000', Para::bicimle(60000));
        $this->assertSame('60.000', Para::bicimle('60000.00'));
        $this->assertSame('1.250,50', Para::bicimle(1250.5));
        $this->assertSame('0', Para::bicimle(0));
        $this->assertNull(Para::bicimle(null));
    }

    public function test_kurus_yalniz_gercekten_varsa_gosterilir(): void
    {
        // "60.000,00" değil "60.000": tam sayıya iki sıfır eklemek bilgi vermez.
        $this->assertSame('99', Para::bicimle(99.00));
        $this->assertSame('99,90', Para::bicimle(99.90));
    }

    #[DataProvider('temalar')]
    public function test_ilan_detayinda_fiyat_ingilizce_bicimde_basilmaz(string $tema): void
    {
        $this->temayiKur($tema);
        $ilan = Listing::factory()->create(['status' => ListingStatus::Aktif, 'price' => 60000, 'currency' => 'KGS']);

        $this->get(route('listings.show', [$ilan, $ilan->slug]))
            ->assertOk()
            ->assertSee('60.000 KGS')
            ->assertDontSee('60,000.00');
    }
}
