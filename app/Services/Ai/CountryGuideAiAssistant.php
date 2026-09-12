<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Ülke ve Yaşam Rehberi Yapay Zekâ Asistanı.
 *
 * Dış temsilcilik (konsolosluk/büyükelçilik) işlem rehberleri, gerekli evraklar,
 * harç/süre bilgileri, diaspora yaşam konuları ve geri bildirim/öneri analizi sunar.
 */
class CountryGuideAiAssistant
{
    public function __construct(
        private readonly AiProvider $ai
    ) {}

    public function isConfigured(): bool
    {
        return $this->ai->isConfigured();
    }

    /**
     * Temsilcilik ve işlem türü için gerekli evraklar, süre, harç ve önemli not taslağı üretir.
     *
     * @return array{
     *     evraklar: list<array{ad: string, not: string|null}>,
     *     sure_metni: string,
     *     ucret_metni: string,
     *     notlar: string,
     *     resmi_kaynak_url: string
     * }
     */
    public function generateConsularContent(string $missionName, string $procedureName, string $countryCode): array
    {
        if ($this->isConfigured()) {
            $prompt = <<<PROMPT
Sen Türkiye Cumhuriyeti Dışişleri Bakanlığı konsolosluk işlemleri ve yurtdışı Türkler uzmanısın.
Platform: Nisoya (nisoya.com).

Görev: Aşağıdaki temsilcilik ve işlem türü için vatandaşların konsolosluğa gitmeden önce bilmesi gereken rehber içeriğini üret.
Temsilcilik: "{$missionName}"
İşlem: "{$procedureName}"
Ülke: "{$countryCode}"

İstenen JSON formatı:
{
  "evraklar": [
    {"ad": "Nüfus cüzdanı / T.C. Kimlik Kartı aslı", "not": "Fotoğraflı ve çipli olmalı"},
    {"ad": "İşleme özel belge", "not": "Gerekiyorsa apostilli ve yeminli tercümeli"}
  ],
  "sure_metni": "İşlemin tahmini süresi (örn: Aynı gün / 1-2 hafta)",
  "ucret_metni": "Tahmini harç ve posta bedeli (örn: ~45 € (2026 konsolosluk harcı))",
  "notlar": "Randevu alma zorunluluğu, fotoğrafların biyometrik standartları, mesai saatleri ve dikkat edilecek püf noktalar.",
  "resmi_kaynak_url": "https://www.konsolosluk.gov.tr veya temsilciliğin resmi web sayfası"
}
Yanıtını SADECE geçerli bir JSON nesnesi olarak ver. Başka hiçbir metin ekleme.
PROMPT;

            $schema = [
                'type' => 'object',
                'properties' => [
                    'evraklar' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'ad' => ['type' => 'string'],
                                'not' => ['type' => 'string'],
                            ],
                            'required' => ['ad'],
                        ],
                    ],
                    'sure_metni' => ['type' => 'string'],
                    'ucret_metni' => ['type' => 'string'],
                    'notlar' => ['type' => 'string'],
                    'resmi_kaynak_url' => ['type' => 'string'],
                ],
                'required' => ['evraklar', 'sure_metni', 'ucret_metni', 'notlar', 'resmi_kaynak_url'],
            ];

            try {
                $res = $this->ai->analyzeText($prompt, $schema, 20);
                if (is_array($res) && is_array($res['evraklar'] ?? null)) {
                    /** @var list<array{ad: string, not: string|null}> $evraklar */
                    $evraklar = [];
                    foreach ($res['evraklar'] as $evrak) {
                        if (is_array($evrak) && filled($evrak['ad'] ?? null)) {
                            $evraklar[] = [
                                'ad' => trim((string) $evrak['ad']),
                                'not' => filled($evrak['not'] ?? null) ? trim((string) $evrak['not']) : null,
                            ];
                        }
                    }

                    return [
                        'evraklar' => $evraklar ?: [
                            ['ad' => 'T.C. Kimlik Kartı aslı', 'not' => 'Geçerli ve fotoğraflı olmalıdır.'],
                        ],
                        'sure_metni' => trim((string) ($res['sure_metni'] ?? 'Genellikle aynı gün tamamlanır.')),
                        'ucret_metni' => trim((string) ($res['ucret_metni'] ?? 'Konsolosluk harç tarifesine tabidir.')),
                        'notlar' => trim((string) ($res['notlar'] ?? 'İşlem öncesinde konsolosluk.gov.tr üzerinden randevu alınması zorunludur.')),
                        'resmi_kaynak_url' => trim((string) ($res['resmi_kaynak_url'] ?? 'https://www.konsolosluk.gov.tr')),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('CountryGuideAiAssistant generateConsularContent hatası: '.$e->getMessage());
            }
        }

        // Akıllı varsayılan şablon (fallback)
        return [
            'evraklar' => [
                ['ad' => 'T.C. Kimlik Kartı veya Nüfus Cüzdanı aslı', 'not' => 'Fotoğraflı ve geçerli olmalıdır.'],
                ['ad' => 'Mevcut Pasaport (varsa)', 'not' => 'Süresi dolmuş olsa dahi ibraz edilmelidir.'],
                ['ad' => '2 Adet Biyometrik Fotoğraf', 'not' => 'Son 6 ay içinde çekilmiş, beyaz fonlu (50x60 mm).'],
                ['ad' => 'İkamet Belgesi (Oturum Kartı / Meldebescheinigung)', 'not' => 'Bulunduğunuz ülkedeki yasal ikametinizi gösterir belge.'],
            ],
            'sure_metni' => 'İşlem yoğunluğuna göre aynı gün veya 1-2 hafta içinde.',
            'ucret_metni' => 'Güncel konsolosluk harcı ve değerli kâğıt bedeli uygulanır.',
            'notlar' => 'Konsolosluk şubesinde işlem yaptırmadan önce konsolosluk.gov.tr adresinden randevu almanız şarttır. Randevu saatinizden 15 dakika önce tüm belgelerin asılları ve fotokopileriyle hazır bulununuz.',
            'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
        ];
    }

    /**
     * Vatandaşların "Bu bilgi güncel mi?" konsolosluk geri bildirimlerini analiz eder.
     *
     * @return array{
     *     gecerlilik: string,
     *     oneri_ozeti: string,
     *     aksiyon_onerisi: string,
     *     oncelik: string
     * }
     */
    public function evaluateConsularFeedback(string $feedbackText, string $procedureName, ?string $currentNotes = null): array
    {
        if ($this->isConfigured()) {
            $prompt = <<<PROMPT
Sen Nisoya Ülke Rehberi moderatörüsün. Bir kullanıcı konsolosluk işlem rehberiyle ilgili geri bildirimde bulundu.
İşlem: "{$procedureName}"
Mevcut Bilgi Notu: "{$currentNotes}"
Kullanıcı Geri Bildirimi: "{$feedbackText}"

Bu geri bildirimi incele:
1. Bildirim mantıklı ve geçerli bir değişiklik/güncelleme mi öneriyor?
2. Önceliği nedir (ücret, randevu kuralı veya evrak değişikliği yüksek önceliklidir)?
3. Rehberde tam olarak ne değiştirilmeli?

İstenen JSON formatı:
{
  "gecerlilik": "gecerli | supheli | bilgi_yok",
  "oneri_ozeti": "Kullanıcının neyi eleştirdiği veya önerdiğinin tek cümlelik özeti",
  "aksiyon_onerisi": "Moderatörün rehberde yapması gereken somut güncelleme talimatı",
  "oncelik": "yuksek | orta | dusuk"
}
Yanıtını SADECE geçerli JSON formatında ver.
PROMPT;

            $schema = [
                'type' => 'object',
                'properties' => [
                    'gecerlilik' => ['type' => 'string', 'enum' => ['gecerli', 'supheli', 'bilgi_yok']],
                    'oneri_ozeti' => ['type' => 'string'],
                    'aksiyon_onerisi' => ['type' => 'string'],
                    'oncelik' => ['type' => 'string', 'enum' => ['yuksek', 'orta', 'dusuk']],
                ],
                'required' => ['gecerlilik', 'oneri_ozeti', 'aksiyon_onerisi', 'oncelik'],
            ];

            try {
                $res = $this->ai->analyzeText($prompt, $schema, 20);
                if (is_array($res) && filled($res['gecerlilik'] ?? null)) {
                    return [
                        'gecerlilik' => in_array($res['gecerlilik'], ['gecerli', 'supheli', 'bilgi_yok'], true) ? (string) $res['gecerlilik'] : 'gecerli',
                        'oneri_ozeti' => trim((string) ($res['oneri_ozeti'] ?? 'Geri bildirim incelendi.')),
                        'aksiyon_onerisi' => trim((string) ($res['aksiyon_onerisi'] ?? 'İçerik resmî kaynaktan teyit edilerek güncellenmeli.')),
                        'oncelik' => in_array($res['oncelik'], ['yuksek', 'orta', 'dusuk'], true) ? (string) $res['oncelik'] : 'orta',
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('CountryGuideAiAssistant evaluateConsularFeedback hatası: '.$e->getMessage());
            }
        }

        // Heuristik analiz fallback
        $low = mb_strtolower($feedbackText, 'UTF-8');
        $oncelik = 'orta';
        if (str_contains($low, 'ücret') || str_contains($low, 'harç') || str_contains($low, 'euro') || str_contains($low, 'randevu') || str_contains($low, 'yanlış')) {
            $oncelik = 'yuksek';
        }

        return [
            'gecerlilik' => mb_strlen($feedbackText) > 10 ? 'gecerli' : 'bilgi_yok',
            'oneri_ozeti' => Str::limit($feedbackText, 90),
            'aksiyon_onerisi' => 'İlgili işlem içeriğinin resmi konsolosluk duyurularıyla karşılaştırılması ve doğrulanması önerilir.',
            'oncelik' => $oncelik,
        ];
    }

    /**
     * Yaşam rehberi için yapılandırılmış blok dizisi (başlık, paragraf, maddeler) üretir.
     *
     * @return array{
     *     icerik: list<array{tip: string, metin: string}>,
     *     kaynak_aciklama: string,
     *     kaynak_url: string
     * }
     */
    public function generateLifeGuideContent(string $topicTitle, string $categoryName, string $countryCode): array
    {
        if ($this->isConfigured()) {
            $prompt = <<<PROMPT
Sen Avrupa ve gurbette yaşayan Türkler için kapsamlı yaşam rehberi içerik editörüsün.
Platform: Nisoya Yaşam Rehberi (nisoya.com).

Konu: "{$topicTitle}"
Kategori: "{$categoryName}"
Hedef Ülke: "{$countryCode}"

Bu konuda gurbetçilerin haklarını, yapması gereken bürokratik/günlük adımları içeren pratik bir rehber hazırla.
İçerik iç içe olmayan DÜZ BLOKLAR halinde olmalıdır.
Her blok için "tip" ('baslik' | 'paragraf' | 'madde') ve "metin" belirtilmelidir.

İstenen JSON formatı:
{
  "icerik": [
    {"tip": "baslik", "metin": "1. Genel Bakış ve Temel Şartlar"},
    {"tip": "paragraf", "metin": "Açıklayıcı net paragraf..."},
    {"tip": "baslik", "metin": "Gereken Belgeler"},
    {"tip": "madde", "metin": "İkamet belgesi (Anmeldung)"},
    {"tip": "madde", "metin": "Vergi kimlik numarası (Steuer-ID)"},
    {"tip": "paragraf", "metin": "Dikkat edilmesi gereken önemli püf noktalar..."}
  ],
  "kaynak_aciklama": "Resmî makamlar veya ilgili mevzuat açıklaması",
  "kaynak_url": "İlgili resmi kurum web adresi"
}
Yanıtını SADECE geçerli bir JSON nesnesi olarak ver.
PROMPT;

            $schema = [
                'type' => 'object',
                'properties' => [
                    'icerik' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'tip' => ['type' => 'string', 'enum' => ['baslik', 'paragraf', 'madde']],
                                'metin' => ['type' => 'string'],
                            ],
                            'required' => ['tip', 'metin'],
                        ],
                    ],
                    'kaynak_aciklama' => ['type' => 'string'],
                    'kaynak_url' => ['type' => 'string'],
                ],
                'required' => ['icerik', 'kaynak_aciklama', 'kaynak_url'],
            ];

            try {
                $res = $this->ai->analyzeText($prompt, $schema, 25);
                if (is_array($res) && is_array($res['icerik'] ?? null)) {
                    /** @var list<array{tip: string, metin: string}> $bloklar */
                    $bloklar = [];
                    foreach ($res['icerik'] as $b) {
                        if (is_array($b) && filled($b['metin'] ?? null)) {
                            $tip = in_array($b['tip'] ?? 'paragraf', ['baslik', 'paragraf', 'madde'], true) ? (string) $b['tip'] : 'paragraf';
                            $bloklar[] = [
                                'tip' => $tip,
                                'metin' => trim((string) $b['metin']),
                            ];
                        }
                    }

                    if (! empty($bloklar)) {
                        return [
                            'icerik' => $bloklar,
                            'kaynak_aciklama' => trim((string) ($res['kaynak_aciklama'] ?? 'Resmi kamu rehberi ve yerel mevzuat.')),
                            'kaynak_url' => trim((string) ($res['kaynak_url'] ?? 'https://www.make-it-in-germany.com')),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('CountryGuideAiAssistant generateLifeGuideContent hatası: '.$e->getMessage());
            }
        }

        // Akıllı şablon (fallback)
        return [
            'icerik' => [
                ['tip' => 'baslik', 'metin' => 'Genel Bilgi ve Yasal Çerçeve'],
                ['tip' => 'paragraf', 'metin' => "{$topicTitle} işlemi, {$countryCode} ülkesinde yaşayan vatandaşlarımızın en sık karşılaştığı konulardan biridir. Sürecin sorunsuz ilerlemesi için yasal süreleri ve gerekli kayıtları zamanında tamamlamak gerekir."],
                ['tip' => 'baslik', 'metin' => 'Gerekli Başvuru Belgeleri'],
                ['tip' => 'madde', 'metin' => 'Geçerli kimlik kartı veya pasaport aslı.'],
                ['tip' => 'madde', 'metin' => 'Yerel ikamet kayıt belgesi (Adres kaydı / Anmeldung vb.).'],
                ['tip' => 'madde', 'metin' => 'İşlem türüne göre gelir belgesi veya resmi başvuru formu.'],
                ['tip' => 'baslik', 'metin' => 'Önemli Püf Noktalar'],
                ['tip' => 'paragraf', 'metin' => 'Resmi kurum randevularınızı olabildiğince erken alınız. Belge eksikliği durumunda süreç baştan başlayabileceğinden, tüm evrakların birer kopyasını da yanınızda bulundurmanız tavsiye edilir.'],
            ],
            'kaynak_aciklama' => "{$countryCode} Resmî Vatandaşlık ve Göçmenlik Bilgi Portalı",
            'kaynak_url' => 'https://www.make-it-in-germany.com',
        ];
    }

    /**
     * Kullanıcıların yaşam konusu düzeltme önerilerini değerlendirir.
     *
     * @return array{
     *     karar: string,
     *     gerekce: string,
     *     onerilen_bloklar: list<array{tip: string, metin: string}>
     * }
     */
    public function evaluateLifeTopicSuggestion(string $suggestionText, string $topicTitle): array
    {
        if ($this->isConfigured()) {
            $prompt = <<<PROMPT
Sen Nisoya Yaşam Rehberi baş editörüsün.
Bir gurbetçi kullanıcı aşağıdaki yaşam konusu için bir düzeltme/ekleme önerisinde bulundu:
Konu: "{$topicTitle}"
Kullanıcı Önerisi: "{$suggestionText}"

Bu öneriyi değerlendir:
1. Bu öneri rehberin kalitesini ve güncelliğini artırıyor mu?
2. Karar ver: "onayla", "reddet", veya "duzenle".
3. Gerekçeni açıkla.
4. Bu öneriye dayanarak rehbere eklenebilecek 1-3 blok hazırla (tip: 'madde' veya 'paragraf').

İstenen JSON formatı:
{
  "karar": "onayla | reddet | duzenle",
  "gerekce": "Karar gerekçesi açıklaması",
  "onerilen_bloklar": [
    {"tip": "madde", "metin": "Önerilen güncel bilgi..."}
  ]
}
Yanıtını SADECE geçerli bir JSON nesnesi olarak ver.
PROMPT;

            $schema = [
                'type' => 'object',
                'properties' => [
                    'karar' => ['type' => 'string', 'enum' => ['onayla', 'reddet', 'duzenle']],
                    'gerekce' => ['type' => 'string'],
                    'onerilen_bloklar' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'tip' => ['type' => 'string', 'enum' => ['baslik', 'paragraf', 'madde']],
                                'metin' => ['type' => 'string'],
                            ],
                            'required' => ['tip', 'metin'],
                        ],
                    ],
                ],
                'required' => ['karar', 'gerekce'],
            ];

            try {
                $res = $this->ai->analyzeText($prompt, $schema, 20);
                if (is_array($res) && filled($res['karar'] ?? null)) {
                    /** @var list<array{tip: string, metin: string}> $bloklar */
                    $bloklar = [];
                    foreach ($res['onerilen_bloklar'] ?? [] as $b) {
                        if (is_array($b) && filled($b['metin'] ?? null)) {
                            $bloklar[] = [
                                'tip' => in_array($b['tip'] ?? 'madde', ['baslik', 'paragraf', 'madde'], true) ? (string) $b['tip'] : 'madde',
                                'metin' => trim((string) $b['metin']),
                            ];
                        }
                    }

                    return [
                        'karar' => in_array($res['karar'], ['onayla', 'reddet', 'duzenle'], true) ? (string) $res['karar'] : 'onayla',
                        'gerekce' => trim((string) ($res['gerekce'] ?? 'Topluluk katkısı faydalı bulundu.')),
                        'onerilen_bloklar' => $bloklar,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('CountryGuideAiAssistant evaluateLifeTopicSuggestion hatası: '.$e->getMessage());
            }
        }

        // Fallback
        return [
            'karar' => mb_strlen($suggestionText) > 15 ? 'onayla' : 'duzenle',
            'gerekce' => 'Kullanıcı önerisi pratik yaşam tecrübesine dayanıyor ve rehber içeriğini zenginleştirebilir.',
            'onerilen_bloklar' => [
                ['tip' => 'madde', 'metin' => trim($suggestionText)],
            ],
        ];
    }

    /**
     * Kategori veya konu başlığına en uygun emojiyi önerir.
     */
    public function suggestEmoji(string $title): string
    {
        $low = mb_strtolower($title, 'UTF-8');

        return match (true) {
            str_contains($low, 'bank') || str_contains($low, 'finans') || str_contains($low, 'para') || str_contains($low, 'hesap') => '🏦',
            str_contains($low, 'ev') || str_contains($low, 'barınma') || str_contains($low, 'kira') || str_contains($low, 'konut') => '🏠',
            str_contains($low, 'sağlık') || str_contains($low, 'hastane') || str_contains($low, 'doktor') || str_contains($low, 'sigorta') => '🏥',
            str_contains($low, 'eğitim') || str_contains($low, 'okul') || str_contains($low, 'üniversite') || str_contains($low, 'kurs') => '🎓',
            str_contains($low, 'araç') || str_contains($low, 'araba') || str_contains($low, 'ehliyet') || str_contains($low, 'trafik') => '🚗',
            str_contains($low, 'çalışma') || str_contains($low, 'iş') || str_contains($low, 'istihdam') || str_contains($low, 'meslek') => '💼',
            str_contains($low, 'hukuk') || str_contains($low, 'avukat') || str_contains($low, 'vekalet') || str_contains($low, 'mahkeme') => '⚖️',
            str_contains($low, 'pasaport') || str_contains($low, 'vize') || str_contains($low, 'konsolosluk') || str_contains($low, 'kimlik') => '🛂',
            str_contains($low, 'vergi') || str_contains($low, 'muhasebe') => '📊',
            str_contains($low, 'askerlik') => '🎖️',
            str_contains($low, 'doğum') || str_contains($low, 'evlilik') || str_contains($low, 'nüfus') => '💍',
            default => '📘',
        };
    }
}
