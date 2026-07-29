<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * İngiltere'ye gelen Türk öğrenciler için ev kurma rehberi (büyüme önerisi 4).
 *
 * NEDEN AYRI BİR SEEDER: `StaticPagesSeeder` deploy yolundan BİLEREK dışlanmış
 * (bkz. ReferenceDataSeeder docblock) — panelden düzenlenen ya da silinen
 * içeriği geri getirmemesi için. O kararı delmemek adına bu sayfa oraya
 * eklenmedi; tek seferlik, elle çalıştırılan ayrı bir seeder olarak duruyor:
 *
 *     php artisan db:seed --class=OgrenciRehberiSeeder --force
 *
 * `firstOrCreate` kullanır: ikinci kez çalıştırmak zararsızdır ve paneldeki
 * düzenlemelerin üzerine YAZMAZ.
 *
 * TASLAK OLARAK OLUŞUR (`status = taslak`). Sahibin panelden okuyup onaylaması
 * ve yayına almasını bekler — Claude halka açık içeriği kendi başına
 * yayınlamaz. Yayına alırken `publish_at` Ağustos başına ayarlanmalı:
 * Eylül'deki aramalarda görünmesi için indekslenme süresi gerekir.
 *
 * İÇERİK KARARI — ARZ ÇAĞRISI: sayfanın eylem çağrısı "gelin alışveriş yapın"
 * DEĞİL "eşyanı ücretsiz listele". Ölçüm (2026-07-29): İngiltere'de 0 ilan var,
 * sitede toplam 3 ilan var. Talep çağrısı tutulamayacak bir vaat olurdu;
 * mezun olanlara "eşyanı devret" demek ise doğru ve dürüst.
 *
 * REHBER TEK BAŞINA FAYDALI OLMALI: sayfa, ziyaretçi Nisoya'yı hiç
 * kullanmasa bile işe yarayan gerçek bilgi içerir. İçeriksiz "kapı sayfası"
 * yazmak, az önce 93 boş kategori sayfasını indeksten çıkarmamızla çelişirdi
 * ve aynı cezayı davet ederdi.
 */
class OgrenciRehberiSeeder extends Seeder
{
    public function run(): void
    {
        Page::query()->firstOrCreate(['slug' => $this->sayfa()['slug']], $this->sayfa());
    }

