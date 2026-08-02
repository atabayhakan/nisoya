<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Dubai'ye yeni taşınan Türkler için ilk 30 gün rehberi (büyüme önerisi 3,
 * karar 2026-07-29: Körfez hedeflenecek — pilot sayfa Dubai).
 *
 * OgrenciRehberiSeeder ile aynı sözleşme: StaticPagesSeeder deploy yolundan
 * bilerek dışlandığı için bu sayfa da TEK SEFERLİK, ELLE çalıştırılan ayrı
 * bir seeder'dır:
 *
 *     php artisan db:seed --class=DubaiRehberiSeeder --force
 *
 * `firstOrCreate` kullanır: ikinci kez çalıştırmak zararsızdır ve paneldeki
 * düzenlemelerin üzerine YAZMAZ.
 *
 * TASLAK OLARAK OLUŞUR — yayına alma kararı sahibindir; Claude halka açık
 * içeriği kendi başına yayınlamaz.
 *
 * İÇERİK KARARI — ARZ ÇAĞRISI: Körfez'de 0 ilan var; "gelin alışveriş yapın"
 * tutulamayacak bir vaat olurdu. Sayfanın eylem çağrısı "eşyanı ücretsiz
 * listele" — İngiltere öğrenci rehberiyle aynı çerçeve. Dubai'nin gerçeği bu
 * çerçeveye ayrıca uyuyor: geçiş şehri, şehirden ayrılan her expat eşya
 * çıkarır ("leaving sale" kültürü).
 *
 * REHBER TEK BAŞINA FAYDALI OLMALI: ziyaretçi Nisoya'yı hiç kullanmasa da
 * işine yarayan gerçek bilgi içerir (fiş tipi, ilaç kuralları uyarısı,
 * eşyasız kiralık gerçeği, ödeme güvenliği) — içeriksiz "kapı sayfası"
 * yazmak, 93 boş kategori sayfasını indeksten çıkarma kararıyla çelişirdi.
 */
class DubaiRehberiSeeder extends Seeder
{
    public function run(): void
    {
        Page::query()->firstOrCreate(['slug' => $this->sayfa()['slug']], $this->sayfa());
    }

