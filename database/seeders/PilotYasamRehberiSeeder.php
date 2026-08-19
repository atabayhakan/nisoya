<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Pilot — "pratik yaşam" rehberi (docs/03-buyume-fikirleri.md, 2026-08-19,
 * öneri 2). Rehber'in bugünkü veri modeli (Temsilcilik×IşlemTürü) bu içeriğe
 * uymuyor: belirli bir konsolosluğa değil ülke geneline bağlı bir konu. Bu
 * yüzden Rehber tablolarına değil, var olan `Page` (CMS) modeline eklendi —
 * yeni tablo/migration yok, tam da pilotun amacı bu (bkz. tasarım gerekçesi).
 *
 * TASLAK doğar — YAYINA ALINMAZ. Sahip admin panelden (/yonetim → Sayfalar)
 * gözden geçirip kendisi yayına alır (K7'nin aynısı: insan doğrulaması
 * olmadan içerik yayına çıkmaz).
 *
 * KAYNAKLAR (2026-08-19 doğrulandı, WebFetch ile birebir okundu):
 * - CFPB: consumerfinance.gov/ask-cfpb/can-i-get-a-checking-account-without-a-social-security-number-or-drivers-license-en-929/
 * - IRS ITIN başvuru süreci: irs.gov/tin/itin/how-to-apply-for-an-itin
 * - Wells Fargo Clear Access Banking kimlik şartları: wellsfargo.com/checking/clear-access-banking/identification-required-to-open/
 *
 * `ReferenceDataSeeder`'a BİLEREK eklenmedi (StaticPagesSeeder'la aynı sınıf —
 * içerik seeder'ları deploy'da otomatik koşmaz). Sahip onaylarsa sunucuda
 * elle çalıştırılır: php artisan db:seed --class=PilotYasamRehberiSeeder --force
 */
class PilotYasamRehberiSeeder extends Seeder
{
    public function run(): void
    {
        Page::query()->firstOrCreate(['slug' => 'abd-ssn-siz-banka-hesabi-acma'], [
            'title' => "ABD'de SSN'siz Banka Hesabı Açma",
            'status' => PageStatus::Taslak->value,
            'show_in_footer' => false,
            'sort_order' => 100,
            'meta_description' => "Amerika'da Sosyal Güvenlik Numaran (SSN) henüz yokken banka hesabı açmak mümkün mü? ITIN süreci, gerçek bir bankada istenen belgeler ve online/şube farkı.",
            'blocks' => [
                ['type' => 'metin', 'data' => ['content' => $this->icerik()]],
            ],
        ]);
    }

    private function icerik(): string
    {
        return implode('', [
            '<h2>Kısa cevap: evet, mümkün</h2>',
            '<p>ABD Tüketici Mali Koruma Bürosu\'nun (CFPB) kendi ifadesiyle: '
                .'<strong>"Banka veya kredi birliği hesabı açmak için Sosyal Güvenlik Numarasına ihtiyacın yok."</strong> '
                .'Yani SSN\'in henüz yoksa — göçmenlik sürecinin başındaysan, öğrenci vizesiyle yeni geldiysen ya da '
                .'SSN başvurun sonuçlanmadıysa — banka hesabı açman yasal olarak mümkün.</p>',

            '<h2>SSN yerine ne kullanabilirsin?</h2>',
            '<p>Bankalar kimliğini doğrulamak için bir numara ister; bu her zaman SSN olmak zorunda değil. En yaygın '
                .'alternatif <strong>ITIN</strong> (Bireysel Vergi Mükellefi Kimlik Numarası) — IRS\'in (ABD Gelir '
                .'İdaresi) SSN alamayan kişilere verdiği vergi kimlik numarası.</p>',
            '<p>ITIN başvurusu <strong>Form W-7</strong> ile yapılır, iki yoldan biriyle: postayla (IRS\'in Austin '
                .'Service Center\'ına) ya da ABD içindeki bir IRS Vergi Mükellefi Yardım Merkezi\'nde şahsen ve '
                .'ücretsiz. Başvuru sonucu genelde <strong>7 hafta</strong>da gelir; yoğun vergi sezonunda (15 '
                .'Ocak–30 Nisan) ya da yurt dışından yapılan başvurularda <strong>9-11 hafta</strong>ya kadar '
                .'sürebilir. Güncel süreci '
                .'<a href="https://www.irs.gov/tin/itin/how-to-apply-for-an-itin" target="_blank" rel="noopener nofollow">IRS\'in kendi sayfasından</a> '
                .'takip edebilirsin.</p>',

            '<h2>Bir bankada gerçekte ne isteniyor</h2>',
            '<p>Örnek olarak Wells Fargo\'nun temel hesabı (Clear Access Banking) üzerinden bakalım — büyük '
                .'bankaların çoğunda tablo benzer:</p>',
            '<ul>'
                .'<li><strong>Kimlik numarası:</strong> SSN ya da ITIN.</li>'
                .'<li><strong>Birincil kimlik:</strong> pasaport bu kategoride geçerli.</li>'
                .'<li><strong>İkincil kimlik:</strong> banka/kredi kartı, öğrenci kimliği ya da başka resmî bir belge.</li>'
                .'</ul>',
            '<p><strong>Önemli ayrım — internetten mi, şubeden mi:</strong> hesabı internetten açmak istersen 18 '
                .'yaş üstü olman, ABD\'de bir telefon numaran VE fiziksel bir ABD adresin olması gerekiyor. Yeni '
                .'gelmiş biri için bu üçü henüz yoksa gerçekçi yol internetten değil <strong>şubeye giderek açmak</strong> '
                .'— orada yaş sınırı yok, yalnız iki kimlik belgesi ve adres kanıtı (kira sözleşmesi, fatura vb.) '
                .'isteniyor. Kaynak: '
                .'<a href="https://www.wellsfargo.com/checking/clear-access-banking/identification-required-to-open/" target="_blank" rel="noopener nofollow">Wells Fargo\'nun kendi sayfası</a>.</p>',

            '<h2>Dürüst uyarı</h2>',
            '<p>Her bankanın, hatta aynı bankanın farklı şubelerinin uygulaması küçük farklar gösterebilir. Gideceğin '
                .'şubeyi önceden arayıp "ITIN/pasaportla hesap açabilir miyim, hangi belgeleri getirmeliyim" diye '
                .'sormak, boşuna gidip geri dönmekten iyidir.</p>',

            '<p><em>Bu sayfa bilgilendirme amaçlıdır; banka politikaları değişebilir. İşleme gitmeden önce ilgili '
                .'bankanın veya '
                .'<a href="https://www.consumerfinance.gov/ask-cfpb/can-i-get-a-checking-account-without-a-social-security-number-or-drivers-license-en-929/" target="_blank" rel="noopener nofollow">CFPB</a>\'nin '
                .'kendi sayfasından teyit et.</em></p>',
        ]);
    }
}
