<?php

namespace App\Services\Demo;

use App\Enums\DealStatus;
use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Deal;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo (örnek) veri üreticisi — üye, ilan, görsel, sohbet, anlaşma,
 * değerlendirme.
 *
 * ---------------------------------------------------------------------------
 * ÜRETİLEN HER ŞEY DEFTERE YAZILIR
 *
 * Bu sınıfın sözü tek cümle: ürettiği hiçbir şey geride kalmaz. Bu yüzden her
 * `create()` çağrısının hemen ardından {@see DemoDefteri::kaydet()} gelir ve
 * diske yazılan dosya yolları da defterde taşınır. Sıra önemlidir: defter
 * üretim sırasını saklar, {@see DemoTemizleyici} ters sırada siler.
 *
 * ---------------------------------------------------------------------------
 * ELOQUENT İLE ÜRETİLİR, HTTP İLE DEĞİL
 *
 * Aynı senaryoyu controller'lardan geçirmek daha "gerçekçi" olurdu ama
 * gerçek bildirimler üretirdi: `DealNotification`, `NewReviewNotification`,
 * `NewMessageNotification` ve hız sınırları (deal-action 20/dk, review-store
 * 10/dk). Demo veri üretmek kimseye e-posta göndermemeli.
 *
 * `Listing::booted()` içindeki `updated` kancası ilan durumu DEĞİŞİRSE sahibine
 * bildirim yollar. Bu yüzden ilanlar doğru durumla BİR KEZ oluşturulur, sonradan
 * güncellenmez; görünürlük değiştirilecekse `saveQuietly()` kullanılır.
 *
 * Etkinlik günlüğü (activity_log) de bilerek kapatılır — bkz. `uret()`.
 *
 * ---------------------------------------------------------------------------
 * E-POSTA ALAN ADI `.invalid`
 *
 * RFC 2606 ile ayrılmış, ASLA çözümlenmeyen bir TLD. Demo hesaplara yanlışlıkla
 * gerçek posta gönderilse bile hiçbir yere ulaşmaz. `@nisoya.test` (eski
 * DemoSeeder'ın kullandığı) de ayrılmıştır ama demo hesapları gerçeklerden
 * ayırt etmek için tek bir alan adında toplanması daha okunur.
 */
class DemoFabrikasi
{
    public const EPOSTA_ALANI = 'demo.invalid';

    /** Demo hesapların parolası — hepsi aynı; gerçek bir hesap değiller. */
    public const PAROLA = 'demo-nisoya-1234';

    private const ISIMLER = [
        ['Elif', 'Kaya'], ['Mehmet', 'Demir'], ['Zeynep', 'Şahin'], ['Can', 'Yıldız'],
        ['Ayşe', 'Çelik'], ['Burak', 'Arslan'], ['Deniz', 'Koç'], ['Selin', 'Aydın'],
        ['Emre', 'Özkan'], ['Merve', 'Doğan'], ['Kerem', 'Polat'], ['Nazlı', 'Erdem'],
        ['Ozan', 'Kurt'], ['İrem', 'Aksoy'], ['Baran', 'Güneş'], ['Ece', 'Karan'],
        ['Tolga', 'Yavuz'], ['Aslı', 'Bulut'], ['Umut', 'Şen'], ['Gizem', 'Ateş'],
        ['Halil', 'Öztürk'], ['Buse', 'Duran'], ['Serkan', 'Acar'], ['Melis', 'Ünal'],
    ];

    private const SEHIRLER = [
        ['DE', 'Berlin', 'EUR'], ['DE', 'Köln', 'EUR'], ['NL', 'Amsterdam', 'EUR'],
        ['GB', 'Londra', 'GBP'], ['CH', 'Zürih', 'CHF'], ['DE', 'Münih', 'EUR'],
        ['DE', 'Frankfurt', 'EUR'], ['AT', 'Viyana', 'EUR'], ['FR', 'Paris', 'EUR'],
        ['US', 'New York', 'USD'], ['GB', 'Manchester', 'GBP'], ['NL', 'Rotterdam', 'EUR'],
    ];

    /**
     * İlan kataloğu: başlık + GERÇEK kategori slug'ı + gerçekçi fiyat aralığı.
     *
     * Eski hâli yalnız başlık listesiydi ve her ilana veritabanındaki İLK alt
     * kategori atanıyordu — ana sayfa "Bebek bakıcılığı" kartında "Yabancı
     * Dil Dersi" çipiyle doldu (canlıda görüldü, 2026-08-01). Slug'lar
     * CategorySeeder'daki adların Str::slug karşılığı; kategori bulunamazsa
     * (admin silmiş/yeniden adlandırmış olabilir) ilk alt kategoriye düşülür
     * ki demo üretimi hiç kırılmasın.
     *
     * Fiyatlar aralıktan DETERMİNİSTİK seçilir (sira tohumlu) — testler ve
     * tekrar koşular kararlı kalır, ama her ilan aynı fiyatı taşımaz.
     */
    private const ILAN_KATALOGU = [
        ['Online İngilizce konuşma dersi', 'yabanci-dil-dersi', 15, 25, 'saatlik'],
        ['Almanca A1-B2 özel ders', 'yabanci-dil-dersi', 18, 30, 'saatlik'],
        ['Ev taşıma ve nakliyat yardımı', 'nakliyat-tasinma', 45, 70, 'saatlik'],
        ['Ev yapımı baklava ve börek', 'pasta-tatli', 22, 40, 'paket'],
        ['Düğün fotoğrafçılığı', 'fotograf-video', 350, 600, 'is_basina'],
        ['Web sitesi tasarımı', 'web-sitesi', 400, 900, 'is_basina'],
        ['Resmî evrak tercümesi', 'tercume', 30, 60, 'is_basina'],
        ['Bebek bakıcılığı', 'bebek-bakiciligi', 12, 18, 'saatlik'],
        ['Havalimanı transferi', 'havalimani-transferi', 40, 70, 'is_basina'],
        ['Tadilat, boya ve montaj', 'tadilat-boya', 28, 45, 'saatlik'],
    ];

    public function __construct(
        private readonly DemoDefteri $defter,
        private readonly DemoGorselUretici $gorsel,
    ) {}

    /**
     * Bir demo partisi üretir.
     *
     * @param  bool  $gorunur  true ise ilanlar `aktif` (sitede görünür, "ÖRNEK"
     *                         rozetiyle); false ise `taslak` — hiçbir herkese
     *                         açık sorgu taslak ilanı göstermez, yani üretim
     *                         sitesinde hiçbir şey değişmez.
     * @return array{parti: string, uye: int, ilan: int, gorsel: int, sohbet: int, anlasma: int, degerlendirme: int}
     */
    public function uret(int $uyeSayisi = 4, int $uyeBasinaIlan = 2, bool $gorunur = false): array
    {
        $parti = $this->defter->yeniParti();

        /*
         * Etkinlik günlüğü kapatılır. `Listing` LogsActivity kullanıyor;
         * açık bırakılsaydı her demo ilan için activity_log satırı yazılırdı
         * ve o tablonun modele bağlı bir FK'si YOK — yani demo silindikten
         * sonra bile orada kalırdı. Üretmemek, sonradan temizlemekten güvenli.
         */
        return activity()->withoutLogs(function () use ($parti, $uyeSayisi, $uyeBasinaIlan, $gorunur): array {
            $uyeler = [];
            $ilanlar = [];
            $gorselSayisi = 0;

            for ($i = 0; $i < $uyeSayisi; $i++) {
                $uyeler[] = $uye = $this->uyeUret($parti, $i);

                for ($j = 0; $j < $uyeBasinaIlan; $j++) {
                    $ilanlar[] = $ilan = $this->ilanUret($parti, $uye, count($ilanlar), $gorunur);
                    $this->gorselUret($parti, $ilan, count($ilanlar));
                    $gorselSayisi++;
                }
            }

            $sohbet = $anlasma = $degerlendirme = 0;

            // Alıcı–satıcı çiftleri: her üye bir sonrakinin ilanına talip olur.
            // En az iki üye gerekir; tek üyeyle kimse kimseyle konuşamaz.
            $uyeAdedi = count($uyeler);

            for ($i = 0; $i < $uyeAdedi && $uyeAdedi > 1; $i++) {
                $alici = $uyeler[$i];
                $satici = $uyeler[($i + 1) % $uyeAdedi];
                $ilan = collect($ilanlar)->firstWhere('user_id', $satici->id);

                if ($ilan === null) {
                    continue;
                }

                $konusma = $this->sohbetUret($parti, $alici, $satici, $ilan);
                $sohbet++;

                // Her çiftte anlaşma yok: gerçek bir pazaryerinde de her sohbet
                // anlaşmayla bitmez ve demo veri o dengesizliği yansıtmalı.
                if ($i % 2 === 0) {
                    $this->anlasmaUret($parti, $konusma, $alici, $satici, $ilan);
                    $anlasma++;

                    $this->degerlendirmeUret($parti, $alici, $satici);
                    $degerlendirme++;
                }
            }

            return [
                'parti' => $parti,
                'uye' => count($uyeler),
                'ilan' => count($ilanlar),
                'gorsel' => $gorselSayisi,
                'sohbet' => $sohbet,
                'anlasma' => $anlasma,
                'degerlendirme' => $degerlendirme,
            ];
        });
    }

    private function uyeUret(string $parti, int $sira): User
    {
        [$ad, $soyad] = self::ISIMLER[$sira % count(self::ISIMLER)];
        [$ulke, $sehir, $paraBirimi] = self::SEHIRLER[$sira % count(self::SEHIRLER)];

        // Benzersizlik parti kimliğinden gelir: aynı isimler farklı partilerde
        // tekrar üretilebilsin diye e-posta ve kullanıcı adı partiyle damgalanır.
        $damga = str_replace('-', '', $parti).'-'.$sira;

        $uye = User::create([
            'name' => $ad.' '.$soyad,
            'username' => Str::slug($ad.'-'.$soyad).'-'.$damga,
            'email' => 'demo-'.$damga.'@'.self::EPOSTA_ALANI,
            'password' => Hash::make(self::PAROLA),
            'email_verified_at' => now(),
            'role' => UserRole::Uye,
            'status' => UserStatus::Aktif,
            'country_code' => $ulke,
            'city' => $sehir,
            'preferred_currency' => $paraBirimi,
            'bio' => 'Örnek hesap — Nisoya demo verisi.',
            'is_demo' => true,
        ]);

        $avatar = $this->gorsel->uret(mb_substr($ad, 0, 12), 'avatars', $sira, 600, 600);

        $uye->forceFill(['avatar_path' => $avatar['large']])->saveQuietly();

        // Avatar dosyaları için gözlemci YOK — üç varyantı da deftere yaz,
        // yoksa üye silindiğinde diskte kalırlar.
        $this->defter->kaydet($parti, $uye, [$avatar['thumb'], $avatar['medium'], $avatar['large']]);

        return $uye;
    }

    private function ilanUret(string $parti, User $sahip, int $sira, bool $gorunur): Listing
    {
        [$baslik, $kategoriSlug, $enAz, $enCok, $birim] = self::ILAN_KATALOGU[$sira % count(self::ILAN_KATALOGU)];
        [$ulke, $sehir, $paraBirimi] = self::SEHIRLER[$sira % count(self::SEHIRLER)];

        // Aralıktan deterministik fiyat: tekrar koşular kararlı, ilanlar çeşitli.
        $fiyat = $enAz + (($sira * 7) % ($enCok - $enAz + 1));

        $ilan = Listing::create([
            'user_id' => $sahip->id,
            'category_id' => Category::query()->where('slug', $kategoriSlug)->value('id')
                ?? Category::query()->whereNotNull('parent_id')->value('id'),
            'type' => 'hizmet',
            'title' => '[ÖRNEK] '.$baslik,
            'slug' => Str::slug($baslik).'-demo-'.str_replace('-', '', $parti).'-'.$sira,
            'description' => 'Bu bir ÖRNEK ilandır — Nisoya demo verisidir, gerçek bir hizmet değildir. '
                .$baslik.' için örnek açıklama metni. Bu ilana gelen mesajlar yanıtlanmaz.',
            'price' => $fiyat,
            'currency' => $paraBirimi,
            'price_unit' => $birim,
            'country_code' => $ulke,
            'city' => $sehir,
            'is_remote' => $sira % 3 === 0,
            // GÖRÜNÜRLÜĞÜN TEK ANAHTARI BU: bu şemada herkese açık görünürlüğün
            // tek koşulu `status === 'aktif'`. `taslak` bırakmak, ilanı 14
            // herkese açık sorgu noktasının hepsinden birden çıkarır.
            'status' => $gorunur ? ListingStatus::Aktif : ListingStatus::Taslak,
            'is_demo' => true,
            'views_count' => 0,
        ]);

        $this->defter->kaydet($parti, $ilan);

        return $ilan;
    }

    private function gorselUret(string $parti, Listing $ilan, int $tohum): ListingImage
    {
        $yollar = $this->gorsel->uret(self::ILAN_KATALOGU[$tohum % count(self::ILAN_KATALOGU)][0], 'listings', $tohum);

        $gorsel = ListingImage::create([
            'listing_id' => $ilan->id,
            'path_thumb' => $yollar['thumb'],
            'path_medium' => $yollar['medium'],
            'path_large' => $yollar['large'],
            'is_cover' => true,
            'sort_order' => 0,
        ]);

        $this->defter->kaydet($parti, $gorsel, [$yollar['thumb'], $yollar['medium'], $yollar['large']]);

        return $gorsel;
    }

    private function sohbetUret(string $parti, User $alici, User $satici, Listing $ilan): Conversation
    {
        // `Conversation::create()` DEĞİL: bu metot katılımcı sırasını
        // normalize eder (user_one = min id) ve null listing tuzağını çözer.
        $konusma = Conversation::findOrCreateBetween($alici->id, $satici->id, $ilan->id);

        // SOHBET MESAJLARDAN ÖNCE DEFTERE GİRER. Defter üretim sırasını
        // saklar ve temizleyici TERS SIRADA siler; sohbet sonra yazılsaydı
        // önce o silinir, mesajlar veritabanı cascade'iyle sessizce giderdi
        // ve defterdeki mesaj satırları "kayıp" görünürdü.
        //
        // `wasRecentlyCreated` KONTROLÜ ŞART: `findOrCreateBetween` var olan
        // bir sohbeti de döndürebilir. Bizim üretmediğimiz bir kaydı deftere
        // yazmak, temizlikte GERÇEK veriyi silmek demektir.
        if ($konusma->wasRecentlyCreated) {
            $this->defter->kaydet($parti, $konusma);
        }

        foreach ([
            [$alici->id, 'Merhaba, ilanınız hâlâ geçerli mi? (örnek mesaj)'],
            [$satici->id, 'Merhaba, evet geçerli. (örnek mesaj)'],
            [$alici->id, 'Harika, detayları konuşalım. (örnek mesaj)'],
        ] as [$gonderen, $metin]) {
            $mesaj = $konusma->messages()->create([
                'sender_id' => $gonderen,
                'type' => 'text',
                'body' => $metin,
            ]);

            $this->defter->kaydet($parti, $mesaj);
        }

        $konusma->forceFill(['last_message_at' => now()])->saveQuietly();

        return $konusma;
    }

    private function anlasmaUret(string $parti, Conversation $konusma, User $alici, User $satici, Listing $ilan): void
    {
        // Doğrudan `tamamlandi` yazılıyor: durum makinesinin ara adımları
        // (teklif → kabul) gerçek akışta iki ayrı insan tıklamasıdır ve burada
        // taklit edilmelerinin bir faydası yok. `completed_at` DOLDURULMALI —
        // "Doğrulanmış işlem" rozeti ReviewController'da o alana göre
        // `latest('completed_at')` ile bulunuyor.
        $anlasma = $konusma->deals()->create([
            'listing_id' => $ilan->id,
            'seller_id' => $satici->id,
            'buyer_id' => $alici->id,
            'proposed_by' => $alici->id,
            'amount' => $ilan->price,
            'currency' => $ilan->currency,
            'status' => DealStatus::Tamamlandi,
            'accepted_at' => now()->subDay(),
            'completed_at' => now(),
        ]);

        $this->defter->kaydet($parti, $anlasma);
    }

    private function degerlendirmeUret(string $parti, User $degerlendiren, User $degerlendirilen): void
    {
        $anlasmaId = Deal::query()
            ->where('buyer_id', $degerlendiren->id)
            ->where('seller_id', $degerlendirilen->id)
            ->where('status', DealStatus::Tamamlandi)
            ->latest('completed_at')
            ->value('id');

        $degerlendirme = Review::create([
            'reviewer_id' => $degerlendiren->id,
            'reviewee_id' => $degerlendirilen->id,
            'listing_id' => null,
            'deal_id' => $anlasmaId,
            'rating' => 5,
            'comment' => 'Örnek değerlendirme — Nisoya demo verisidir.',
            'status' => 'yayinda',
        ]);

        $this->defter->kaydet($parti, $degerlendirme);
    }
}