    /** @return array<string, mixed> */
    protected function sayfa(): array
    {
        return [
            'slug' => 'dubai-ilk-30-gun-rehberi',
            'title' => "Dubai'de İlk 30 Gün: Yeni Taşınan Türkler İçin Ev Kurma ve İkinci El Rehberi",
            'status' => PageStatus::Taslak->value,
            'show_in_footer' => false,
            'sort_order' => 51,
            'meta_description' => "Dubai'ye yeni taşınan Türkler için ev kurma listesi, ikinci el eşya nereden bulunur, expat çıkış satışları, dolandırıcılıktan korunma ve şehirden ayrılırken eşya devretme rehberi.",
            'blocks' => $this->bloklar(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    protected function bloklar(): array
    {
        return [
            ['type' => 'metin', 'data' => ['content' => '<p>Dubai\'ye ilk kez taşınan birinin ilk ayı hep aynı sorularla geçer: '
                .'bomboş (çoğu zaman beyaz eşyası bile olmayan) bir daire, bu sıcakta eşya taşıma derdi ve "neyi nereden alacağım?" sorusu. '
                .'Bu rehber o ilk 30 günü kolaylaştırmak için yazıldı — neyi Türkiye\'den getirmenin mantıklı olduğu, '
                .'neyi orada almanın daha ucuz olduğu, ikinci el eşyanın nereden bulunacağı ve '
                .'en önemlisi <strong>ödeme yaparken nelere dikkat edilmesi gerektiği</strong>.</p>'
                .'<p>Kimseye bir şey satmaya çalışmıyoruz. Aşağıdaki bilgilerin çoğu Nisoya\'yı hiç kullanmasan da işine yarar.</p>',
            ]],

            ['type' => 'baslik', 'data' => ['level' => 'h2', 'text' => 'Türkiye\'den getir mi, orada al mı?']],

            ['type' => 'iki_sutun', 'data' => [
                'left' => '<p><strong>Türkiye\'den getirmeye değer</strong></p>'
                    .'<ul>'
                    .'<li><strong>Reçeteli ilaçların ve doktor raporu</strong> — BAE bazı etken maddeleri kontrollü ilaç sayar; '
                    .'raporsuz ilaç sınırda ciddi sorun olabilir. Gelmeden önce ilacının güncel durumunu resmî kaynaktan kontrol et, '
                    .'raporu yanında taşı.</li>'
                    .'<li><strong>Resmî belgelerin apostilli kopyaları</strong> — ikamet, iş ve okul işlemleri belge ister; '
                    .'Türkiye\'den apostil almak, Dubai\'den uzaktan halletmeye çalışmaktan çok daha kolay.</li>'
                    .'<li><strong>Baharat, çay, kahvaltılık</strong> — Türk marketleri var ama fiyatlar Türkiye\'nin birkaç katı. '
                    .'İlk ay için iyi gelir.</li>'
                    .'<li><strong>İnce bir hırka</strong> — kulağa saçma geliyor ama AVM, ofis ve sinemalarda klima çok güçlü; '
                    .'"klima üşümesi" Dubai\'de gerçek bir şey.</li>'
                    .'</ul>',
                'right' => '<p><strong>Orada almak daha mantıklı</strong></p>'
                    .'<ul>'
                    .'<li><strong>Elektrikli her şey</strong> — BAE prizleri İngiliz tipi 3 uçlu (Type G), şebeke 230V. '
                    .'Türkiye\'den getirdiğin her cihaz adaptör ister; küçük ev aletlerini orada (ve bolca ikinci el) almak daha pratik.</li>'
                    .'<li><strong>Yorgan, yastık, havlu</strong> — valizde hacim kaplar, orada her bütçeye var.</li>'
                    .'<li><strong>Karartma perdesi</strong> — yaz güneşi sabah 5\'te tavan yapar; kiralık dairelerin çoğunda perde yoktur. '
                    .'İlk hafta alacağın en hayat kurtarıcı şeylerden biri.</li>'
                    .'<li><strong>Mutfak takımı</strong> — tencere, tabak, çatal-bıçak. İkinci el piyasası çok canlı (aşağıda).</li>'
                    .'</ul>',
            ]],

            ['type' => 'baslik', 'data' => ['level' => 'h2', 'text' => 'İlk ay: ev kurma listesi']],

            ['type' => 'metin', 'data' => ['content' => '<p>Dubai\'de kiralık dairelerin önemli kısmı <em>eşyasız</em> (unfurnished) çıkar '
                .'ve bazılarında buzdolabı, ocak, çamaşır makinesi bile yoktur — ilana bakarken '
                .'"furnished / semi-furnished / unfurnished" ayrımını mutlaka kontrol et. Öncelik sırasıyla:</p>'
                .'<ol>'
                .'<li><strong>Uyku</strong> — yatak, nevresim, yastık. Yatak ölçülerinde farklı standartlar (İngiliz/Amerikan) bir arada '
                .'kullanılıyor; nevresim almadan önce yatağı ölç.</li>'
                .'<li><strong>Klima</strong> — Dubai\'de yaşamın merkezi. Taşınmadan önce her odada çalıştığını kontrol et; '
                .'filtre temizliği ihmal edilmiş dairede ilk servis işini baştan planla.</li>'
                .'<li><strong>Mutfak</strong> — beyaz eşya eksikse önce o; sonra tencere-tava, tabak, bardak.</li>'
                .'<li><strong>Perde</strong> — özellikle karartma; yukarıda anlattık.</li>'
                .'<li><strong>Temizlik</strong> — deterjan, bez, çöp poşeti, süpürge.</li>'
                .'</ol>'
                .'<p><strong>Zamanlama:</strong> Haziran-Eylül arasında taşınma ve eşya teslimini öğle saatlerine koyma — '
                .'sabah erken ya da akşam planla. <strong>Ve acele etme:</strong> Dubai bir geçiş şehri; her hafta birileri '
                .'şehirden ayrılıyor ve ev dolusu eşya çıkarıyor. Birkaç hafta beklemeyi bilen, aynı eşyayı yarı fiyata kurar.</p>',
            ]],

            ['type' => 'baslik', 'data' => ['level' => 'h2', 'text' => 'İkinci el eşya nereden bulunur?']],

            ['type' => 'metin', 'data' => ['content' => '<p>Dubai\'de ikinci el piyasası, şehrin sürekli giriş-çıkışı sayesinde çok canlıdır:</p>'
                .'<ul>'
                .'<li><strong>Dubizzle</strong> — bölgenin en yerleşik ilan platformu; en geniş arz burada.</li>'
                .'<li><strong>Facebook Marketplace ve mahalle grupları</strong> — semt bazlı gruplarda özellikle mobilya bol.</li>'
                .'<li><strong>"Leaving sale" (çıkış satışları)</strong> — şehirden ayrılan expatlar ev dolusu eşyayı toplu ve ucuza satar. '
                .'Kaliteli eşyanın en iyi kaynağı; ilanlarda ve gruplarda bu ifadeyi ara.</li>'
                .'<li><strong>İkinci el ve konsinye dükkânları</strong> — Karama ve Satwa çevresi klasik adresler; '
                .'mobilya ve beyaz eşyada pazarlık payı vardır.</li>'
                .'<li><strong>Türk toplulukları</strong> — WhatsApp ve Facebook grupları. Kendi dilinde anlaşmak, '
                .'özellikle ilk aylarda, işi ciddi biçimde kolaylaştırır.</li>'
                .'</ul>',
            ]],

            ['type' => 'baslik', 'data' => ['level' => 'h2', 'text' => 'Ödeme yaparken: dolandırıcılıktan korunma']],

            ['type' => 'metin', 'data' => ['content' => '<p>Yeni gelenler, ikinci el dolandırıcılığının birinci hedefidir: '
                .'şehri tanımayan, acele eden, kimseyi tanımayan biri. Birkaç kural neredeyse tüm riski ortadan kaldırır:</p>'
                .'<ul>'
                .'<li><strong>Görmeden ödeme yapma.</strong> Dubai\'de ikinci elde elden teslim ve elden ödeme hâlâ en yaygın '
                .'ve en güvenli yol. "Kargoyla göndereyim, önce parayı at" diyen kişiyle işin olmasın — aynı şehirdeyseniz '
                .'buluşmamak için hiçbir geçerli sebep yok.</li>'
                .'<li><strong>Banka havalesi geri alınamaz.</strong> Tanımadığın birine havale yapmak, nakit verip fiş almamakla aynı şeydir.</li>'
                .'<li><strong>Konuşmayı platform dışına taşımaya ısrar eden kişiye dikkat et.</strong> Sorun çıktığında elinde kayıt kalmaz.</li>'
                .'<li><strong>Fiyat çok iyiyse bir sebebi vardır.</strong> Piyasanın çok altındaki ilan, çoğu zaman ilan değildir.</li>'
                .'<li><strong>Buluşma yeri:</strong> AVM girişi, metro istasyonu gibi kalabalık ve kameralı yerleri seç.</li>'
                .'</ul>',
            ]],

            ['type' => 'ayrac', 'data' => []],

            ['type' => 'baslik', 'data' => ['level' => 'h2', 'text' => 'Şehirden ayrılıyorsan']],

            ['type' => 'metin', 'data' => ['content' => '<p>Bu rehberin diğer yarısı sana: <strong>giderken eşyanı çöpe atma, '
                .'yok pahasına da elden çıkarma.</strong></p>'
                .'<p>Dubai\'de herkes bir gün taşınır — sözleşme biter, iş değişir, ülke değişir. '
                .'Elinde kalan mobilya, beyaz eşya ve mutfak takımı, tam da yeni gelen birinin aradığı şeylerdir. '
                .'Atmak yerine devretmek hem çevre hem cüzdan hem de arkadan gelen için doğru olan.</p>'
                .'<p>Nisoya\'da ilan vermek ücretsizdir; komisyon yoktur, üyelik ücreti yoktur. '
                .'Eşyanı ister sat, ister ücretsiz devret — ikisi de aynı şekilde ilan edilir.</p>'
                .'<p><em>Dürüst olalım:</em> Nisoya yeni bir platform ve Körfez tarafı henüz yeni açılıyor. '
                .'Yani burada "hazır bir pazar var, gelin alın" demiyoruz. '
                .'Tam tersi: <strong>ilk ilanları koyanlar, yeni gelenler için o pazarı kuranlar olacak.</strong></p>',
            ]],

            ['type' => 'cta', 'data' => [
                'title' => 'Eşyanı ücretsiz listele, arkandan gelene bırak',
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