    /** @return array<string, mixed> */
    protected function sayfa(): array
    {
        return [
            'slug' => 'ingiltere-ogrenci-ev-kurma-rehberi',
            'title' => "İngiltere'de İlk Yıl: Türk Öğrenciler İçin Ev Kurma ve İkinci El Rehberi",
            'status' => PageStatus::Taslak->value,
            'show_in_footer' => false,
            'sort_order' => 50,
            'meta_description' => "İngiltere'de üniversiteye başlayan Türk öğrenciler için ev kurma listesi, ikinci el eşya nereden alınır, dolandırıcılıktan korunma ve mezun olanlar için eşya devretme rehberi.",
            'blocks' => $this->bloklar(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    protected function bloklar(): array
    {
        return [
            ['type' => 'metin', 'data' => ['content' => '<p>Eylül ayında İngiltere\'ye ilk kez gelen bir öğrencinin ilk iki haftası hep aynı geçer: '
                .'valizden çıkan üç parça eşya, bomboş bir oda ve "şimdi ne lazım?" sorusu. '
                .'Bu rehber o iki haftayı kolaylaştırmak için yazıldı — neyi Türkiye\'den getirmenin mantıklı olduğu, '
                .'neyi orada almanın daha ucuz olduğu, ikinci el eşyanın nereden bulunacağı ve '
                .'en önemlisi <strong>ödeme yaparken nelere dikkat edilmesi gerektiği</strong>.</p>'
                .'<p>Kimseye bir şey satmaya çalışmıyoruz. Aşağıdaki bilgilerin çoğu Nisoya\'yı hiç kullanmasan da işine yarar.</p>',
            ]],

            ['type' => 'baslik', 'data' => ['level' => 'h2', 'text' => 'Türkiye\'den getir mi, orada al mı?']],

            ['type' => 'iki_sutun', 'data' => [
                'left' => '<p><strong>Türkiye\'den getirmeye değer</strong></p>'
                    .'<ul>'
                    .'<li><strong>Çeyrek/yarım takım nevresim</strong> — İngiltere\'de yatak ölçüleri farklı (single 90×190, double 135×190). '
                    .'Yurt odası genelde single. Ölçüyü öğrenmeden nevresim getirme.</li>'
                    .'<li><strong>Baharat, çay, kahvaltılık</strong> — bulunur ama pahalı. İlk ay için iyi gelir.</li>'
                    .'<li><strong>Reçeteli ilaçların raporu</strong> — GP kaydı yaptırana kadar sürebilir.</li>'
                    .'<li><strong>Resmî belgelerin fotokopisi</strong> — pasaport, kabul mektubu, CAS, banka dökümü.</li>'
                    .'</ul>',
                'right' => '<p><strong>Orada almak daha mantıklı</strong></p>'
                    .'<ul>'
                    .'<li><strong>Yorgan, yastık, havlu</strong> — hacim kaplar, orada ucuz.</li>'
                    .'<li><strong>Mutfak takımı</strong> — tencere, tabak, çatal-bıçak. İkinci el bolca var.</li>'
                    .'<li><strong>Elektrikli her şey</strong> — İngiltere\'de fiş 3 uçlu (Type G) ve şebeke 230V. '
                    .'Adaptörle idare etmeye çalışmak yerine orada al.</li>'
                    .'<li><strong>Kışlık mont</strong> — hava beklediğinden farklı; yerinde görüp almak daha isabetli.</li>'
                    .'</ul>',
            ]],

            ['type' => 'baslik', 'data' => ['level' => 'h2', 'text' => 'İlk hafta: ev kurma listesi']],

            ['type' => 'metin', 'data' => ['content' => '<p>Yurt odasına giriyorsan mutfak genelde ortak ve temel eşya çıkabilir; '
                .'özel ev tutuyorsan liste uzar. Öncelik sırasıyla:</p>'
                .'<ol>'
                .'<li><strong>Uyku</strong> — yatak örtüsü, yorgan, yastık, havlu. İlk gece bunlar olmadan zor.</li>'
                .'<li><strong>Mutfak</strong> — tencere, tava, tabak, bardak, çatal-bıçak, açacak, kesme tahtası.</li>'
                .'<li><strong>Temizlik</strong> — deterjan, bez, çöp poşeti, süpürge (ev ortaksa sorup al).</li>'
                .'<li><strong>Çalışma</strong> — masa lambası, uzatma kablosu, kulaklık.</li>'
                .'<li><strong>Isınma</strong> — bazı evlerde ısıtma zayıf; elektrikli battaniye ya da küçük ısıtıcı hayat kurtarır.</li>'
                .'</ol>'
                .'<p><strong>Acele etme:</strong> ilk hafta her şeyi almak zorunda değilsin. '
                .'Dönem başında ikinci el arzı en yüksek noktasındadır — mezun olanlar ve taşınanlar aynı anda eşya çıkarır.</p>',
            ]],

            ['type' => 'baslik', 'data' => ['level' => 'h2', 'text' => 'İkinci el eşya nereden bulunur?']],

            ['type' => 'metin', 'data' => ['content' => '<p>İngiltere\'de ikinci el kültürü güçlüdür ve seçenek boldur:</p>'
                .'<ul>'
                .'<li><strong>Üniversitenin kendi grupları</strong> — çoğu okulun Facebook\'ta "freshers" ve '
                .'"buy &amp; sell" grubu vardır. Dönem sonunda mezunlar buradan eşya devreder.</li>'
                .'<li><strong>Charity shop\'lar</strong> — her şehirde var. Mutfak eşyası ve kitap için çok uygun; '
                .'gelir hayır kurumuna gider.</li>'
                .'<li><strong>Facebook Marketplace ve Gumtree</strong> — en geniş arz, ama en dikkatli olunması gereken yer.</li>'
                .'<li><strong>Türk öğrenci toplulukları</strong> — kendi dilinde anlaşmak, özellikle ilk haftalarda, '
                .'işi ciddi biçimde kolaylaştırır.</li>'
                .'</ul>',
            ]],

            ['type' => 'baslik', 'data' => ['level' => 'h2', 'text' => 'Ödeme yaparken: dolandırıcılıktan korunma']],

            ['type' => 'metin', 'data' => ['content' => '<p>Dönem başı, ikinci el dolandırıcılığının da en yoğun olduğu dönemdir. '
                .'Hedef kitle bellidir: ülkeyi yeni tanıyan, acele eden, kimseyi tanımayan öğrenci. '
                .'Birkaç kural neredeyse tüm riski ortadan kaldırır:</p>'
                .'<ul>'
                .'<li><strong>Görmeden ödeme yapma.</strong> "Kargoyla göndereyim, önce parayı at" diyen kişiyle işin olmasın. '
                .'Mümkünse eşyayı elden al.</li>'
                .'<li><strong>PayPal kullanacaksan "Mal ve Hizmetler" (Goods &amp; Services) seç.</strong> '
                .'"Arkadaşa/Aileye" (Friends &amp; Family) seçeneğinin <em>hiçbir</em> alıcı koruması yoktur ve '
                .'dolandırıcılar özellikle bunu ister. Aradaki küçük komisyon, sigorta primi gibi düşün.</li>'
                .'<li><strong>Banka havalesi geri alınamaz.</strong> Tanımadığın birine doğrudan IBAN\'a para göndermek, '
                .'nakit verip fiş almamakla aynı şeydir.</li>'
                .'<li><strong>Konuşmayı platform dışına taşımaya ısrar eden kişiye dikkat et.</strong> '
                .'"WhatsApp\'tan devam edelim" ilk adımda masumdur, ama bir sorun çıktığında elinde hiçbir kayıt kalmaz.</li>'
                .'<li><strong>Fiyat çok iyiyse bir sebebi vardır.</strong> Piyasanın çok altındaki ilan, çoğu zaman ilan değildir.</li>'
                .'</ul>'
                .'<p>Buluşurken kalabalık ve aydınlık bir yer seç — kampüs girişi, kütüphane önü, market otoparkı gibi.</p>',
            ]],

            ['type' => 'ayrac', 'data' => []],

            ['type' => 'baslik', 'data' => ['level' => 'h2', 'text' => 'Mezun oluyorsan ya da taşınıyorsan']],

            ['type' => 'metin', 'data' => ['content' => '<p>Bu rehberin diğer yarısı sana: <strong>gitmeden önce eşyanı çöpe atma.</strong></p>'
                .'<p>Haziran-Temmuz\'da mezun olan bir öğrencinin elinde genelde bir yıl kullanılmış '
                .'yorgan, mutfak takımı, masa lambası, ders kitabı ve bazen bisiklet olur. '
                .'Bunların çoğu tam da Eylül\'de gelen birinin aradığı şeylerdir. '
                .'Atmak yerine devretmek hem çevre hem de arkadan gelen için doğru olan.</p>'
                .'<p>Nisoya\'da ilan vermek ücretsizdir; komisyon yoktur, üyelik ücreti yoktur. '
                .'Eşyanı ister sat, ister ücretsiz devret — ikisi de aynı şekilde ilan edilir.</p>'
                .'<p><em>Dürüst olalım:</em> Nisoya yeni bir platform ve İngiltere tarafı henüz yeni kuruluyor. '
                .'Yani burada "hazır bir pazar var, gelin alın" demiyoruz. '
                .'Tam tersi: <strong>ilk ilanları koyanlar, Eylül\'de gelenler için o pazarı kuranlar olacak.</strong></p>',
            ]],

            ['type' => 'cta', 'data' => [
                'title' => 'Eşyanı ücretsiz listele, arkadan gelene bırak',
                'button_text' => 'Ücretsiz ilan ver',
                'button_url' => '/panel/ilan/yeni',
            ]],

            ['type' => 'metin', 'data' => ['content' => '<p class="text-sm">Bu sayfa yurt dışındaki Türklere yönelik ücretsiz bir pazaryeri olan '
                .'<a href="/">Nisoya</a> tarafından hazırlanmıştır. '
                .'Soru veya düzeltme için <a href="/iletisim">iletişim sayfamızı</a> kullanabilirsin.</p>',
            ]],
        ];
    }
}
