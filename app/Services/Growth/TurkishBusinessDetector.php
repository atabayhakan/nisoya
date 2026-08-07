<?php

namespace App\Services\Growth;

use App\Contracts\AiProvider;
use App\Support\Growth\TurkishLexicon;

/**
 * Bir işletmenin Türk (Türkiye kökenli / Türk diasporası) olup olmadığını iki
 * katmanda belirler:
 *
 *  1. Deterministik ön-eleme (screen) — API'siz, ücretsiz, anlık. İşletme/kişi
 *     adı, kültürel işaretler, diakritik ve dil sinyalleri üzerinden ağırlıklı
 *     bir skor üretir. Bariz Türk / bariz değil olanları burada ayırır.
 *  2. LLM sınıflandırma (detect) — yalnızca "belirsiz" bandındakiler için
 *     çağrılır (token tasarrufu). Sağlayıcıdan bağımsız AI katmanını (OpenRouter
 *     vb.) kullanır; yapılandırılmış JSON döndürür.
 *
 * Amaç yüksek doğruluk değil, ucuz ve ölçeklenebilir bir HUNI: kesin olanları
 * otomatik geçir, sınırda olanları LLM + insan onayına bırak. Yanlış-pozitif
 * (Azerice/Balkan/diğer Türki isimler) sınırda banda düşer, körü körüne "Türk"
 * denmez.
 */
final class TurkishBusinessDetector
{
    /** Deterministik skor bu eşiği geçerse kesin Türk kabul edilir. */
    private const HIGH = 0.6;

    /** Skor bu eşiğin altındaysa kesin Türk-değil; arası "belirsiz". */
    private const LOW = 0.2;

