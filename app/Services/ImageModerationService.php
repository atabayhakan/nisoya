<?php

namespace App\Services;

use App\Contracts\AiProvider;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

/**
 * Yüklenen görsellerde (ilan + sohbet fotoğrafı) AI ile otomatik uygunsuz
 * içerik ön-elemesi. Görseli ASLA silmez/reddetmez tek başına — ilan
 * görselinde işaretleyip ilanı incelemeye alır (bkz. ProcessListingImage),
 * sohbet fotoğrafında ise mesajın oluşturulmasını engeller (bkz.
 * MessageController). Nihai karar her zaman bir admin'e ait.
 *
 * AI kapalı/yapılandırılmamışsa veya çağrı başarısız olursa FAIL-OPEN:
 * hiçbir şeyi engellemez (bu bir güvenlik ağı, sert bir zorunluluk değil).
 */
class ImageModerationService
{
    /** Moderasyon için küçük gönderim yeterli — maliyeti düşürür. */
    private const MAX_WIDTH = 768;

    public function __construct(private readonly AiProvider $ai) {}

    public function isEnabled(): bool
    {
        return (bool) config('ai.features.image_moderation') && $this->ai->isConfigured();
    }

    /**
     * Diskteki bir görseli (gerçek dosya yolu) moderasyondan geçirir.
     *
     * @return array{flagged: bool, reason: ?string}|null AI kapalı/başarısızsa null (fail-open)
     */
    public function check(string $realPath): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            [$base64, $mediaType] = $this->prepareImage($realPath);
        } catch (\Throwable $e) {
            Log::warning('Moderasyon: görsel hazırlanamadı', ['exception' => $e->getMessage()]);

            return null;
        }

        $data = $this->ai->analyzeImage($base64, $mediaType, $this->prompt(), $this->schema());

        if (! is_array($data) || ! array_key_exists('uygunsuz', $data)) {
            return null;
        }

        return [
            'flagged' => (bool) $data['uygunsuz'],
            'reason' => filled($data['kategori'] ?? null) ? (string) $data['kategori'] : null,
        ];
    }

    private function prompt(): string
    {
        return <<<'PROMPT'
        Bu görseli bir pazaryeri/mesajlaşma platformu için içerik güvenliği
        açısından değerlendir.

        Kurallar:
        - Yalnızca AÇIK ve BARİZ ihlallerde uygunsuz=true işaretle: çıplaklık/
          cinsel içerik, kanlı/grafik şiddet, nefret sembolleri, uyuşturucu/
          yasadışı madde satışı, ateşli silah.
        - Ev eşyası, mobilya, yemek, günlük yaşam, hizmet/iş fotoğrafları,
          sıradan insan fotoğrafları KESİNLİKLE uygunsuz DEĞİLDİR — emin
          değilsen uygunsuz=false yaz.
        - Bu bir ön-eleme; nihai kararı bir admin verecek. Şüpheliyse false yaz.
        PROMPT;
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'uygunsuz' => ['type' => 'boolean'],
                'kategori' => [
                    'type' => ['string', 'null'],
                    'enum' => ['cinsel_icerik', 'siddet', 'nefret_sembolu', 'yasadisi_madde', 'silah', null],
                ],
            ],
            'required' => ['uygunsuz', 'kategori'],
            'additionalProperties' => false,
        ];
    }

    /** @return array{0: string, 1: string} [base64, mediaType] */
    private function prepareImage(string $realPath): array
    {
        $manager = new ImageManager(new Driver);
        $image = $manager->decode($realPath);

        if ($image->width() > self::MAX_WIDTH) {
            $image->scaleDown(width: self::MAX_WIDTH);
        }

        return [base64_encode((string) $image->encode(new JpegEncoder(quality: 70))), 'image/jpeg'];
    }
}
