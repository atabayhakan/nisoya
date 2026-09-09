<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Models\Listing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Otomatik temsilî görsel üretiminden ÖNCE ilan metnini ahlaki/uygunluk
 * açısından ön-eler.
 *
 * ---------------------------------------------------------------------------
 * NEDEN {@see DolandiricilikTespiti} DEĞİL — AYNI ARAYÜZ, FARKLI SORU
 *
 * O sınıf "bu metin dolandırıcılık deseni mi" sorar. Bu sınıf tamamen farklı
 * bir soru sorar: "bu ilana kamuya açık, sitenin kendi ürettiği bir GÖRSEL
 * eklemek uygun mu". İkisi aynı ilanda aynı anda "hayır" diyebilir ama biri
 * diğerinin yerine geçemez — dolandırıcılık deseni taşımayan bir ilan
 * (ör. yasa dışı bir mal/hizmet, yetişkin içerik, nefret söylemi) pekâlâ bu
 * kapıdan geçmemeli. Kullanıcının kararı: bu iki soru ayrı sorulsun, aynı
 * kontrole gömülmesin.
 *
 * SADECE OTOMATİK ÜRETİMİN KAPISI — ilanın kendisini Beklemede'ye alan tek
 * mekanizma bu değil (bkz. Listing::booted() metin denetimi, ayrı ve zaten
 * yayındaki her ilanda çalışıyor). Bu sınıf yalnız "görsel üretmeden önce bir
 * kez daha bak" diyor; ilanın var olma hakkına karışmaz.
 *
 * FAIL-OPEN: AI kapalıysa ya da çağrı başarısızsa görsel YİNE ÜRETİLİR —
 * DolandiricilikTespiti ile aynı felsefe ("güvenlik ağı, kapı değil").
 * Üretimi engellemek için AI'nın açıkça "uygun değil" demesi gerekir.
 */
class AhlakDenetimi
{
    private const MAX_CHARS = 4000;

    public function __construct(private readonly AiProvider $ai) {}

    public function isEnabled(): bool
    {
        return (bool) config('ai.features.content_ethics_check') && $this->ai->isConfigured();
    }

    /**
     * @return array{uygun: bool, sebep: ?string}|null AI kapalı/başarısızsa null (fail-open → uygun say)
     */
    public function kontrolEt(Listing $listing): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $veri = $this->ai->analyzeText($this->istem($listing), $this->sema());
        } catch (\Throwable $e) {
            Log::warning('Ahlak denetimi başarısız', [
                'listing_id' => $listing->id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }

        if (! is_array($veri) || ! array_key_exists('uygun', $veri)) {
            Log::warning('Ahlak denetimi kullanılamaz yanıt döndü', [
                'listing_id' => $listing->id,
                'saglayici' => $this->ai->name(),
                'saglayici_hatasi' => $this->ai->lastError(),
            ]);

            return null;
        }

        $uygun = (bool) $veri['uygun'];

        return [
            'uygun' => $uygun,
            'sebep' => ! $uygun && filled($veri['sebep'] ?? null) ? (string) $veri['sebep'] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'uygun' => ['type' => 'boolean'],
                'sebep' => ['type' => ['string', 'null']],
            ],
            'required' => ['uygun', 'sebep'],
            'additionalProperties' => false,
        ];
    }

    public function istem(Listing $listing): string
    {
        return implode("\n", [
            'Aşağıdaki ilana, sitenin KENDİSİNİN otomatik ürettiği jenerik bir',
            'kapak görseli eklenecek (yazısız, insansız, markasız, sahnede yalnız',
            'ilanın türünü çağrıştıran nötr bir görsel). Bu ilana böyle bir görsel',
            'eklemek UYGUN MU diye soruyoruz — ilanın kendisinin sitede kalıp',
            'kalmayacağına karar vermiyoruz, yalnız "buraya görsel üretelim mi".',
            '',
            'YALNIZ ŞUNLARDA uygun=false yaz (dar tut, emin olmadığında true de):',
            '- Yetişkin/cinsel içerikli hizmet veya ürün.',
            '- Yasa dışı mal veya hizmet (silah, uyuşturucu, sahte belge vb.).',
            '- Nefret söylemi, ayrımcılık veya şiddet çağrısı içeren metin.',
            '- İnsan ticareti/istismarına işaret eden ifadeler.',
            '',
            'Sıradan bir ürün/hizmet/emlak/vasıta ilanı — fiyat pazarlığı,',
            'WhatsApp\'tan iletişim, kapora isteği gibi ifadeler İÇEREBİLİR;',
            'bunlar TEK BAŞINA uygun=false gerekçesi DEĞİLDİR.',
            '',
            'uygun=false yazarsan sebep alanına kısa (tek cümle) gerekçe yaz;',
            'uygun=true ise sebep null olsun.',
            '',
            '--- BAŞLIK ---',
            Str::limit((string) $listing->title, 250, ''),
            '',
            '--- AÇIKLAMA ---',
            Str::limit((string) $listing->description, self::MAX_CHARS, ''),
        ]);
    }
}
