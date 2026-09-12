<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Models\Category;
use App\Services\DolandiricilikTespiti;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pazaryeri ve Ticaret Yapay Zekâ Asistanı.
 *
 * İlan kalitesi, başlık/açıklama optimizasyonu, güvenlik denetimi,
 * kategori/etiket önerisi ve anlaşma uyuşmazlık analizi sunar.
 */
class MarketplaceAiAssistant
{
    public function __construct(
        private readonly AiProvider $ai,
        private readonly DolandiricilikTespiti $fraudDetector
    ) {}

    public function isConfigured(): bool
    {
        return $this->ai->isConfigured();
    }

    /**
     * İlan başlığını ve açıklamasını diaspora bağlamında profesyonelleştirir ve zenginleştirir.
     *
     * @return array{title: string, description: string}
     */
    public function improveListing(string $title, string $description, ?string $category = null, ?string $city = null): array
    {
        if ($this->isConfigured()) {
            $prompt = <<<PROMPT
Sen Nisoya (nisoya.com) platformunun ilan ve pazaryeri içerik uzmanısın.
Nisoya; Avrupa ve gurbetçi Türk topluluğunun güvenle ilan verdiği ve esnaflarla buluştuğu platformdur.

Aşağıdaki taslak ilanı diaspora alıcılarının güvenini kazanacak, net, anlaşılır ve çekici bir Türkçe ilan metnine dönüştür.
Başlık: "{$title}"
Açıklama: "{$description}"
Kategori: "{$category}"
Şehir/Konum: "{$city}"

İstenen JSON formatı:
{
  "title": "Çekici, profesyonel, konum/marka belirten başlık (maks 70 karakter)",
  "description": "Düzenli paragraflar ve maddeli liste içeren detaylı, güven veren açıklama (Durum, Özellikler, Teslimat/İletişim)"
}
Yanıtını SADECE geçerli bir JSON nesnesi olarak ver. Başka hiçbir metin veya açıklama ekleme.
PROMPT;

            $schema = [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                ],
                'required' => ['title', 'description'],
            ];

            try {
                $res = $this->ai->analyzeText($prompt, $schema, 20);
                if (is_array($res) && filled($res['title'] ?? null) && filled($res['description'] ?? null)) {
                    return [
                        'title' => trim((string) $res['title']),
                        'description' => trim((string) $res['description']),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('MarketplaceAiAssistant improveListing hatası: '.$e->getMessage());
            }
        }

        // Akıllı yedek (fallback)
        $cleanTitle = Str::headline($title);
        if ($city && ! str_contains(mb_strtolower($cleanTitle), mb_strtolower($city))) {
            $cleanTitle = "{$city} | {$cleanTitle}";
        }

        $formattedDesc = trim($description);
        if (! str_contains($formattedDesc, '•') && ! str_contains($formattedDesc, '-')) {
            $formattedDesc .= "\n\n📌 Öne Çıkan Detaylar:\n• Güvenilir ve temiz durumdadır.\n• Detaylı bilgi veya sorularınız için mesaj atabilirsiniz.";
        }

        return [
            'title' => $cleanTitle,
            'description' => $formattedDesc,
        ];
    }

    /**
     * İlanı güvenlik, dolandırıcılık şüphesi ve genel kalite açısından inceler.
     *
     * @return array{score: int, recommendation: string, summary: string, risks: array<int, string>, suggestions: array<int, string>}
     */
    public function evaluateListing(string $title, string $description, ?float $price = null, ?string $city = null): array
    {
        $risks = [];
        $suggestions = [];
        $score = 85;

        // Yerleşik sezgisel dolandırıcılık ve güvenlik denetimi
        $lower = mb_strtolower($title.' '.$description);
        if (str_contains($lower, 'nisoya güvencesi') || str_contains($lower, 'nisoya garantisi') || str_contains($lower, 'nisoya emanet')) {
            $risks[] = 'Dolandırıcılık İşareti: '.$this->fraudDetector->kategoriAdi('sahte_site_guvencesi');
            $score -= 60;
        } elseif (str_contains($lower, 'şifre') || str_contains($lower, 'doğrulama kodu') || str_contains($lower, 'kart şifresi')) {
            $risks[] = 'Dolandırıcılık İşareti: '.$this->fraudDetector->kategoriAdi('kimlik_sifre_isteme');
            $score -= 60;
        } elseif (str_contains($lower, 'kapora gönder') || str_contains($lower, 'önceden kapora')) {
            $risks[] = 'Dolandırıcılık İşareti: '.$this->fraudDetector->kategoriAdi('gormeden_kapora');
            $score -= 40;
        }

        if (mb_strlen($description) < 30) {
            $risks[] = 'Açıklama çok kısa; alıcılar için yetersiz bilgi içeriyor.';
            $suggestions[] = 'İlanın kondisyonu, kullanım durumu ve teslimat şartları eklenmelidir.';
            $score -= 20;
        }

        if ($price === null || $price <= 0) {
            $suggestions[] = 'Net bir fiyat belirtmek ilan etkileşimini %40 artırır.';
            $score -= 10;
        }

        if (blank($city)) {
            $suggestions[] = 'Şehir/bölge belirtilmesi yerel alıcıların güvenini sağlar.';
            $score -= 10;
        }

        if ($this->isConfigured() && empty($risks)) {
            $prompt = <<<PROMPT
Sen Nisoya platformunun güvenlik ve moderasyon asistanısın.
Aşağıdaki ilanı incele:
Başlık: "{$title}"
Açıklama: "{$description}"
Fiyat: "{$price}"
Şehir: "{$city}"

İlanın güvenilirlik ve kalite durumunu değerlendir.
İstenen JSON formatı:
{
  "score": 0 ile 100 arasında tamsayı puan,
  "recommendation": "onayla", "incele" veya "reddet",
  "summary": "Kısa 1-2 cümlelik moderatör özeti",
  "risks": ["varsa tespit edilen risk maddesi"],
  "suggestions": ["ilana yönelik geliştirme önerisi"]
}
Yanıtını SADECE geçerli bir JSON olarak ver.
PROMPT;

            $schema = [
                'type' => 'object',
                'properties' => [
                    'score' => ['type' => 'integer'],
                    'recommendation' => ['type' => 'string'],
                    'summary' => ['type' => 'string'],
                    'risks' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'suggestions' => ['type' => 'array', 'items' => ['type' => 'string']],
                ],
                'required' => ['score', 'recommendation', 'summary'],
            ];

            try {
                $res = $this->ai->analyzeText($prompt, $schema, 15);
                if (is_array($res) && isset($res['score'])) {
                    return [
                        'score' => max(0, min(100, (int) $res['score'])),
                        'recommendation' => in_array($res['recommendation'] ?? '', ['onayla', 'incele', 'reddet'], true)
                            ? (string) $res['recommendation']
                            : 'onayla',
                        'summary' => (string) ($res['summary'] ?? 'İlan genel yayın standartlarına uygundur.'),
                        'risks' => array_values(array_map('strval', (array) ($res['risks'] ?? []))),
                        'suggestions' => array_values(array_map('strval', (array) ($res['suggestions'] ?? []))),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('MarketplaceAiAssistant evaluateListing hatası: '.$e->getMessage());
            }
        }

        $finalScore = max(10, min(100, $score));
        $recommendation = $finalScore >= 70 ? 'onayla' : ($finalScore >= 40 ? 'incele' : 'reddet');
        $summary = match ($recommendation) {
            'onayla' => 'İlan içeriği temiz, diaspora pazaryeri yayın kurallarına uygundur.',
            'incele' => 'İlanda bazı eksik veya şüpheli ifadeler var, manuel kontrol tavsiye edilir.',
            default => 'İlan içeriğinde yüksek risk veya kural ihlali tespit edildi.',
        };

        return [
            'score' => $finalScore,
            'recommendation' => $recommendation,
            'summary' => $summary,
            'risks' => $risks,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Başlık ve açıklamaya göre en uygun kategori ve etiketleri önerir.
     *
     * @return array{category_id: int|null, category_name: string|null, tags: array<int, string>}
     */
    public function suggestCategoryAndTags(string $title, string $description): array
    {
        $categories = Category::query()->where('is_active', true)->pluck('name', 'id')->all();
        $matchedCatId = null;
        $matchedCatName = null;

        $combined = mb_strtolower($title.' '.$description);
        foreach ($categories as $id => $name) {
            $nameLower = mb_strtolower($name);
            if (str_contains($combined, $nameLower)) {
                $matchedCatId = (int) $id;
                $matchedCatName = $name;
                break;
            }
        }

        // Basit diaspora etiket çıkarıcı
        $tagPool = ['ikinciel', 'temiz', 'garantili', 'almanya', 'fransa', 'avusturya', 'turk-esnaf', 'acil', 'nakliyat', 'hizmet', 'uygun'];
        $suggestedTags = [];

        foreach ($tagPool as $tag) {
            if (str_contains($combined, $tag)) {
                $suggestedTags[] = $tag;
            }
        }

        if ($suggestedTags === []) {
            $suggestedTags = ['ikinciel', 'diaspora', 'firsat'];
        }

        return [
            'category_id' => $matchedCatId,
            'category_name' => $matchedCatName,
            'tags' => array_slice(array_unique($suggestedTags), 0, 5),
        ];
    }

    /**
     * Kategori adı için en uygun emojiyi belirler.
     */
    public function suggestCategoryEmoji(string $categoryName): string
    {
        $map = [
            'araba' => '🚗',
            'otomobil' => '🚗',
            'oto' => '🚗',
            'vasıta' => '🚙',
            'emlak' => '🏠',
            'konut' => '🏡',
            'ev' => '🏠',
            'daire' => '🏢',
            'elektronik' => '📱',
            'telefon' => '📱',
            'bilgisayar' => '💻',
            'hizmet' => '💼',
            'iş' => '💼',
            'nakliyat' => '🚚',
            'taşımacılık' => '🚛',
            'gıda' => '🥖',
            'market' => '🛒',
            'restoran' => '🍽️',
            'yemek' => '🍲',
            'tamir' => '🔧',
            'usta' => '🔨',
            'eğitim' => '📚',
            'ders' => '📖',
            'sağlık' => '🩺',
            'doktor' => '🏥',
            'giyim' => '👗',
            'moda' => '👔',
            'mobilya' => '🛋️',
            'bebek' => '👶',
            'çocuk' => '🧸',
            'spor' => '⚽',
            'hobi' => '🎨',
            'acil' => '🚨',
        ];

        $lower = mb_strtolower($categoryName);
        foreach ($map as $key => $emoji) {
            if (str_contains($lower, $key)) {
                return $emoji;
            }
        }

        return '🏷️';
    }

    /**
     * İtirazlı / sorun bildirilmiş anlaşmalarda arabuluculuk özeti üretir.
     *
     * @return array{summary: string, recommendation: string, risk_level: string}
     */
    public function analyzeDispute(
        string $disputeNote,
        string $buyerName,
        string $sellerName,
        ?string $listingTitle = null,
        ?string $amount = null
    ): array {
        if ($this->isConfigured()) {
            $prompt = <<<PROMPT
Sen Nisoya platformunun güvenli ticaret ve arabuluculuk asistanısın.
İki üye arasında gerçekleşen anlaşmada alıcı veya satıcı sorun bildirmiştir.
Alıcı: "{$buyerName}"
Satıcı: "{$sellerName}"
İlgili İlan: "{$listingTitle}"
Tutar: "{$amount}"
Sorun Bildirim Notu: "{$disputeNote}"

Bu uyuşmazlığı yönetici için tarafsızca değerlendir.
İstenen JSON formatı:
{
  "summary": "Sorunun kök sebebini ve iki tarafın durumunu açıklayan 2-3 cümlelik net özet",
  "recommendation": "Yönetici için adil ve pratik çözüm tavsiyesi",
  "risk_level": "dusuk", "orta" veya "yuksek"
}
Yanıtını SADECE geçerli bir JSON olarak ver.
PROMPT;

            $schema = [
                'type' => 'object',
                'properties' => [
                    'summary' => ['type' => 'string'],
                    'recommendation' => ['type' => 'string'],
                    'risk_level' => ['type' => 'string'],
                ],
                'required' => ['summary', 'recommendation', 'risk_level'],
            ];

            try {
                $res = $this->ai->analyzeText($prompt, $schema, 20);
                if (is_array($res) && filled($res['summary'] ?? null)) {
                    return [
                        'summary' => (string) $res['summary'],
                        'recommendation' => (string) ($res['recommendation'] ?? 'Taraflar ile iletişime geçilerek teyit alınması önerilir.'),
                        'risk_level' => (string) ($res['risk_level'] ?? 'orta'),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('MarketplaceAiAssistant analyzeDispute hatası: '.$e->getMessage());
            }
        }

        return [
            'summary' => "{$buyerName} ile {$sellerName} arasındaki anlaşmada ihtilaf bildirildi. Not: \"{$disputeNote}\"",
            'recommendation' => 'Nisoya doğrudan ödeme aracılığı yapmadığından, iki tarafın mesaj geçmişi incelenmeli ve uzlaşma sağlanamazsa anlaşma iptal edilmelidir.',
            'risk_level' => 'orta',
        ];
    }
}
