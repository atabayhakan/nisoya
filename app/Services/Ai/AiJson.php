<?php

namespace App\Services\Ai;

/**
 * Sağlayıcılar arasında paylaşılan toleranslı ve güvenli JSON çözümleme yardımcısı.
 *
 * LLM'ler (özellikle Llama 3.2, Nemotron, DeepSeek vb.) bazen JSON'ı ```json ... ```
 * bloğuna sarabilir, önüne veya ardına sohbet/açıklama metni ekleyebilir,
 * akıl yürütme etiketleri (<think>...</think>) koyabilir veya JSON dizim hatası
 * (trailing comma) üretebilir. Bu sınıf tüm bu varyasyonları ayıklar ve çözer.
 */
class AiJson
{
    /**
     * Metinden ilk geçerli JSON nesnesini veya dizisini toleranslı ve güvenli
     * biçimde çözer. Başarısızsa null döner.
     *
     * @return array<string, mixed>|null
     */
    public static function decode(?string $text): ?array
    {
        if (! is_string($text) || trim($text) === '') {
            return null;
        }

        // 1. UTF-8 BOM ve kontrol karakterlerini temizle
        $text = preg_replace('/^\xEF\xBB\xBF/', '', trim($text)) ?? trim($text);

        // 2. Akıl yürütme modellerinin (DeepSeek-R1, Nemotron vb.) <think>...</think> bloklarını temizle
        $cleanText = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $text) ?? $text;
        $cleanText = trim($cleanText);

        // 3. Doğrudan standart json_decode dene (en hızlı yol)
        $data = json_decode($cleanText, true);
        if (is_array($data)) {
            return $data;
        }

        // 4. Markdown kod bloklarını (```json ... ``` veya ``` ... ```) ara ve ayıkla
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $cleanText, $matches)) {
            $extracted = trim($matches[1]);
            $data = json_decode($extracted, true);
            if (is_array($data)) {
                return $data;
            }

            // Kod bloğu içinde trailing comma varsa temizle
            $cleaned = self::cleanTrailingCommas($extracted);
            $data = json_decode($cleaned, true);
            if (is_array($data)) {
                return $data;
            }

            // Python-tarzı tek tırnak veya True/False/None düzeltmesi dene
            $dictData = self::tryFixPythonDict($extracted);
            if ($dictData !== null) {
                return $dictData;
            }
        }

        // 5. Metin içindeki en dış dengeli { ... } nesnesini bul (balanced braces)
        $extractedObject = self::extractOutermostJson($cleanText, '{', '}');
        if ($extractedObject !== null) {
            $data = json_decode($extractedObject, true);
            if (is_array($data)) {
                return $data;
            }

            $cleaned = self::cleanTrailingCommas($extractedObject);
            $data = json_decode($cleaned, true);
            if (is_array($data)) {
                return $data;
            }

            $dictData = self::tryFixPythonDict($extractedObject);
            if ($dictData !== null) {
                return $dictData;
            }
        }

        // 6. Metin içindeki en dış dengeli [ ... ] dizisini bul
        $extractedArray = self::extractOutermostJson($cleanText, '[', ']');
        if ($extractedArray !== null) {
            $data = json_decode($extractedArray, true);
            if (is_array($data)) {
                return $data;
            }

            $cleaned = self::cleanTrailingCommas($extractedArray);
            $data = json_decode($cleaned, true);
            if (is_array($data)) {
                return $data;
            }
        }

        // 7. Ham metin üzerinde trailing comma ve python dict son çare dene
        $cleaned = self::cleanTrailingCommas($cleanText);
        $data = json_decode($cleaned, true);
        if (is_array($data)) {
            return $data;
        }

        return self::tryFixPythonDict($cleanText);
    }

    /**
     * JSON nesne ve dizilerindeki son virgülleri (trailing comma) temizler.
     * Ör: {"a": 1,} -> {"a": 1}
     */
    private static function cleanTrailingCommas(string $json): string
    {
        return preg_replace('/,\s*([}\]])/', '$1', $json) ?? $json;
    }

    /**
     * Python-benzeri sözlük formatını (tek tırnak, True/False/None) geçerli JSON'a dönüştürmeyi dener.
     *
     * @return array<string, mixed>|null
     */
    private static function tryFixPythonDict(string $text): ?array
    {
        $converted = preg_replace("/'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'/", '"$1"', $text);
        if ($converted === null) {
            return null;
        }
        $converted = preg_replace('/\bTrue\b/', 'true', $converted) ?? $converted;
        $converted = preg_replace('/\bFalse\b/', 'false', $converted) ?? $converted;
        $converted = preg_replace('/\bNone\b/', 'null', $converted) ?? $converted;

        $cleaned = self::cleanTrailingCommas($converted);
        $data = json_decode($cleaned, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Metin içindeki ilk açılış karakterinden itibaren en dış dengeli
     * JSON bloğunu (nesne veya dizi) ayıklar. Tırnak içindeki karakterleri ve kaçışları atlar.
     */
    private static function extractOutermostJson(string $text, string $openChar, string $closeChar): ?string
    {
        $startPos = strpos($text, $openChar);
        if ($startPos === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escape = false;
        $length = strlen($text);

        for ($i = $startPos; $i < $length; $i++) {
            $char = $text[$i];

            if ($escape) {
                $escape = false;

                continue;
            }

            if ($char === '\\') {
                $escape = true;

                continue;
            }

            if ($char === '"') {
                $inString = ! $inString;

                continue;
            }

            if (! $inString) {
                if ($char === $openChar) {
                    $depth++;
                } elseif ($char === $closeChar) {
                    $depth--;
                    if ($depth === 0) {
                        return substr($text, $startPos, $i - $startPos + 1);
                    }
                }
            }
        }

        return null;
    }
}
