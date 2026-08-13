<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Models\Listing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * İlan METNİNDE dolandırıcılık deseni tespiti — görsel moderasyonun ikizi.
 *
 * ---------------------------------------------------------------------------
 * BU PLATFORMDA NEYİN NORMAL OLDUĞU FARKLI — EN KOLAY YAPILACAK HATA BU
 *
 * Nisoya parayı HİÇ görmüyor: komisyon yok, escrow yok, ödeme satıcının kendi
 * kanalından geçiyor. Bu yüzden başka pazaryerlerinde kırmızı bayrak sayılan
 * iki şey burada TAMAMEN NORMAL:
 *
 *   - "WhatsApp'tan yazın", telefon/e-posta paylaşmak
 *   - IBAN vermek, kendi ödeme bağlantısını koymak
 *
 * Bunları işaretlemek, sitedeki dürüst satıcıların çoğunu işaretlemek olurdu.
 * İstem bunu açıkça söylüyor ve testle mühürlü.
 *
 * ---------------------------------------------------------------------------
 * İKİ SEVİYE: AĞIR VE HAFİF
 *
 * Görsel moderasyonu bir görseli işaretlediğinde ilanı Beklemede'ye alıyor.
 * Metinde aynısını yapmak daha risklidir: "kapora alınır" cümlesi Türkiye ve
 * Avrupa kiralama pratiğinde SIRADAN bir ifade. Yanlış alarm, dürüst bir ev
 * sahibinin ilanını yayından düşürür — hem de arz zaten yokken.
 *
 * Bu yüzden:
 *   AĞIR  → yayındaki ilan Beklemede'ye alınır + sahibi bilgilendirilir.
 *           (Yanlış alarmın neredeyse imkânsız olduğu kategoriler: kart/şifre
 *           istemek, sahte Nisoya güvencesi iddiası.)
 *   HAFİF → ilan yayında KALIR, yalnız panelde işaretlenir.
 *           İnsan bakar, karar verir.
 *
 * AI kapalıysa ya da çağrı başarısızsa FAIL-OPEN: hiçbir şey engellenmez.
 * Bu bir güvenlik ağı, kapı değil.
 */
class DolandiricilikTespiti
{
    /**
     * Yayındaki ilanı düşürecek kadar ağır kategoriler.
     *
     * Kısa tutuldu ve kısa kalmalı: bu listeye eklenen her kategori, yanlış
     * alarmda bir satıcının ilanının yayından kalkması demek.
     */
    public const AGIR = [
        'kimlik_sifre_isteme',
        'sahte_site_guvencesi',
    ];

    /** @var array<string, string> Panelde okunabilir karşılıkları. */
    public const KATEGORILER = [
        'kimlik_sifre_isteme' => 'Kart/şifre/doğrulama kodu isteniyor',
        'sahte_site_guvencesi' => 'Nisoya güvencesi/garantisi diye sahte iddia',
        'gormeden_kapora' => 'Görmeden/tanışmadan kapora baskısı',
        'sahte_kargo_emanet' => 'Sahte kargo veya emanet hesabı',
        'aciliyet_baskisi' => 'Yapay aciliyet + peşin ödeme baskısı',
    ];

    private const MAX_CHARS = 4000;

    public function __construct(private readonly AiProvider $ai) {}

    public function isEnabled(): bool
    {
        return (bool) config('ai.features.text_moderation') && $this->ai->isConfigured();
    }

    public function kategoriAdi(string $kategori): string
    {
        return self::KATEGORILER[$kategori] ?? $kategori;
    }

    public function agirMi(?string $kategori): bool
    {
        return $kategori !== null && in_array($kategori, self::AGIR, true);
    }

