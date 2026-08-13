<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Models\Listing;
use App\Models\ListingTranslation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * İlanı bulunduğu ülkenin diline çevirir.
 *
 * ---------------------------------------------------------------------------
 * NEDEN
 *
 * Almanya'daki bir Türk terzinin ilanı bugün yalnız Türkçe. O ilanı arayan
 * Alman komşu "Änderungsschneiderei" yazıyor ve hiçbir zaman bulamıyor.
 * Sayfada Almanca metin olması hem yerel aramayı hem de Türkçe bilmeyen
 * müşteriyi açar.
 *
 * ---------------------------------------------------------------------------
 * ÜÇ KURAL
 *
 * 1. ÇEVİRİR, YAZMAZ. İstem açıkça "yalnız çevir, ekleme yapma, süsleme"
 *    diyor. Modelin "bu ilanı daha çekici yazayım" demesi, satıcının
 *    vermediği bir sözü onun ağzından vermek olurdu.
 * 2. BAYAT ÇEVİRİ BASILMAZ. Satıcı metni değiştirince çeviri sessizce
 *    yanlışa döner (eski fiyat, kaldırılmış detay). Kaynak özeti tutmuyorsa
 *    çeviri gizlenir — bkz. ListingTranslation::guncelMi.
 * 3. OTOMATİK OLDUĞU YAZAR. Sayfada "otomatik çeviri" etiketi olmadan
 *    basılmaz; okuyan kişi hatayı satıcıya yüklememeli.
 *
 * ---------------------------------------------------------------------------
 * DİL HARİTASI NEDEN EKSİK BIRAKILDI
 *
 * Yalnız emin olduğumuz ülkeler var. Kazakistan/Özbekistan/Körfez gibi
 * ülkelerde ticaretin hangi dilde arandığı (yerel dil mi, Rusça mı,
 * İngilizce mi) bizim bilmediğimiz bir şey; yanlış dile çevirmek parayı
 * boşa harcamak ve kimsenin aramadığı bir metin üretmektir. O ülkelerde
 * düğme HİÇ görünmüyor. Liste büyütülebilir — ama tahminle değil.
 */
class IlanCevirmeni
{
    /**
     * Ülke → yerel dil. Yalnız emin olunan ülkeler; gerisinde özellik kapalı.
     *
     * @var array<string, string>
     */
    public const DILLER = [
        'DE' => 'de',
        'AT' => 'de',
        'CH' => 'de',   // Zürih/Basel ağırlıklı; fr/it bölgeleri kapsanmıyor.
        'NL' => 'nl',
        'BE' => 'nl',   // Türk nüfusu Flaman bölgesinde yoğun; Brüksel fr.
        'FR' => 'fr',
        'GB' => 'en',
        'US' => 'en',
        'CA' => 'en',   // Quebec fr; Türk nüfusu Toronto ağırlıklı.
        'AU' => 'en',
        'SE' => 'sv',
        'NO' => 'no',
        'DK' => 'da',
        'IT' => 'it',
        'ES' => 'es',
        'PL' => 'pl',
        'RU' => 'ru',
    ];

    /** @var array<string, string> Ekranda dilin adı. */
    public const DIL_ADLARI = [
        'de' => 'Almanca',
        'nl' => 'Felemenkçe',
        'fr' => 'Fransızca',
        'en' => 'İngilizce',
        'sv' => 'İsveççe',
        'no' => 'Norveççe',
        'da' => 'Danca',
        'it' => 'İtalyanca',
        'es' => 'İspanyolca',
        'pl' => 'Lehçe',
        'ru' => 'Rusça',
    ];

    private const MAX_CHARS = 4000;

    public function __construct(private readonly AiProvider $ai) {}

    public function isEnabled(): bool
    {
        return (bool) config('ai.features.listing_translation') && $this->ai->isConfigured();
    }

    /** İlanın ülkesine göre hedef dil; haritada yoksa null. */
    public function hedefDil(Listing $listing): ?string
    {
        return self::DILLER[strtoupper((string) $listing->country_code)] ?? null;
    }

    public function dilAdi(string $locale): string
    {
        return self::DIL_ADLARI[$locale] ?? strtoupper($locale);
    }

