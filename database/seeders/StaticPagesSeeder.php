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
                'meta_description' => 'Nisoya gizlilik politikası ve veri işleme esasları.',
                'blocks' => [
                    ['type' => 'metin', 'data' => ['content' => '<p><em>Taslak metin (GDPR uyumu için hukuki inceleme önerilir).</em></p><h2>1. Topladığımız veriler</h2><p>Hesap bilgileri (ad, e-posta, ülke/şehir), ilan içerikleri, mesajlar ve teknik kayıtlar (oturum, IP) işlenir.</p><h2>2. Kullanım amacı</h2><p>Verileri hizmeti sunmak, güvenliği sağlamak, kullanıcıları buluşturmak ve platformu geliştirmek için kullanırız. Verilerini satmayız.</p><h2>3. Çerezler</h2><p>Oturum ve tercih çerezleri kullanılır. Tarayıcı ayarlarından çerezleri yönetebilirsin.</p><h2>4. Haklarınız (GDPR)</h2><p>Avrupa Birliği\'nde yaşıyorsan; verilerine erişme, düzeltme, silme ve taşıma haklarına sahipsin. Talepler için <a href="/iletisim">bize ulaşabilirsin</a>.</p><h2>5. Veri saklama</h2><p>Veriler, hesabın aktif olduğu sürece ve yasal yükümlülükler gerektirdiği ölçüde saklanır. Hesabını silebilirsin.</p>']],
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
