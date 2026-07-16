<?php

namespace App\Contracts;

/**
 * Sisteme eklenen tüm yapay zeka özelliklerinin arkasındaki tek arayüz.
 * Uygulamayı sağlayıcıdan (Claude, OpenAI, Gemini, ...) bağımsızlaştırır —
 * özellik kodu bu arayüzü konuşur, hangi sağlayıcının çalıştığını bilmez.
 *
 * Yeni sağlayıcı eklerken bu arayüzü uygula ve App\Services\Ai\AiManager'a
 * kaydet (bkz. config/ai.php).
 */
interface AiProvider
{
    /** Sağlayıcının çalışabilmesi için gerekli yapılandırma (API anahtarı vb.) var mı? */
    public function isConfigured(): bool;

    /** İnsan tarafından okunabilir sağlayıcı adı (log/hata mesajları için). */
    public function name(): string;

    /**
     * Bir görseli + metin yönergesini analiz edip yapılandırılmış JSON döndürür.
     * Yönerge (prompt) beklenen JSON anahtarlarını tam olarak tarif etmelidir.
     * $jsonSchema verilirse ve sağlayıcı destekliyorsa çıktı şemayla zorlanır;
     * desteklemeyen sağlayıcılar yalnızca "JSON döndür" modunu kullanır.
     *
     * @param  string  $base64Image  Base64 kodlanmış görsel verisi (data: öneki YOK)
     * @param  string  $mediaType  ör. "image/jpeg"
     * @param  array<string, mixed>|null  $jsonSchema  JSON Schema (opsiyonel)
     * @return array<string, mixed>|null Çözülmüş JSON; hata/güvenlik reddi durumunda null
     */
    public function analyzeImage(string $base64Image, string $mediaType, string $prompt, ?array $jsonSchema = null): ?array;
}
