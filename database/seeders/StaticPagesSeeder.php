<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Database\Seeder;

class StaticPagesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $page) {
            Page::query()->firstOrCreate(['slug' => $page['slug']], $page);
        }
    }

    /** @return array<int, array<string, mixed>> */
    protected function pages(): array
    {
        return [
            [
                'slug' => 'hakkimizda',
                'title' => 'Hakkımızda',
                'status' => PageStatus::Yayin->value,
                'show_in_footer' => true,
                'sort_order' => 1,
                'meta_description' => 'Nisoya, yurt dışındaki Türklerin yetenek ve hizmet pazaryeri.',
                'blocks' => [
                    ['type' => 'metin', 'data' => ['content' => '<p><strong>Nisoya</strong> — "Ne İş Olursa Yaparım" — yurt dışında yaşayan Türklerin kendi aralarında yetenek, hizmet ve emeklerini paylaştığı bir pazaryeridir.</p><p>Yabancı bir ülkede yaşarken güvendiğin birinden hizmet almak, kendi dilinde anlaşmak çok değerlidir. Bir öğretmen, bir usta, bir aşçı, bir tasarımcı... Herkesin paylaşabileceği bir yeteneği var. Nisoya bu yetenekleri görünür kılar ve insanları buluşturur.</p><p>Amacımız basit: <strong>yeteneğini paraya dönüştürmek isteyenlerle, kendi insanından güvenle hizmet almak isteyenleri</strong> bir araya getirmek.</p><p>Nisoya bir aracıdır; ödeme ve anlaşmalar kullanıcılar arasında gerçekleşir. Topluluğu güvende tutmak için değerlendirme ve moderasyon sistemleri kullanırız.</p>']],
                    ['type' => 'cta', 'data' => ['title' => 'Sen de aramıza katıl', 'button_text' => 'Ücretsiz kayıt ol', 'button_url' => '/kayit']],
                ],
            ],
            [
                'slug' => 'kosullar',
                'title' => 'Kullanım Koşulları',
                'status' => PageStatus::Yayin->value,
                'show_in_footer' => true,
                'sort_order' => 2,
                'meta_description' => 'Nisoya kullanım koşulları.',
                'blocks' => [
                    ['type' => 'metin', 'data' => ['content' => '<p><em>Taslak metin (yayın öncesi hukuki inceleme önerilir).</em></p><h2>1. Platformun niteliği</h2><p>Nisoya, kullanıcıların hizmet ve ürünlerini ilan ettiği, birbirleriyle iletişim kurduğu bir aracı platformdur. Nisoya hizmetin/ürünün sağlayıcısı değildir; taraflar arasındaki anlaşma, ödeme ve ifa kullanıcıların sorumluluğundadır.</p><h2>2. Üyelik</h2><p>Üyeler doğru ve güncel bilgi vermekle yükümlüdür. Hesabın güvenliğinden üye sorumludur. Nisoya, kurallara aykırı hesapları askıya alma veya kapatma hakkını saklı tutar.</p><h2>3. İlan kuralları</h2><p>Yasa dışı, yanıltıcı, başkalarının haklarını ihlal eden veya lisans gerektiren alanlarda yetkisiz hizmet sunan ilanlar yasaktır. Nisoya bu ilanları kaldırabilir.</p><h2>4. Sorumluluk reddi</h2><p>Nisoya, kullanıcılar arasındaki işlemlerin kalitesi, güvenliği veya yasallığı konusunda garanti vermez ve doğabilecek zararlardan sorumlu tutulamaz.</p><h2>5. Değişiklikler</h2><p>Bu koşullar zaman zaman güncellenebilir. Güncel sürüm bu sayfada yayımlanır.</p>']],
                ],
            ],
            [
                'slug' => 'gizlilik',
                'title' => 'Gizlilik Politikası',
                'status' => PageStatus::Yayin->value,
                'show_in_footer' => true,
                'sort_order' => 3,
                'meta_description' => 'Nisoya gizlilik politikası: hangi verileri topluyoruz, çerezler, AdSense ve Analytics.',
                'blocks' => [
                    ['type' => 'metin', 'data' => ['content' => '<h2>1. Topladığımız Veriler</h2><ul><li><strong>Hesap bilgileri:</strong> Ad, e-posta, kullanıcı adı, ülke/şehir, profil fotoğrafı, biyografi.</li><li><strong>İlan içerikleri:</strong> Paylaştığın başlık, açıklama, görseller, fiyat ve konum bilgisi.</li><li><strong>İletişim kayıtları:</strong> Diğer kullanıcılarla yaptığın mesajlaşmalar (sistem içi).</li><li><strong>Teknik veriler:</strong> IP adresi, tarayıcı türü, cihaz bilgisi, oturum çerezi.</li><li><strong>Çerezler ve analitik:</strong> Google Analytics 4 ve AdSense tarafından toplanan anonimleştirilmiş kullanım verileri.</li></ul><h2>2. Çerezler (Cookies)</h2><p>Sitemizde aşağıdaki çerez türleri kullanılır:</p><ul><li><strong>Zorunlu çerezler:</strong> Oturum açma, dil ve tema tercihleri gibi temel işlevler için.</li><li><strong>Analitik çerezler:</strong> Google Analytics 4 — kullanıcıların siteyi nasıl kullandığını anlamak için (anonimleştirilmiş).</li><li><strong>Reklam çerezleri:</strong> Google AdSense — siteye gelir sağlayan reklamların gösterimi ve ölçümü için.</li></ul><p>Çerez tercihlerini sitemize ilk girdiğinde çıkan <em>çerez banner\'ı</em> üzerinden yönetebilirsin. Tercihini istediğin zaman değiştirebilirsin; bunun için tarayıcındaki site verilerini temizleyip yeniden siteye girdiğinde banner tekrar görünecektir.</p><h2>3. Google AdSense ve Reklamlar</h2><p>Nisoya, gelirini <strong>Google AdSense</strong> reklamlarından elde eder ve hizmeti uzun süre <strong>ücretsiz</strong> sunmayı hedefler.</p><p>Google, AdSense aracılığıyla ilgi alanına dayalı reklamlar gösterebilir. Bu süreçte Google\'ın kendi çerezleri devreye girer. Detaylı bilgi için <a href="https://policies.google.com/technologies/ads" target="_blank" rel="noopener noreferrer">Google\'ın reklam politikasını</a> inceleyebilirsin. Reklam kişiselleştirmesini kapatmak için <a href="https://adssettings.google.com/" target="_blank" rel="noopener noreferrer">Google Ads Ayarları</a>\'nı kullanabilirsin.</p><h2>4. Google Analytics 4</h2><p>Site trafiğini ölçmek için <strong>Google Analytics 4</strong> kullanıyoruz. IP adresleri <em>anonimleştirilmiş</em> olarak işlenir (anonymize_ip: true). Toplanan veriler: sayfa görüntülemeleri, oturum süresi, cihaz ve tarayıcı türü, trafik kaynağı. Bu veriler hiçbir kişisel bilgiyle eşleştirilmez.</p><h2>5. Verilerin Kullanım Amacı</h2><ul><li>Hizmetin sağlanması ve kişiselleştirilmesi.</li><li>İlanlar arasında arama ve eşleştirme.</li><li>Spam, dolandırıcılık ve kötüye kullanımın önlenmesi.</li><li>Yasal yükümlülüklerin yerine getirilmesi.</li><li>Anonimleştirilmiş analitik ve reklam optimizasyonu.</li></ul><h2>6. Hakların</h2><p>6698 sayılı <strong>KVKK</strong> kapsamında aşağıdaki haklara sahipsin:</p><ul><li>Kişisel verilerinin işlenip işlenmediğini öğrenme.</li><li>İşlenmişse buna ilişkin bilgi talep etme.</li><li>İşlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme.</li><li>Yurt içinde/dışında aktarıldığı 3. kişileri öğrenme.</li><li>Eksik/yanlış işlenen verilerin düzeltilmesini isteme.</li><li>Şartlar oluştuğunda silinmesini/yok edilmesini isteme.</li></ul><p>Bu haklarını kullanmak için <a href="/iletisim">iletişim</a> sayfasındaki kanallardan bize ulaşabilirsin.</p><h2>7. Verilerin Saklanma Süresi</h2><p>Hesap bilgilerin, hesabın aktif olduğu sürece saklanır. Hesabını sildiğinde verilerin makul bir süre içinde (yasal yükümlülükler hariç) sistemden kaldırılır. Çerez tercihlerin 12 ay boyunca tarayıcında saklanır.</p><h2>8. Politika Değişiklikleri</h2><p>Bu politikayı zaman zaman güncelleyebiliriz. Önemli değişikliklerde sitemizde duyuru yayınlarız. Güncel versiyon her zaman bu sayfada yer alır.</p><h2>9. İletişim</h2><p>Soruların için <a href="/iletisim">İletişim</a> sayfasını kullanabilirsin.</p>']],
                ],
            ],
            [
                'slug' => 'sss',
                'title' => 'Sıkça Sorulan Sorular',
                'status' => PageStatus::Yayin->value,
                'show_in_footer' => true,
                'sort_order' => 4,
                'meta_description' => 'Nisoya hakkında sıkça sorulan sorular.',
                'blocks' => [
                    ['type' => 'metin', 'data' => ['content' => '<h3>Nisoya ücretli mi?</h3><p>Hayır, kayıt olmak ve ilan vermek tamamen ücretsiz. İleride isteğe bağlı öne çıkarma seçenekleri eklenebilir.</p><h3>Ödeme Nisoya üzerinden mi yapılıyor?</h3><p>Hayır. Nisoya bir ilan ve iletişim platformudur. Ödeme ve anlaşma doğrudan kullanıcılar arasında yapılır.</p><h3>Türkiye\'den kullanabilir miyim?</h3><p>Nisoya yurt dışında yaşayan Türklere yöneliktir ve Türk Lirası kullanmaz. Fiyatlar bulunduğun ülkenin para biriminde gösterilir.</p><h3>Bir ilana nasıl güvenirim?</h3><p>Satıcının profilini, değerlendirmelerini ve puanını incele. Şüpheli durumda "şikayet et" özelliğini kullan.</p><h3>İlanım neden görünmüyor?</h3><p>İlanlar genelde anında yayınlanır. Kurallara aykırı bulunan ilanlar yöneticiler tarafından pasifleştirilebilir.</p>']],
                ],
            ],
        ];
    }
}