    /** LLM çıktısı için beklenen JSON şeması. */
    private const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'turk_mu' => ['type' => 'boolean'],
            'guven' => ['type' => 'number'],
            'sinyaller' => ['type' => 'array', 'items' => ['type' => 'string']],
            'gerekce' => ['type' => 'string'],
        ],
        'required' => ['turk_mu', 'guven', 'gerekce'],
        'additionalProperties' => false,
    ];

    public function __construct(private readonly AiProvider $ai) {}

    /**
     * Hızlı, çevrimdışı, API'siz ön-eleme. Deterministik sinyallerden bir band
     * kararı verir. LLM'e hiç gitmez — toplu keşifte her aday için ucuzca çalışır.
     */
    public function screen(BusinessSignal $s): DetectionResult
    {
        [$score, $signals] = $this->deterministicScore($s);

        if ($score >= self::HIGH) {
            return new DetectionResult(true, min($score, 0.98), DetectionResult::BAND_TURKISH, $signals, 'deterministic', 'Güçlü Türk işaretleri.');
        }

        if ($score < self::LOW) {
            return new DetectionResult(false, round(1 - $score, 2), DetectionResult::BAND_NOT, $signals, 'deterministic', 'Belirgin Türk işareti yok.');
        }

        return new DetectionResult(false, round($score, 2), DetectionResult::BAND_AMBIGUOUS, $signals, 'deterministic', 'Sınırda — LLM doğrulaması gerekli.');
    }

    /**
     * Tam akış: önce ön-eleme; sonuç "belirsiz" ise (ve AI yapılandırılmışsa)
     * LLM'e sorar. Belirsiz değilse LLM'e hiç gitmez.
     */
    public function detect(BusinessSignal $s): DetectionResult
    {
        $screened = $this->screen($s);

        if ($screened->band !== DetectionResult::BAND_AMBIGUOUS) {
            return $screened;
        }

        return $this->classifyWithLlm($s, $screened);
    }

    /**
     * Deterministik ağırlıklı skor + tetiklenen sinyallerin listesi.
     *
     * @return array{0: float, 1: list<string>}
     */
    private function deterministicScore(BusinessSignal $s): array
    {
        $signals = [];
        $score = 0.0;

        $haystack = TurkishLexicon::fold($s->name.' '.($s->category ?? ''));
        $nameWords = TurkishLexicon::words($s->name.' '.($s->ownerName ?? ''));
        $allWords = TurkishLexicon::words($s->name.' '.($s->ownerName ?? '').' '.($s->category ?? ''));

        // 1) Öz-tanımlama: "Turkish" / "Türk" adı veya kategoride.
        if (in_array('turkish', $allWords, true) || in_array('turk', $allWords, true)) {
            $score += 0.6;
            $signals[] = 'öz-tanımlama (Turkish/Türk)';
        }

        /*
         * 2-3) Kültürel işaretler — KELİME FARKINDA eşleşmeyle.
         *
         * Eskiden `str_contains` ile alt-dize aranıyordu ve "Romantic"
         * (·manti·), "Londoner" (·doner·), "Rapide" (·pide·) gibi adlar tek
         * hamlede 0.6 alıp KESİN TÜRK bandına giriyordu — insan onayına bile
         * uğramadan. Gerekçesi ve üç kademe {@see TurkishLexicon::terimEslesmesi}.
         *
         * Kademeli puan bilinçli: ZAYIF eşleşme (tanımadığımız bir bileşiğin
         * parçası) adayı ELEMİYOR, yalnız tek başına eşiği geçmesini
         * engelliyor — "belirsiz" banda düşüp insan onayına gidiyor. Sistemin
         * geri kalanıyla aynı ilke: emin değilsen atma, sor.
         */
        [$token, $kademe] = TurkishLexicon::ilkEslesme($haystack, TurkishLexicon::CULTURAL_STRONG);
        if ($token !== null) {
            $tam = $kademe === TurkishLexicon::ESLESME_TAM;
            $score += $tam ? 0.6 : 0.3;
            $signals[] = ($tam ? 'güçlü işaret' : 'güçlü işaret (belirsiz bileşik)').": {$token}";
        }

        [$token, $kademe] = TurkishLexicon::ilkEslesme($haystack, TurkishLexicon::CULTURAL_WEAK);
        if ($token !== null) {
            $tam = $kademe === TurkishLexicon::ESLESME_TAM;
            $score += $tam ? 0.4 : 0.2;
            $signals[] = ($tam ? 'zayıf işaret' : 'zayıf işaret (belirsiz bileşik)').": {$token}";
        }

        // 4) Türkçe ön ad.
        foreach ($nameWords as $w) {
            if (in_array($w, TurkishLexicon::GIVEN_NAMES, true)) {
                $score += 0.35;
                $signals[] = "Türkçe ad: {$w}";
                break;
            }
        }

        // 5) Türkçe soyad.
        foreach ($nameWords as $w) {
            if (in_array($w, TurkishLexicon::SURNAMES, true)) {
                $score += 0.3;
                $signals[] = "Türkçe soyad: {$w}";
                break;
            }
        }

        // 6) Türkçe diakritik (zayıf ipucu — Almanca vb. ile örtüşebilir).
        if (TurkishLexicon::hasDiacritics($s->name.' '.($s->ownerName ?? ''))) {
            $score += 0.2;
            $signals[] = 'Türkçe diakritik (ç/ş/ğ/ı/ö/ü)';
        }

        // 7) Site/yorum dilinin Türkçe görünmesi.
        if ($this->looksTurkishLanguage($s)) {
            $score += 0.25;
            $signals[] = 'Türkçe dil (site/yorum)';
        }

        return [min($score, 1.0), $signals];
    }

    /** Site dili veya örnek yorum Türkçe'ye işaret ediyor mu? */
    private function looksTurkishLanguage(BusinessSignal $s): bool
    {
        $lang = TurkishLexicon::fold((string) $s->siteLanguage);
        if (in_array($lang, ['tr', 'tr-tr', 'turkish', 'turkce'], true)) {
            return true;
        }

        if ($s->reviewSample === null) {
            return false;
        }

        if (TurkishLexicon::hasDiacritics($s->reviewSample)) {
            return true;
        }

        $folded = TurkishLexicon::fold($s->reviewSample);
        foreach (['cok guzel', 'tesekkur', 'lezzetli', 'harika bir', 'usta', 'ellerine saglik'] as $phrase) {
            if (str_contains($folded, TurkishLexicon::fold($phrase))) {
                return true;
            }
        }

        return false;
    }

    /** Belirsiz adayı sağlayıcıdan bağımsız AI katmanına sorar. */
    private function classifyWithLlm(BusinessSignal $s, DetectionResult $screened): DetectionResult
    {
        if (! $this->ai->isConfigured()) {
            // LLM yoksa belirsiz sonucu insan onayına bırak (körü körüne karar verme).
            return $screened;
        }

        $result = $this->ai->analyzeText($this->buildPrompt($s), self::SCHEMA, 20);

        if ($result === null || ! array_key_exists('turk_mu', $result)) {
            return $screened; // LLM başarısız → belirsiz kalır, insan onayına gider.
        }

        $isTurkish = (bool) $result['turk_mu'];
        $confidence = max(0.0, min(1.0, (float) ($result['guven'] ?? 0.5)));

        /** @var list<string> $llmSignals */
        $llmSignals = array_map(static fn ($x): string => (string) $x, (array) ($result['sinyaller'] ?? []));
        $signals = array_values(array_merge($screened->signals, $llmSignals));

        $confident = $confidence >= 0.75;
        $band = $confident
            ? ($isTurkish ? DetectionResult::BAND_TURKISH : DetectionResult::BAND_NOT)
            : DetectionResult::BAND_AMBIGUOUS;

        return new DetectionResult($isTurkish, $confidence, $band, $signals, 'llm', (string) ($result['gerekce'] ?? ''));
    }

    private function buildPrompt(BusinessSignal $s): string
    {
        $lines = array_filter([
            'İşletme adı: '.$s->name,
            $s->category !== null ? 'Kategori: '.$s->category : null,
            $s->ownerName !== null ? 'Sahip/kişi: '.$s->ownerName : null,
            $s->country !== null ? 'Ülke: '.$s->country : null,
            $s->siteLanguage !== null ? 'Site dili: '.$s->siteLanguage : null,
            $s->reviewSample !== null ? 'Örnek yorum: '.mb_substr($s->reviewSample, 0, 300) : null,
        ]);

        return 'Aşağıdaki işletmenin Türk (Türkiye kökenli ya da Türk diasporası) bir işletme/esnaf olup olmadığını değerlendir. '
            .'DİKKAT: Azerice, Balkan (Boşnak/Arnavut) veya diğer Türki (Kazak/Kırgız/Özbek) isimlerle KARIŞTIRMA — yalnızca '
            .'Türkiye Türklüğüne dair somut işaret varsa olumlu de; emin değilsen düşük güven ver. '
            .'Yanıtı SADECE şu JSON ile döndür: '
            .'{"turk_mu": true/false, "guven": 0 ile 1 arası ondalık, "sinyaller": ["kısa dize", ...], "gerekce": "tek cümle"}.'
            ."\n\n".implode("\n", $lines);
    }
}
