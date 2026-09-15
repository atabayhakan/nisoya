<?php

declare(strict_types=1);

namespace App\Services\Diaspora;

use App\Models\DiasporaReel;
use App\Services\Ai\CmsAiAssistant;
use App\Support\GlobalCommand\GeoNameResolver;

/**
 * Diaspora Reels içerikleri için çok modlu zekâ, otomatik konum/ülke tespiti,
 * kategori sınıflandırması ve içerik güvenlik (moderasyon) analizi servisi.
 */
class DiasporaIntelligenceService
{
    public function __construct(private readonly CmsAiAssistant $aiAssistant) {}

    /**
     * Metinden veya kullanıcı adından şehir ve ülke kodunu tespit eder.
     *
     * @return array{country_code: ?string, city: ?string}
     */
    public function detectLocation(string $text, ?string $fallbackCountry = null): array
    {
        return app(GeoNameResolver::class)->detect($text, $fallbackCountry);
    }

    /**
     * Metinden tematik kategoriyi tespit eder.
     */
    public function detectCategory(string $text): string
    {
        $normalized = mb_strtolower($text, 'UTF-8');

        // Gastronomi
        if (preg_match('/(?:^|[^\p{L}])(döner|doner|kebap|kebab|lokanta|restoran|börek|borek|baklava|lahmacun|pide|kahvaltı|kahvalti|lezzet|mutfak|fırın|firin|kasap|çiğ köfte|cig kofte|yemek|menü)/ui', $normalized) === 1) {
            return DiasporaReel::CATEGORY_GASTRONOMI;
        }

        // Spor
        if (preg_match('/(?:^|[^\p{L}])(futbol|football|maç|mac|halı saha|hali saha|turnuva|derbi|boks|güreş|gures|spor|şampiyon|sampiyon|idman|takım|takim)/ui', $normalized) === 1) {
            return DiasporaReel::CATEGORY_SPOR;
        }

        // Topluluk & Dayanışma
        if (preg_match('/(?:^|[^\p{L}])(dernek|vakıf|vakif|cami|cemevi|gençlik|genclik|kadınlar|kadinlar|dayanışma|dayanisma|topluluk|hemşehri|hemsehri|gurbetçi|gurbetci)/ui', $normalized) === 1) {
            return DiasporaReel::CATEGORY_TOPLULUK;
        }

        // Gurbet Rehberi
        if (preg_match('/(?:^|[^\p{L}])(vize|konsolosluk|pasaport|askerlik|oturum|bürokrasi|burokrasi|vergi|belediye|sigorta|ehliyet|tavsiye|haklar|vatandaşlık|vatandaslik|avukat)/ui', $normalized) === 1) {
            return DiasporaReel::CATEGORY_REHBER;
        }

        // Etkinlik & Festival
        if (preg_match('/(?:^|[^\p{L}])(festival|şenlik|senlik|konser|fuar|buluşma|bulusma|kermes|tiyatro|sahne|canlı müzik|canli muzik|kutlama|etkinlik|organizasyon|iftar|bayram)/ui', $normalized) === 1) {
            return DiasporaReel::CATEGORY_ETKINLIK;
        }

        return DiasporaReel::CATEGORY_GENEL;
    }

