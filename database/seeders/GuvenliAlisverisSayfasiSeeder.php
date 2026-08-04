<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * "Güvenli alışveriş" sayfası — ana sayfadaki güven satırının hedefi.
 *
 * ---------------------------------------------------------------------------
 * NEDEN AYRI BİR SEEDER
 *
 * Ana sayfaya "Nisoya'dan para geçmez · Dolandırılmamak için →" satırı
 * eklendi ve bağlantının bir hedefi olması gerekiyordu. Hedefsiz bağlantı,
 * tam da bu turda kaçınılması gerektiğini yazdığımız şey: tutulmayan vaat.
 *
 * İçerik UYDURULMADI: ürünün içindeki gerçek güvenlik mekanizmalarından
 * (partials/payment-safety-card, kara liste, anlaşma kaydı, doğrulanmış işlem
 * rozeti, dolandırıcılık bildirimi) türetildi. Ziyaretçi bu sayfada okuduğu
 * her şeyin karşılığını sitede bulur.
 *
 * `firstOrCreate` — sahip metni panelden düzenlerse deploy ezmez.
 * StaticPagesSeeder'ın deploy zincirinden bilinçle dışlanması kararına
 * uygun olarak bu da ELLE çalıştırılır:
 *
 *     php artisan db:seed --class=GuvenliAlisverisSayfasiSeeder --force
 */
class GuvenliAlisverisSayfasiSeeder extends Seeder
{
    public function run(): void
    {
        Page::firstOrCreate(
            ['slug' => 'guvenli-alisveris'],
            [
                'title' => 'Güvenli alışveriş — dolandırılmamak için',
                'status' => PageStatus::Yayin,
                'show_in_footer' => true,
                'sort_order' => 4,
                'meta_description' => 'Nisoya ödemeye aracılık etmez. Ödeme yaparken nelere dikkat etmelisin, '
                    .'hangi işaretler şüphelidir, sorun çıkarsa ne yaparsın.',
                'blocks' => [
                    ['type' => 'metin', 'data' => ['content' => $this->icerik()]],
                ],
            ],
        );
    }

    private function icerik(): string
    {
        return <<<'HTML'
        <h2>Önce en önemlisi: Nisoya'dan para geçmez</h2>
        <p>Nisoya bir <strong>buluşma yeri</strong>. Para tutmuyoruz, komisyon almıyoruz,
        ödemeye aracılık etmiyoruz. Anlaşma ve ödeme tamamen seninle karşı taraf arasında.</p>
        <p>Bunun iyi tarafı: kimse senden pay almıyor ve bilgilerin bir ödeme sisteminden
        geçmiyor. <strong>Dürüst tarafı ise şu:</strong> parayı biz garanti etmiyoruz.
        Bu yüzden aşağıdakiler önemli.</p>

        <h2>Ödeme yaparken beş kural</h2>
        <ol>
            <li><strong>PayPal kullanacaksan "Mal ve Hizmetler" seç.</strong>
            "Arkadaş/Aile" ile gönderilen para <strong>geri alınamaz</strong>.
            Karşı taraf ısrarla "Arkadaş/Aile" istiyorsa bu tek başına bir uyarı işaretidir.</li>

            <li><strong>Tanımadığın kişiye tutarın tamamını peşin gönderme.</strong>
            Mümkünse teslimde ya da yüz yüze öde. Kapora isteniyorsa küçük tut.</li>

            <li><strong>Acele baskısına direnç göster.</strong>
            "Başka alıcı var", "bugün son", "hemen kapora at" — bunlar düşünmeni engellemek
            için kurulmuş cümlelerdir. Gerçek satıcı beklemeyi göze alır.</li>

            <li><strong>Piyasa altı fiyattan şüphelen.</strong>
            Çok ucuz olan şey genelde ya yoktur ya da anlatıldığı gibi değildir.</li>

            <li><strong>Konuşmayı Nisoya içinde tut.</strong>
            Karşı taraf hemen WhatsApp'a ya da başka bir yere çekmek istiyorsa dikkat et.
            Site içindeki mesajlarda kayıt kalır; dışarıda kalmaz.</li>
        </ol>

        <h2>Şüpheli işaretler</h2>
        <ul>
            <li>Yeni açılmış, hiç değerlendirmesi olmayan hesap (profilde görünür).</li>
            <li>Ürünü görmeden, görüşmeden ödeme ısrarı.</li>
            <li>Kendi adına olmayan bir hesaba para isteme.</li>
            <li>Görselleri internette başka yerde de çıkan ilan.</li>
            <li>Sorulara net cevap vermeyen, konuyu değiştiren mesajlar.</li>
        </ul>

        <h2>Nisoya'nın yaptıkları</h2>
        <p>Parayı garanti etmiyoruz ama boş da durmuyoruz:</p>
        <ul>
            <li>Ödeme bilgisinin gösterildiği her yerde <strong>uyarı kartı</strong> çıkar.</li>
            <li>Dolandırıcılıkta kullanılan IBAN ve ödeme adresleri <strong>kara listeye</strong>
            alınır; aynı bilgi yeni bir hesapta tekrar kullanılamaz.</li>
            <li>Değerlendirme bırakmak için gerçekten iletişim kurmuş olmak gerekir —
            <strong>sahte puanla güven kazanılamaz</strong>.</li>
            <li>Tamamlanmış anlaşmaya dayanan yorumlar <strong>"Doğrulanmış işlem"</strong>
            rozeti taşır.</li>
            <li>İlan görselleri otomatik kontrolden geçer; konum verisi temizlenir.</li>
        </ul>

        <h2>Bir sorun yaşadıysan</h2>
        <p>Karşı tarafın profilindeki <strong>"Bu kullanıcıyı dolandırıcılık için bildir"</strong>
        bağlantısını kullan. Bildirimin gizli tutulur ve incelenir. Ödeme detayını, mesajları
        ve tarihi yazarsan inceleme çok daha hızlı olur.</p>
        <p>Para kaybettiysen ayrıca bulunduğun ülkenin <strong>polisine</strong> ve varsa
        <strong>ödeme sağlayıcına</strong> (PayPal, banka) bildir. Nisoya'ya bildirmen o
        hesabın başkalarını dolandırmasını engeller ama paranı geri getirmez —
        bu yüzden yukarıdaki beş kural önemli.</p>
        HTML;
    }
}
