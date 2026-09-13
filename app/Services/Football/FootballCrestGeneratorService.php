<?php

declare(strict_types=1);

namespace App\Services\Football;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FootballCrestGeneratorService
{
    /**
     * Sembol seçenekleri.
     *
     * @return array<string, string>
     */
    public static function getAvailableSymbols(): array
    {
        return [
            'kartal' => 'Kartal (Yırtıcı / Güçlü)',
            'aslan' => 'Aslan (Lider / Cesur)',
            'kurt' => 'Bozkurt (Birlik / Hızlı)',
            'boga' => 'Boğa (Dirençli / Yılmaz)',
            'simsek' => 'Şimşek (Patlayıcı / Tempolu)',
            'ates' => 'Alev (Tutkulu / Ateşli)',
            'hilal' => 'Hilal & Yıldız (Geleneksel / Asil)',
            'tac' => 'Kraliyet Tacı (Şampiyonluk Hedefi)',
            'yildiz' => 'Yıldız (Yıldızlar Topluluğu)',
            'top' => 'Klasik Futbol Topu (Saf Futbol)',
        ];
    }

    /**
     * Kalkan stil seçenekleri.
     *
     * @return array<string, string>
     */
    public static function getAvailableStyles(): array
    {
        return [
            'klasik_kalkan' => 'EA FC Klasik Kalkan',
            'modern_elmas' => 'Modern Elmas Kesim',
            'avrupa_zirve' => 'Avrupa Zirve Kalkanı',
            'altin_daire' => 'Altın Çerçeveli Dairesel Rozet',
        ];
    }

    /**
     * Takım adı, şehir, forma renkleri ve sembole göre profesyonel EA FC tarzı SVG arma üretir ve kaydeder.
     *
     * @return array{
     *     path: string,
     *     url: string,
     *     svg: string,
     *     initials: string,
     *     colors: array{primary: string, secondary: string, accent: string}
     * }
     */
    public function generateAndStore(
        string $teamName,
        string $city,
        ?string $primaryColor = null,
        ?string $secondaryColor = null,
        string $symbol = 'kartal',
        string $style = 'klasik_kalkan',
    ): array {
        $colors = $this->resolveColors($primaryColor, $secondaryColor);
        $initials = $this->extractInitials($teamName);
        $svg = $this->buildSvg($teamName, $city, $initials, $colors, $symbol, $style);

        $slug = Str::slug($teamName ?: 'takim');
        $random = Str::random(6);
        $filename = "football/teams/{$slug}-crest-{$random}.svg";

        Storage::disk('public')->put($filename, $svg);
        $url = Storage::disk('public')->url($filename);

        return [
            'path' => $filename,
            'url' => $url,
            'svg' => $svg,
            'initials' => $initials,
            'colors' => $colors,
        ];
    }

    /**
     * Renk metnini veya hex kodunu çözer.
     *
     * @return array{primary: string, secondary: string, accent: string}
     */
    public function resolveColors(?string $primary, ?string $secondary): array
    {
        $colorMap = [
            'kirmizi' => '#dc2626',
            'beyaz' => '#f8fafc',
            'siyah' => '#0f172a',
            'mavi' => '#2563eb',
            'sari' => '#eab308',
            'yesil' => '#16a34a',
            'lacivert' => '#1e3a8a',
            'bordo' => '#881337',
            'turuncu' => '#ea580c',
            'mor' => '#7c3aed',
            'gumus' => '#94a3b8',
            'altin' => '#f59e0b',
            'turkuaz' => '#06b6d4',
            'antrasit' => '#334155',
        ];

        $parseColor = function (?string $val, string $fallback) use ($colorMap): string {
            if (empty($val)) {
                return $fallback;
            }
            $clean = trim($val);
            if (str_starts_with($clean, '#') && (strlen($clean) === 7 || strlen($clean) === 4)) {
                return $clean;
            }
            $slug = Str::slug($clean);
            foreach ($colorMap as $name => $hex) {
                if (str_contains($slug, $name)) {
                    return $hex;
                }
            }

            return $fallback;
        };

        $p = $parseColor($primary, '#059669'); // Varsayılan zümrüt yeşili
        $s = $parseColor($secondary, '#0f172a'); // Varsayılan gece siyahı

        // Kontrast ve zenginlik için altın/titanyum aksan rengi
        $accent = ($p === '#eab308' || $s === '#eab308') ? '#f8fafc' : '#f59e0b';

        return [
            'primary' => $p,
            'secondary' => $s,
            'accent' => $accent,
        ];
    }