    /**
     * İçerik güvenliğini ve moderasyon uygunluğunu denetler (0-100 puan).
     *
     * @return array{score: int, status: string, notes: array<string>}
     */
    public function evaluateSafety(string $text): array
    {
        $normalized = mb_strtolower($text, 'UTF-8');
        $score = 100;
        $notes = [];

        // Spam & Bahis Anahtar Kelimeleri
        if (preg_match('/\b(casino|slot|rulet|bahis|kumar|bett|canlı bahis|bonus al|sweet bonanza)\b/u', $normalized) === 1) {
            $score -= 60;
            $notes[] = 'Kumar/bahis veya spam anahtar kelimesi tespit edildi.';
        }

        // Kripto & Kolay Para Dolandırıcılığı
        if (preg_match('/\b(zengin ol|kolay para|kripto sinyal|forex garantili|günlük kazanç)\b/u', $normalized) === 1) {
            $score -= 40;
            $notes[] = 'Maddi manipülasyon veya şüpheli kazanç vaadi tespit edildi.';
        }

        // Şiddet / Argo / Aşırı Siyasi Çatışma
        if (preg_match('/\b(şerefsiz|piç|terör|nefret|küfür)\b/u', $normalized) === 1) {
            $score -= 50;
            $notes[] = 'Uygunsuz veya hakaret içeren ifade tespit edildi.';
        }

        // Skor Sınırları
        $score = max(0, min(100, $score));

        $status = 'safe';
        if ($score < 60) {
            $status = 'rejected';
        } elseif ($score < 85) {
            $status = 'review_needed';
        }

        return [
            'score' => $score,
            'status' => $status,
            'notes' => $notes,
        ];
    }

    /**
     * Ham verileri analiz ederek vitrine hazır zenginleştirilmiş kayıt dizisi oluşturur.
     *
     * @param array{
     *     title?: ?string,
     *     caption?: ?string,
     *     instagram_url: string,
     *     instagram_username?: ?string,
     *     country_code?: ?string,
     *     city?: ?string,
     *     thumbnail_url?: ?string,
     *     video_url?: ?string,
     * } $data
     * @return array<string, mixed>
     */
    public function enrich(array $data): array
    {
        $fullText = trim(($data['title'] ?? '').' '.($data['caption'] ?? ''));

        // Konum ve Ülke Tespiti
        $loc = $this->detectLocation($fullText, $data['country_code'] ?? null);
        $countryCode = $loc['country_code'] ?? ($data['country_code'] ?? 'DE');
        $city = $loc['city'] ?? ($data['city'] ?? null);

        // Kategori Tespiti
        $category = $this->detectCategory($fullText);

        // Güvenlik & Moderasyon Taraması
        $safety = $this->evaluateSafety($fullText);

        // Başlık ve Açıklamayı Optimize Et
        $title = $data['title'] ?? null;
        $caption = $data['caption'] ?? null;

        // Başlık çok ham veya yoksa akıllı formatla
        if (blank($title) || mb_strlen($title) > 90 || str_starts_with($title, '#')) {
            $cleaned = preg_replace('/#[A-Za-z0-9_]+/u', '', $fullText);
            $cleaned = trim(preg_replace('/\s+/', ' ', (string) $cleaned));

            if (! empty($cleaned)) {
                $title = mb_substr($cleaned, 0, 75);
            } else {
                $title = ($city ?: 'Diaspora').' Türk Topluluğu Paylaşımı';
            }
        }

        // AI configured ve başlık/açıklama zayıfsa Claude'dan yardım al
        if ($this->aiAssistant->isConfigured() && (blank($caption) || mb_strlen((string) $caption) < 20)) {
            $story = $this->aiAssistant->generateDiasporaStory(
                (string) $title,
                $countryCode,
                $city
            );

            if ($story) {
                $title = $story['title'] ?? $title;
                $caption = $story['caption'] ?? $caption;
                $city = $city ?: ($story['suggested_city'] ?? null);
                $countryCode = $countryCode ?: ($story['country_code'] ?? 'DE');
            }
        }

        return [
            'title' => mb_substr((string) $title, 0, 120),
            'caption' => $caption ? mb_substr((string) $caption, 0, 500) : null,
            'instagram_url' => $data['instagram_url'],
            'instagram_username' => $data['instagram_username'] ?? null,
            'country_code' => $countryCode,
            'city' => $city,
            'category' => $category,
            'safety_score' => $safety['score'],
            'safety_status' => $safety['status'],
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'video_url' => $data['video_url'] ?? null,
        ];
    }
}
