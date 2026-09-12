<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Support\MailTemplates;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sistem & Araçlar Yapay Zekâ Asistanı.
 *
 * E-posta şablonu optimizasyonu (yer-tutucu güvenliği dahil), sistem hata teşhisi,
 * felaket kurtarma / güvenlik denetimi ve ülke/bölge yapılandırma desteği sağlar.
 */
class SystemToolsAiAssistant
{
    public function __construct(
        private readonly AiProvider $ai
    ) {}

    public function isConfigured(): bool
    {
        return $this->ai->isConfigured();
    }

    /**
     * E-posta şablon parçasını yer-tutucuları koruyarak optimize eder.
     *
     * @return array{
     *     optimized_text: string,
     *     explanation: string,
     *     placeholders_preserved: bool
     * }
     */
    public function optimizeEmailTemplate(
        string $templateKey,
        string $part,
        string $currentText,
        string $tone = 'profesyonel'
    ): array {
        $template = MailTemplates::TEMPLATES[$templateKey] ?? null;
        $expectedPlaceholders = $template ? array_keys($template['placeholders']) : [];
        $partLabel = MailTemplates::PART_LABELS[$part] ?? $part;

        if ($this->isConfigured()) {
            $phList = implode(', ', $expectedPlaceholders);
            $prompt = <<<PROMPT
Sen Nisoya (nisoya.com) platformu e-posta ve iletişim metinleri uzmanısın.
Görev: Aşağıda verilen e-posta şablon parçasını kullanıcıya güven veren, diaspora topluluğuna hitap eden, açık ve {$tone} bir tonda yeniden yaz.

Şablon: "{$templateKey}" ({$template['label']})
Bölüm: "{$partLabel}"
Mevcut Metin: "{$currentText}"
Zorunlu Yer-Tutucular: [{$phList}]

KRİTİK KURAL: Metinde bulunan veya şablonda zorunlu olan yer-tutucuları (örneğin {ad}, {gonderen}, {arama}, {sayi}, {konu}, {tarih}) ASLA kaldırma, değiştirmeden aynen koru.

İstenen JSON formatı:
{
  "optimized_text": "Yeniden yazılmış metin",
  "explanation": "Yapılan iyileştirmenin gerekçesi (1-2 cümle)",
  "placeholders_preserved": true
}
PROMPT;

            try {
                $response = $this->ai->analyzeText($prompt);
                if (is_array($response) && ! empty($response['optimized_text'])) {
                    $optText = (string) $response['optimized_text'];
                    $allPreserved = true;
                    foreach ($expectedPlaceholders as $ph) {
                        if (str_contains($currentText, $ph) && ! str_contains($optText, $ph)) {
                            $allPreserved = false;
                            break;
                        }
                    }

                    if ($allPreserved) {
                        return [
                            'optimized_text' => $optText,
                            'explanation' => (string) ($response['explanation'] ?? 'Metin akıcılığı ve nezaketi artırıldı.'),
                            'placeholders_preserved' => true,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('SystemToolsAiAssistant::optimizeEmailTemplate hatası: '.$e->getMessage());
            }
        }

        return $this->fallbackEmailTemplate($templateKey, $part, $currentText, $expectedPlaceholders, $tone);
    }

    /**
     * Sistem loglarındaki bir hatayı analiz edip kök neden, etki ve çözüm adımları üretir.
     *
     * @return array{
     *     severity: string,
     *     root_cause: string,
     *     impact: string,
     *     solution_steps: list<string>,
     *     prevention_advice: string
     * }
     */
    public function diagnoseSystemError(
        string $message,
        string $errorClass,
        ?string $fileLine = null,
        ?string $context = null
    ): array {
        if ($this->isConfigured()) {
            $prompt = <<<PROMPT
Sen kıdemli bir Laravel & sistem mimarısın. Nisoya platformunda meydana gelen aşağıdaki sistem hatasını analiz et.

Hata Sınıfı: "{$errorClass}"
Hata Mesajı: "{$message}"
Dosya / Satır: "{$fileLine}"
Bağlam: "{$context}"

İstenen JSON formatı:
{
  "severity": "kritik | uyari | bilgi",
  "root_cause": "Hatanın gerçek teknik kök nedeni (anlaşılır Türkçe)",
  "impact": "Kullanıcılara veya platforma etkisi",
  "solution_steps": [
    "1. Adım...",
    "2. Adım..."
  ],
  "prevention_advice": "Bu hatanın gelecekte tekrarlanmaması için mimari tavsiye"
}
PROMPT;

            try {
                $response = $this->ai->analyzeText($prompt);
                if (is_array($response) && isset($response['root_cause'], $response['solution_steps'])) {
                    return [
                        'severity' => (string) ($response['severity'] ?? 'uyari'),
                        'root_cause' => (string) $response['root_cause'],
                        'impact' => (string) ($response['impact'] ?? 'Kısmi sayfa yükleme veya işlem aksaması.'),
                        'solution_steps' => array_values(array_map('strval', (array) $response['solution_steps'])),
                        'prevention_advice' => (string) ($response['prevention_advice'] ?? 'Düzenli log izleme ve birim testleri önerilir.'),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('SystemToolsAiAssistant::diagnoseSystemError hatası: '.$e->getMessage());
            }
        }

        return $this->fallbackDiagnoseError($message, $errorClass, $fileLine);
    }

    /**
     * Yönetici ve kurtarma kiti durumunu değerlendirerek güvenlik denetim raporu üretir.
     *
     * @return array{
     *     score: int,
     *     level: string,
     *     summary: string,
     *     risks: list<string>,
     *     action_plan: list<string>
     * }
     */
    public function generateRecoveryAudit(
        int $adminCount,
        int $twoFactorCount,
        int $remainingCodes,
        bool $smtpConfigured
    ): array {
        if ($this->isConfigured()) {
            $prompt = <<<PROMPT
Sen siber güvenlik ve sistem dayanıklılığı uzmanısın. Nisoya platformu yönetim paneli için felaket kurtarma durumunu denetle.

Mevcut Metrikler:
- Aktif Yönetici Sayısı: {$adminCount}
- 2FA Kurulmuş Yönetici Sayısı: {$twoFactorCount}
- Kalan Tek Kullanımlık Kurtarma Kodu: {$remainingCodes}
- E-posta (SMTP) Çalışır Durumda mı: {$smtpConfigured}

İstenen JSON formatı:
{
  "score": 85,
  "level": "guvenli | orta | kritik",
  "summary": "Güvenlik ve kilitlenme riski özeti (1-2 cümle)",
  "risks": [
    "Tespit edilen risk maddesi 1",
    "Tespit edilen risk maddesi 2"
  ],
  "action_plan": [
    "Yapılması gereken acil eylem 1",
    "İyileştirme önerisi 2"
  ]
}
PROMPT;

            try {
                $response = $this->ai->analyzeText($prompt);
                if (is_array($response) && isset($response['score'], $response['summary'])) {
                    return [
                        'score' => (int) $response['score'],
                        'level' => (string) ($response['level'] ?? 'orta'),
                        'summary' => (string) $response['summary'],
                        'risks' => array_values(array_map('strval', (array) ($response['risks'] ?? []))),
                        'action_plan' => array_values(array_map('strval', (array) ($response['action_plan'] ?? []))),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('SystemToolsAiAssistant::generateRecoveryAudit hatası: '.$e->getMessage());
            }
        }

        return $this->fallbackRecoveryAudit($adminCount, $twoFactorCount, $remainingCodes, $smtpConfigured);
    }

    /**
     * Ülke adı veya koduna göre standart kod, Türkçe isim, bayrak emojisi ve para birimi önerir.
     *
     * @return array{
     *     code: string,
     *     name_tr: string,
     *     emoji: string,
     *     default_currency: string,
     *     dial_code: string
     * }
     */
    public function suggestCountryDetails(string $countryNameOrCode): array
    {
        $input = trim(Str::upper($countryNameOrCode));

        $known = [
            'DE' => ['code' => 'DE', 'name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'default_currency' => 'EUR', 'dial_code' => '+49'],
            'ALMANYA' => ['code' => 'DE', 'name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'default_currency' => 'EUR', 'dial_code' => '+49'],
            'FR' => ['code' => 'FR', 'name_tr' => 'Fransa', 'emoji' => '🇫🇷', 'default_currency' => 'EUR', 'dial_code' => '+33'],
            'FRANSA' => ['code' => 'FR', 'name_tr' => 'Fransa', 'emoji' => '🇫🇷', 'default_currency' => 'EUR', 'dial_code' => '+33'],
            'AT' => ['code' => 'AT', 'name_tr' => 'Avusturya', 'emoji' => '🇦🇹', 'default_currency' => 'EUR', 'dial_code' => '+43'],
            'AVUSTURYA' => ['code' => 'AT', 'name_tr' => 'Avusturya', 'emoji' => '🇦🇹', 'default_currency' => 'EUR', 'dial_code' => '+43'],
            'NL' => ['code' => 'NL', 'name_tr' => 'Hollanda', 'emoji' => '🇳🇱', 'default_currency' => 'EUR', 'dial_code' => '+31'],
            'HOLLANDA' => ['code' => 'NL', 'name_tr' => 'Hollanda', 'emoji' => '🇳🇱', 'default_currency' => 'EUR', 'dial_code' => '+31'],
            'BE' => ['code' => 'BE', 'name_tr' => 'Belçika', 'emoji' => '🇧🇪', 'default_currency' => 'EUR', 'dial_code' => '+32'],
            'BELCIKA' => ['code' => 'BE', 'name_tr' => 'Belçika', 'emoji' => '🇧🇪', 'default_currency' => 'EUR', 'dial_code' => '+32'],
            'CH' => ['code' => 'CH', 'name_tr' => 'İsviçre', 'emoji' => '🇨🇭', 'default_currency' => 'CHF', 'dial_code' => '+41'],
            'ISVICRE' => ['code' => 'CH', 'name_tr' => 'İsviçre', 'emoji' => '🇨🇭', 'default_currency' => 'CHF', 'dial_code' => '+41'],
            'GB' => ['code' => 'GB', 'name_tr' => 'Birleşik Krallık', 'emoji' => '🇬🇧', 'default_currency' => 'GBP', 'dial_code' => '+44'],
            'INGILTERE' => ['code' => 'GB', 'name_tr' => 'Birleşik Krallık', 'emoji' => '🇬🇧', 'default_currency' => 'GBP', 'dial_code' => '+44'],
            'TR' => ['code' => 'TR', 'name_tr' => 'Türkiye', 'emoji' => '🇹🇷', 'default_currency' => 'TRY', 'dial_code' => '+90'],
            'TURKIYE' => ['code' => 'TR', 'name_tr' => 'Türkiye', 'emoji' => '🇹🇷', 'default_currency' => 'TRY', 'dial_code' => '+90'],
            'US' => ['code' => 'US', 'name_tr' => 'Amerika Birleşik Devletleri', 'emoji' => '🇺🇸', 'default_currency' => 'USD', 'dial_code' => '+1'],
            'AZ' => ['code' => 'AZ', 'name_tr' => 'Azerbaycan', 'emoji' => '🇦🇿', 'default_currency' => 'AZN', 'dial_code' => '+994'],
        ];

        if (isset($known[$input])) {
            return $known[$input];
        }

        if ($this->isConfigured()) {
            $prompt = <<<PROMPT
Aşağıdaki ülke adı veya ISO kodu için bilgileri JSON olarak sağla.
Girdi: "{$countryNameOrCode}"

Format:
{
  "code": "2 harfli büyük ISO kodu (örn: SE)",
  "name_tr": "Türkçe ülke adı (örn: İsveç)",
  "emoji": "Ülke bayrak emojisi (örn: 🇸🇪)",
  "default_currency": "3 harfli para birimi kodu (örn: SEK)",
  "dial_code": "Telefon uluslararası alan kodu (örn: +46)"
}
PROMPT;

            try {
                $res = $this->ai->analyzeText($prompt);
                if (is_array($res) && ! empty($res['code']) && ! empty($res['name_tr'])) {
                    return [
                        'code' => Str::upper((string) $res['code']),
                        'name_tr' => (string) $res['name_tr'],
                        'emoji' => (string) ($res['emoji'] ?? '🌍'),
                        'default_currency' => Str::upper((string) ($res['default_currency'] ?? 'EUR')),
                        'dial_code' => (string) ($res['dial_code'] ?? ''),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('SystemToolsAiAssistant::suggestCountryDetails hatası: '.$e->getMessage());
            }
        }

        $code = Str::substr(Str::upper(Str::slug($countryNameOrCode, '')), 0, 2);

        return [
            'code' => $code !== '' ? $code : 'EU',
            'name_tr' => Str::title($countryNameOrCode),
            'emoji' => '🌍',
            'default_currency' => 'EUR',
            'dial_code' => '',
        ];
    }

    /**
     * @param  list<string>  $expectedPlaceholders
     * @return array{optimized_text: string, explanation: string, placeholders_preserved: bool}
     */
    private function fallbackEmailTemplate(
        string $templateKey,
        string $part,
        string $currentText,
        array $expectedPlaceholders,
        string $tone
    ): array {
        $clean = trim($currentText);
        $optimized = $clean;

        if ($part === 'greeting') {
            $optimized = in_array('{ad}', $expectedPlaceholders, true)
                ? 'Merhaba {ad},'
                : 'Merhaba,';
        } elseif ($part === 'outro') {
            $optimized = 'Saygılarımızla, Nisoya Ekibi — Diaspora Dayanışma Platformu';
        } elseif ($part === 'action') {
            $optimized = Str::contains($clean, 'Gör') ? 'Detayları Hemen İncele' : 'Nisoya\'da Görüntüle';
        } elseif ($part === 'subject') {
            if (! Str::startsWith($clean, 'Nisoya:')) {
                $optimized = 'Nisoya: '.$clean;
            }
        }

        foreach ($expectedPlaceholders as $ph) {
            if (str_contains($currentText, $ph) && ! str_contains($optimized, $ph)) {
                $optimized = $currentText;
                break;
            }
        }

        return [
            'optimized_text' => $optimized,
            'explanation' => 'Şablon metni diaspora topluluğu iletişim standartlarına uygun şekilde optimize edildi.',
            'placeholders_preserved' => true,
        ];
    }

    /**
     * @return array{severity: string, root_cause: string, impact: string, solution_steps: list<string>, prevention_advice: string}
     */
    private function fallbackDiagnoseError(string $message, string $errorClass, ?string $fileLine): array
    {
        $severity = 'uyari';
        $rootCause = 'Uygulama çalışma zamanı istisnası tespit edildi.';
        $steps = ['Hatanın oluştuğu dosya ve satırı inceleyin.', 'Girdi parametrelerini ve null kontrollerini doğrulayın.'];

        if (Str::contains($errorClass, ['QueryException', 'PDOException', 'Database'])) {
            $severity = 'kritik';
            $rootCause = 'Veritabanı sorgusu veya bağlantı hatası oluştu.';
            $steps = [
                'Veritabanı bağlantı durumunu ve yetkilerini kontrol edin.',
                'İlgili tablonun migration durumunu (php artisan migrate:status) doğrulayın.',
                'Sorguda geçen sütun isimlerinin şemayla eşleştiğini teyit edin.',
            ];
        } elseif (Str::contains($errorClass, ['NotFoundHttpException', 'ModelNotFoundException'])) {
            $severity = 'bilgi';
            $rootCause = 'İstenen kaynak veya model kaydı veritabanında bulunamadı.';
            $steps = [
                'İlgili URL parametresini veya ID bilgisini kontrol edin.',
                'Kaydın silinip silinmediğini (soft delete) teyit edin.',
            ];
        } elseif (Str::contains($errorClass, ['TransportException', 'Swift', 'Symfony\Component\Mailer'])) {
            $severity = 'kritik';
            $rootCause = 'E-posta (SMTP) sunucusuyla iletişim kurulamadı.';
            $steps = [
                'Yönetim panelinden "E-posta (SMTP)" sayfasındaki sunucu, port ve parola ayarlarını kontrol edin.',
                'Test e-postası göndererek sağlayıcı bağlantısını doğrulayın.',
            ];
        }

        return [
            'severity' => $severity,
            'root_cause' => $rootCause,
            'impact' => 'İlgili isteği yapan kullanıcı hata ekranı (500) ile karşılaşmış olabilir.',
            'solution_steps' => $steps,
            'prevention_advice' => 'Kritik akışlarda try-catch blokları ve kapsamlı hata loglama mekanizması kullanılması önerilir.',
        ];
    }

    /**
     * @return array{score: int, level: string, summary: string, risks: list<string>, action_plan: list<string>}
     */
    private function fallbackRecoveryAudit(
        int $adminCount,
        int $twoFactorCount,
        int $remainingCodes,
        bool $smtpConfigured
    ): array {
        $score = 100;
        $risks = [];
        $actions = [];

        if ($adminCount < 2) {
            $score -= 35;
            $risks[] = 'Tek yönetici riski: Mevcut yönetici hesabı kilitlenirse yönetim paneline erişim tamamen kaybolabilir.';
            $actions[] = 'İkinci bir yönetici hesabı oluşturun (veya kendinize ait alternatif bir e-posta ekleyin).';
        }

        if ($twoFactorCount < $adminCount) {
            $score -= 25;
            $risks[] = 'Eksik 2FA kurulumu: '.($adminCount - $twoFactorCount).' yöneticinin 2FA doğrulaması henüz aktif değil.';
            $actions[] = 'Tüm yöneticilerin iki faktörlü doğrulamayı tamamlamasını sağlayın.';
        }

        if ($remainingCodes <= 0) {
            $score -= 20;
            $risks[] = 'Kurtarma kodları tükenmiş veya okunamıyor: E-posta ve telefon erişimi kesildiğinde parola sıfırlanamaz.';
            $actions[] = 'Kurtarma Kiti sayfasından 8 adet yeni hesap kurtarma kodu üretip güvenli yerde saklayın.';
        }

        if (! $smtpConfigured) {
            $score -= 20;
            $risks[] = 'SMTP yapılandırılmamış: Şifre sıfırlama ve kritik sistem uyarı e-postaları kullanıcılara ve adminlere iletilemez.';
            $actions[] = 'E-posta (SMTP) sayfasından geçerli bir SMTP sunucusu kaydedin ve test gönderin.';
        }

        $level = $score >= 80 ? 'guvenli' : ($score >= 50 ? 'orta' : 'kritik');
        $summary = $score >= 80
            ? 'Sistem felaket kurtarma ve kimlik güvencesi yüksek dayanıklılığa sahiptir.'
            : 'Sistem kilitlenme ve felaket kurtarma senaryolarında dikkat gerektiren riskler barındırmaktadır.';

        return [
            'score' => max(0, $score),
            'level' => $level,
            'summary' => $summary,
            'risks' => $risks,
            'action_plan' => $actions,
        ];
    }
}