    /** Bu ilan çevrilebilir mi? */
    public function uygunMu(Listing $listing): bool
    {
        if (! $this->isEnabled() || $this->hedefDil($listing) === null) {
            return false;
        }

        // Çevrilecek bir şey olmalı: iki kelimelik açıklamayı çevirmek
        // maliyeti hak etmiyor ve sonuç arama trafiği getirmiyor.
        return mb_strlen(trim((string) $listing->description)) >= 40;
    }

    /**
     * Var olan çeviri GÜNCEL mi? Değilse (ya da hiç yoksa) null.
     *
     * Gösterim tarafının tek kapısı bu: bayat çeviri hiçbir yerde basılmaz.
     */
    public function guncelCeviri(Listing $listing): ?ListingTranslation
    {
        $dil = $this->hedefDil($listing);

        if ($dil === null) {
            return null;
        }

        $ceviri = $listing->translations->firstWhere('locale', $dil);

        return $ceviri && $ceviri->guncelMi($listing) ? $ceviri : null;
    }

    /**
     * Çeviriyi üretir ve kaydeder. Kapalı/başarısızsa null.
     *
     * Aynı dilde kayıt varsa ÜZERİNE yazılır — satıcı metni değiştirip
     * yeniden çevirttiğinde iki satır kalmamalı.
     */
    public function cevir(Listing $listing): ?ListingTranslation
    {
        if (! $this->uygunMu($listing)) {
            return null;
        }

        $dil = (string) $this->hedefDil($listing);

        try {
            $sonuc = $this->ai->analyzeText($this->istem($listing, $dil), $this->sema());
        } catch (\Throwable $e) {
            Log::warning('İlan çevirisi başarısız', [
                'listing_id' => $listing->id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }

        $baslik = trim((string) ($sonuc['title'] ?? ''));
        $aciklama = trim((string) ($sonuc['description'] ?? ''));

        if ($baslik === '' || $aciklama === '') {
            /*
             * BU LOG SATIRI CANLIDA EKSİKTİ VE HATAYI GÖRÜNMEZ YAPIYORDU.
             *
             * Model kullanılamaz cevap döndüğünde sessizce null dönülüyordu:
             * kullanıcı "Çeviri yapılamadı" görüyordu, günlükte hiçbir iz
             * yoktu. Özellik haftalarca ölü kalabilirdi. Sağlayıcının kendi
             * hata metni de yazılıyor — teşhisi mümkün kılan tek şey o.
             */
            Log::warning('İlan çevirisi kullanılamaz yanıt döndü', [
                'listing_id' => $listing->id,
                'saglayici' => $this->ai->name(),
                'saglayici_hatasi' => $this->ai->lastError(),
            ]);

            return null;
        }

        return ListingTranslation::updateOrCreate(
            ['listing_id' => $listing->id, 'locale' => $dil],
            [
                'title' => Str::limit($baslik, 250, ''),
                'description' => Str::limit($aciklama, 5000, ''),
                'source_hash' => ListingTranslation::kaynakOzeti($listing),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
            ],
            'required' => ['title', 'description'],
        ];
    }

    public function istem(Listing $listing, string $dil): string
    {
        $dilAdi = $this->dilAdi($dil);

        return implode("\n", [
            "Aşağıdaki Türkçe ilan metnini {$dilAdi} diline çevir.",
            '',
            'KURALLAR:',
            '- YALNIZ ÇEVİR. Ekleme yapma, süsleme, pazarlama cümlesi katma.',
            '- Satıcının VERMEDİĞİ hiçbir bilgiyi (garanti, teslimat, deneyim yılı) ekleme.',
            '- Fiyat, sayı, ölçü ve marka adlarını olduğu gibi bırak.',
            '- Telefon, e-posta, adres, sosyal medya hesabı varsa ÇEVİRME ve METNE ALMA.',
            '- Doğal ve akıcı yaz; kelime kelime çeviri yapma.',
            '- Çıktı tamamen '.$dilAdi.' olsun; Türkçe kelime bırakma.',
            '',
            '--- BAŞLIK ---',
            Str::limit((string) $listing->title, 250, ''),
            '',
            '--- AÇIKLAMA ---',
            Str::limit((string) $listing->description, self::MAX_CHARS, ''),
        ]);
    }
}
