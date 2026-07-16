<?php

namespace App\Services\Ai;

/**
 * Sağlayıcılar arasında paylaşılan güvenli JSON çözümleme yardımcısı.
 * Modeller bazen JSON'ı ```json ... ``` bloğu içine sarabilir; bunu ayıklar.
 */
class AiJson
{
    /**
     * Metinden ilk geçerli JSON nesnesini çöz. Başarısızsa null.
     *
     * @return array<string, mixed>|null
     */
    public static function decode(?string $text): ?array
    {
        if (! is_string($text) || trim($text) === '') {
            return null;
        }

        $text = trim($text);

        // Markdown kod bloğu sarmalını temizle (```json ... ```)
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text);
        }

        $data = json_decode((string) $text, true);

        return is_array($data) ? $data : null;
    }
}
