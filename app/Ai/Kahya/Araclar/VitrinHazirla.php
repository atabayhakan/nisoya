<?php

namespace App\Ai\Kahya\Araclar;

use App\Services\Growth\ClaimableListingCreator;
use App\Services\Growth\WhatsAppDavetServisi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

/**
 * Kâhya Büyüme & Üye Kazanım Donanımı (Reverse Onboarding):
 * Keşfedilen işletmeler için önceden hazırlanmış sahiplenilebilir vitrin üretir.
 */
class VitrinHazirla implements Tool
{
    public function __construct(
        private readonly ClaimableListingCreator $creator,
        private readonly WhatsAppDavetServisi $whatsapp,
    ) {}

    public function name(): string
    {
        return 'vitrin-hazirla';
    }

    public function description(): Stringable|string
    {
        return 'Keşfedilen veya harici bir Türk işletmesi için Nisoya üzerinde sahiplenilebilir ön vitrin oluşturur, '
            .'fotoğrafını optimize edip ekler ve 15 saniyelik sahiplenme davet bağlantısını (/sahiplen/{token}) üretir. '
            .'Telefon varsa doğrudan WhatsApp üzerinden davet gönderme bağlantısı da sağlar. '
            .'Yeni üye kazanımında esnafa "Sizin için vitrininizi hazırladık, tek tıkla sahiplenin" '
            .'daveti iletmek için kullanılır.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $result = $this->creator->createFromData([
                'name' => (string) $request['isletme_adi'],
                'country_code' => (string) $request['ulke_kodu'],
                'city' => (string) $request['sehir'],
                'category_name' => isset($request['kategori']) ? (string) $request['kategori'] : null,
                'phone' => isset($request['telefon']) ? (string) $request['telefon'] : null,
                'email' => isset($request['eposta']) ? (string) $request['eposta'] : null,
                'website' => isset($request['web_sitesi']) ? (string) $request['web_sitesi'] : null,
                'address' => isset($request['adres']) ? (string) $request['adres'] : null,
                'description' => isset($request['aciklama']) ? (string) $request['aciklama'] : null,
                'rating' => isset($request['google_puani']) ? (float) $request['google_puani'] : null,
                'review_count' => isset($request['yorum_sayisi']) ? (int) $request['yorum_sayisi'] : null,
                'photo_reference' => isset($request['foto_referansi']) ? (string) $request['foto_referansi'] : null,
            ]);

            $listing = $result['listing'];
            $claimUrl = $result['claim_url'];
            $listingUrl = route('listings.show', [$listing->id, $listing->slug]);

            $hasPhoto = $listing->images()->exists();
            $waUrl = filled($listing->claim_phone)
                ? $this->whatsapp->listingIcinUrl($listing)
                : null;

            $output = "BAŞARILI: Sahiplenilebilir vitrin oluşturuldu.\n"
                ."- İlan No: #{$listing->id}\n"
                ."- Başlık: {$listing->title}\n"
                ."- Konum: {$listing->city} ({$listing->country_code})\n"
                ."- Sahiplenme URL: {$claimUrl}\n"
                ."- Vitrin Önizleme URL: {$listingUrl}\n"
                .($hasPhoto ? "- Kapak Görseli: Google Places fotoğrafı WebP olarak eklendi.\n" : '')
                .($waUrl ? "- WhatsApp Doğrudan Davet: {$waUrl}\n" : '')
                ."\n"
                .'Bu bağlantıyı işletme sahibine WhatsApp veya e-posta ile ulaştırabilir, ya da `hamle-oner` ile onay kuyruğuna ekleyebilirsin.';

            return $output;
        } catch (Throwable $e) {
            return 'HATA: Vitrin oluşturulamadı — '.$e->getMessage();
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'isletme_adi' => $schema->string()
                ->description('İşletmenin ticari veya tabela adı (Örn: "Öz Urfa Kebap Rotterdam").')
                ->required(),
            'ulke_kodu' => $schema->string()
                ->description('2 harfli ISO ülke kodu (Örn: "NL", "DE", "TR", "US", "FR").')
                ->required(),
            'sehir' => $schema->string()
                ->description('İşletmenin bulunduğu şehir (Örn: "Rotterdam", "Berlin", "İstanbul").')
                ->required(),
            'kategori' => $schema->string()
                ->description('İşletmenin sektörü veya kategorisi (Örn: "Restoran", "Market", "Kuaför", "Avukat").'),
            'telefon' => $schema->string()
                ->description('İşletmenin telefon veya WhatsApp numarası (uluslararası kod ile, örn: +31 10 123 4567).'),
            'eposta' => $schema->string()
                ->description('İşletmenin e-posta adresi (varsa).'),
            'web_sitesi' => $schema->string()
                ->description('İşletmenin web sitesi veya sosyal medya sayfası (varsa).'),
            'adres' => $schema->string()
                ->description('İşletmenin açık adresi.'),
            'aciklama' => $schema->string()
                ->description('İlan açıklaması (boş bırakılırsa profesyonel tanıtım metni otomatik üretilir).'),
            'google_puani' => $schema->number()
                ->description('Google Places puanı (örn: 4.8).'),
            'yorum_sayisi' => $schema->integer()
                ->description('Google Places değerlendirme sayısı (örn: 120).'),
            'foto_referansi' => $schema->string()
                ->description('Google Places fotoğraf referansı (places/.../photos/...).'),
        ];
    }
}