    /**
     * Takım adından 2-3 harflik EA FC kulüp monogramı çıkarır.
     */
    public function extractInitials(string $name): string
    {
        $clean = trim(preg_replace('/[^a-zA-Z0-9\sğüşıöçĞÜŞİÖÇ]/u', '', $name) ?? '');
        if ($clean === '') {
            return 'FC';
        }

        $words = preg_split('/\s+/', $clean) ?: [];
        if (count($words) >= 3) {
            return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1).mb_substr($words[2], 0, 1));
        }
        if (count($words) === 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 2));
        }

        return mb_strtoupper(mb_substr($words[0], 0, 3));
    }

    /**
     * Vektörel EA FC tarzı SVG üretir.
     *
     * @param  array{primary: string, secondary: string, accent: string}  $colors
     */
    public function buildSvg(
        string $teamName,
        string $city,
        string $initials,
        array $colors,
        string $symbol,
        string $style,
    ): string {
        $pColor = htmlspecialchars($colors['primary'], ENT_QUOTES, 'UTF-8');
        $sColor = htmlspecialchars($colors['secondary'], ENT_QUOTES, 'UTF-8');
        $accent = htmlspecialchars($colors['accent'], ENT_QUOTES, 'UTF-8');
        $escapedName = htmlspecialchars(Str::limit(mb_strtoupper($teamName), 18), ENT_QUOTES, 'UTF-8');
        $escapedCity = htmlspecialchars(Str::limit(mb_strtoupper($city), 14), ENT_QUOTES, 'UTF-8');

        $symbolPath = $this->getSymbolSvg($symbol, $accent, $colors);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 480" width="100%" height="100%" class="ea-fc-crest">
  <defs>
    <!-- Ana Kalkan Gradyanı -->
    <linearGradient id="shieldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$pColor}" />
      <stop offset="50%" stop-color="{$pColor}" />
      <stop offset="50.1%" stop-color="{$sColor}" />
      <stop offset="100%" stop-color="{$sColor}" />
    </linearGradient>

    <!-- Metalik Altın / Titanyum Çerçeve -->
    <linearGradient id="goldBorder" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#fef08a" />
      <stop offset="25%" stop-color="#eab308" />
      <stop offset="50%" stop-color="#ca8a04" />
      <stop offset="75%" stop-color="#fef08a" />
      <stop offset="100%" stop-color="#a16207" />
    </linearGradient>

    <!-- Üst Parıltı Efekti -->
    <linearGradient id="sheen" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.3" />
      <stop offset="40%" stop-color="#ffffff" stop-opacity="0.05" />
      <stop offset="100%" stop-color="#000000" stop-opacity="0.4" />
    </linearGradient>

    <!-- Gölgelendirme -->
    <filter id="crestShadow" x="-10%" y="-10%" width="130%" height="130%">
      <feDropShadow dx="0" dy="12" stdDeviation="10" flood-color="#000000" flood-opacity="0.6" />
    </filter>
  </defs>

  <g filter="url(#crestShadow)">
    <!-- Dış Kalkan / Çerçeve -->
    <path d="M 200,20 L 360,60 C 360,250 310,380 200,450 C 90,380 40,250 40,60 Z"
          fill="url(#goldBorder)" stroke="#78350f" stroke-width="4" />

    <!-- Siyah İç Kontur -->
    <path d="M 200,32 L 346,68 C 346,242 300,366 200,432 C 100,366 54,242 54,68 Z"
          fill="#090d16" />

    <!-- Renkli İç Gövde (Takım Renkleri) -->
    <path d="M 200,42 L 336,76 C 336,236 292,354 200,418 C 108,354 64,236 64,76 Z"
          fill="url(#shieldGrad)" />

    <!-- Işık / Parlama Katmanı -->
    <path d="M 200,42 L 336,76 C 336,236 292,354 200,418 C 108,354 64,236 64,76 Z"
          fill="url(#sheen)" />

    <!-- Dekoratif Çizgiler & Kafes -->
    <line x1="200" y1="42" x2="200" y2="418" stroke="{$accent}" stroke-width="2" stroke-opacity="0.6" />

    <!-- Üst Takım Adı Bandı -->
    <rect x="70" y="80" width="260" height="38" rx="6" fill="#090d16" stroke="url(#goldBorder)" stroke-width="2" />
    <text x="200" y="105" text-anchor="middle" font-family="'Arial Black', Impact, sans-serif" font-weight="900" font-size="16" fill="#ffffff" letter-spacing="2">
      {$escapedName}
    </text>

    <!-- Merkez Sembol -->
    <g transform="translate(140, 140)">
      {$symbolPath}
    </g>

    <!-- Kulüp Monogramı / Harfleri -->
    <circle cx="200" cy="300" r="32" fill="#090d16" stroke="url(#goldBorder)" stroke-width="3" />
    <text x="200" y="311" text-anchor="middle" font-family="'Arial Black', Impact, sans-serif" font-weight="900" font-size="20" fill="{$accent}">
      {$initials}
    </text>

    <!-- Alt Şehir Bandı & Yıldızlar -->
    <text x="200" y="365" text-anchor="middle" font-family="sans-serif" font-weight="700" font-size="12" fill="#ffffff" letter-spacing="3" opacity="0.9">
      ★ {$escapedCity} ★
    </text>

    <!-- EA FC Tarzı Estetik Altın Taç Detayı -->
    <path d="M 180,390 L 190,380 L 200,395 L 210,380 L 220,390 L 215,400 L 185,400 Z" fill="url(#goldBorder)" />
  </g>
</svg>
SVG;
    }

    /**
     * Seçilen sembol için temiz, profesyonel SVG yolları üretir.
     */
    private function getSymbolSvg(string $symbol, string $accent, array $colors): string
    {
        return match ($symbol) {
            'aslan' => <<<SVG
              <circle cx="60" cy="60" r="50" fill="none" stroke="{$accent}" stroke-width="4"/>
              <path d="M 30,70 Q 60,30 90,70 Q 60,110 30,70 Z" fill="{$accent}" />
              <circle cx="48" cy="55" r="5" fill="#ffffff" />
              <circle cx="72" cy="55" r="5" fill="#ffffff" />
              <polygon points="60,65 52,78 68,78" fill="#0f172a" />
              <path d="M 40,30 Q 60,10 80,30 Q 95,45 100,70 Q 80,100 60,105 Q 40,100 20,70 Z" fill="none" stroke="{$accent}" stroke-width="3" />
SVG,
            'kurt' => <<<SVG
              <polygon points="60,15 85,55 95,100 60,85 25,100 35,55" fill="none" stroke="{$accent}" stroke-width="4" />
              <polygon points="60,25 78,55 85,90 60,78 35,90 42,55" fill="{$accent}" />
              <polygon points="50,55 42,62 50,65" fill="#ffffff" />
              <polygon points="70,55 78,62 70,65" fill="#ffffff" />
              <circle cx="60" cy="72" r="4" fill="#0f172a" />
SVG,
            'boga' => <<<SVG
              <path d="M 15,35 Q 30,15 60,40 Q 90,15 105,35 Q 85,45 75,55 L 60,75 L 45,55 Q 35,45 15,35 Z" fill="{$accent}" />
              <circle cx="60" cy="60" r="28" fill="#090d16" stroke="{$accent}" stroke-width="3" />
              <circle cx="50" cy="56" r="4" fill="#ffffff" />
              <circle cx="70" cy="56" r="4" fill="#ffffff" />
              <ellipse cx="60" cy="72" rx="10" ry="6" fill="{$accent}" />
SVG,
            'simsek' => <<<SVG
              <polygon points="68,10 32,60 58,60 48,110 88,50 62,50" fill="{$accent}" stroke="#ffffff" stroke-width="2" />
SVG,
            'ates' => <<<SVG
              <path d="M 60,10 Q 75,35 65,55 Q 85,40 85,65 Q 85,95 60,110 Q 35,95 35,65 Q 35,45 50,35 Q 45,50 55,55 Q 50,30 60,10 Z" fill="{$accent}" stroke="#ef4444" stroke-width="3" />
              <path d="M 60,45 Q 70,60 60,85 Q 50,70 60,45 Z" fill="#fef08a" />
SVG,
            'hilal' => <<<SVG
              <path d="M 65,15 A 45,45 0 1,0 65,105 A 35,35 0 1,1 65,15 Z" fill="{$accent}" />
              <polygon points="80,50 83,57 91,57 85,62 87,69 80,64 73,69 75,62 69,57 77,57" fill="#ffffff" />
SVG,
            'tac' => <<<SVG
              <polygon points="20,80 15,40 38,55 60,25 82,55 105,40 100,80" fill="{$accent}" stroke="#ffffff" stroke-width="2" />
              <rect x="20" y="82" width="80" height="10" rx="2" fill="#eab308" />
              <circle cx="15" cy="38" r="4" fill="#ffffff" />
              <circle cx="60" cy="23" r="5" fill="#ffffff" />
              <circle cx="105" cy="38" r="4" fill="#ffffff" />
SVG,
            'yildiz' => <<<SVG
              <polygon points="60,15 72,48 107,48 78,70 89,103 60,82 31,103 42,70 13,48 48,48" fill="{$accent}" stroke="#ffffff" stroke-width="3" />
              <polygon points="60,30 68,54 92,54 73,69 80,92 60,78 40,92 47,69 28,54 52,54" fill="#ffffff" />
SVG,
            'top' => <<<'SVG'
              <circle cx="60" cy="60" r="45" fill="#ffffff" stroke="#0f172a" stroke-width="3" />
              <polygon points="60,45 72,54 67,68 53,68 48,54" fill="#0f172a" />
              <line x1="60" y1="45" x2="60" y2="25" stroke="#0f172a" stroke-width="3" />
              <line x1="72" y1="54" x2="90" y2="45" stroke="#0f172a" stroke-width="3" />
              <line x1="67" y1="68" x2="80" y2="88" stroke="#0f172a" stroke-width="3" />
              <line x1="53" y1="68" x2="40" y2="88" stroke="#0f172a" stroke-width="3" />
              <line x1="48" y1="54" x2="30" y2="45" stroke="#0f172a" stroke-width="3" />
SVG,
            default => <<<SVG
              <!-- Kartal (Varsayılan) -->
              <path d="M 60,20 L 75,45 L 105,45 L 80,65 L 90,95 L 60,75 L 30,95 L 40,65 L 15,45 L 45,45 Z" fill="{$accent}" />
              <path d="M 60,35 Q 70,55 85,60 Q 60,95 60,95 Q 60,95 35,60 Q 50,55 60,35 Z" fill="#ffffff" />
              <polygon points="60,50 55,65 65,65" fill="#0f172a" />
SVG,
        };
    }
}