    /**
     * İlan metnini denetler.
     *
     * @return array{flagged: bool, reason: ?string}|null AI kapalı/başarısızsa null (fail-open)
     */
    public function kontrolEt(Listing $listing): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $veri = $this->ai->analyzeText($this->istem($listing), $this->sema());
        } catch (\Throwable $e) {
            Log::warning('Metin moderasyonu başarısız', [
                'listing_id' => $listing->id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }

        if (! is_array($veri) || ! array_key_exists('supheli', $veri)) {
            /*
             * FAIL-OPEN AMA SESSİZ DEĞİL.
             *
             * Canlıda bu dal her seferinde çalışıyordu (sağlayıcı JSON
             * döndürmüyordu) ve hiçbir yerde iz bırakmıyordu: moderasyon
             * tamamen ölüydü, gösterge de yoktu. Engellemiyoruz ama
             * SÖYLÜYORUZ.
             */
            Log::warning('Metin moderasyonu kullanılamaz yanıt döndü', [
                'listing_id' => $listing->id,
                'saglayici' => $this->ai->name(),
                'saglayici_hatasi' => $this->ai->lastError(),
            ]);

            return null;
        }

        $kategori = filled($veri['kategori'] ?? null) ? (string) $veri['kategori'] : null;

        // Şüpheli dendi ama kategori yok: karar dayanaksız, işaretlemeyiz.
        // (Sebebi olmayan bir işaret, paneldeki insana hiçbir şey söylemez.)
        if ((bool) $veri['supheli'] === true && $kategori === null) {
            return ['flagged' => false, 'reason' => null];
        }

        return [
            'flagged' => (bool) $veri['supheli'],
            'reason' => (bool) $veri['supheli'] ? $kategori : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'supheli' => ['type' => 'boolean'],
                'kategori' => [
                    'type' => ['string', 'null'],
                    'enum' => [...array_keys(self::KATEGORILER), null],
                ],
            ],
            'required' => ['supheli', 'kategori'],
            'additionalProperties' => false,
        ];
    }

    public function istem(Listing $listing): string
    {
        return implode("\n", [
            'Aşağıdaki ilan metnini DOLANDIRICILIK deseni açısından değerlendir.',
            '',
            'BU PLATFORMU TANI — YANLIŞ ALARM VERME:',
            'Nisoya ücretsiz bir ilan sitesidir. Parayı HİÇ görmez: komisyon almaz,',
            'ödemeye aracılık etmez. Alıcı ve satıcı doğrudan anlaşır. Bu yüzden',
            'aşağıdakiler TAMAMEN NORMALDİR ve ASLA şüpheli sayılmaz:',
            '- Telefon, WhatsApp, e-posta veya sosyal medya hesabı paylaşmak',
            '- "Bana WhatsApp\'tan yazın", "site dışından iletişim" demek',
            '- IBAN, banka hesabı ya da kendi ödeme bağlantısını vermek',
            '- Kapora/depozito İSTEMEK (kiralamada sıradan bir uygulamadır)',
            '- Peşin ödeme istemek, pazarlık payı olmadığını söylemek',
            '',
            'YALNIZ ŞU KALIPLARDA şüpheli=true yaz:',
            '- kimlik_sifre_isteme: kart numarası, CVV, banka şifresi, SMS/e-posta',
            '  doğrulama kodu ya da hesap giriş bilgisi isteniyor.',
            '- sahte_site_guvencesi: "Nisoya güvencesi", "site garantili ödeme",',
            '  "para sitede bekletilir" gibi OLMAYAN bir hizmet iddia ediliyor.',
            '- gormeden_kapora: eşyayı/evi görmeden, tanışmadan önce para',
            '  gönderilmesi dayatılıyor (ör. "yurt dışındayım, parayı yollayın,',
            '  anahtarı kargoyla göndereyim").',
            '- sahte_kargo_emanet: uydurma bir kargo/emanet şirketi üzerinden',
            '  ödeme yönlendirmesi var.',
            '- aciliyet_baskisi: yapay aciliyetle ("son 1 saat", "ilk parayı',
            '  yatıran alır") peşin ödemeye zorlanıyor.',
            '',
            'EMİN DEĞİLSEN şüpheli=false yaz. Bu bir ön-elemedir; nihai kararı',
            'bir insan verecek. Yanlış alarm, dürüst bir satıcının ilanını',
            'yayından düşürür.',
            '',
            '--- BAŞLIK ---',
            Str::limit((string) $listing->title, 250, ''),
            '',
            '--- AÇIKLAMA ---',
            Str::limit((string) $listing->description, self::MAX_CHARS, ''),
        ]);
    }
}
