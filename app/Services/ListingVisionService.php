<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

/**
 * Kamera-önce hızlı ilan (Faz M3). Bir ürün fotoğrafından Claude görüntü
 * analizi ile başlık/kategori/açıklama/fiyat önerisi üretir. Sonuç mevcut
 * ilan formuna önceden doldurulur — kullanıcı onaylamadan hiçbir şey
 * yayınlanmaz (bkz. QuickListingController).
 *
 * Neden SDK değil de Http: proje dış API'leri (Nominatim, GeocodingService)
 * zaten Laravel Http istemcisiyle çağırıyor; tek bir vision isteği için ayrı
 * bir Composer bağımlılığı eklemek yerine aynı deseni sürdürüyoruz.
 */
class ListingVisionService
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';

    private const API_VERSION = '2023-06-01';

    /** Analiz maliyetini sınırlamak için görsel bu genişliğe küçültülür. */
    private const MAX_WIDTH = 1024;

    public function isEnabled(): bool
    {
        return config('services.anthropic.quick_listing_enabled')
            && filled(config('services.anthropic.api_key'));
    }

    /**
     * Fotoğrafı analiz edip form önerisi döndürür. Hata/kapalı durumda null.
     *
     * @return array{title: string, category_id: ?int, description: string, price: ?float, condition: ?string}|null
     */
    public function analyze(string $imageRealPath): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            [$base64, $mediaType] = $this->prepareImage($imageRealPath);
            $categories = $this->productCategories();

            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => self::API_VERSION,
                'content-type' => 'application/json',
            ])
                ->timeout(30)
                ->post(self::ENDPOINT, $this->requestBody($base64, $mediaType, $categories));

            if (! $response->successful()) {
                Log::warning('Hızlı ilan analizi başarısız (API yanıtı)', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $this->parseResponse($response->json(), $categories);
        } catch (\Throwable $e) {
            Log::warning('Hızlı ilan analizi başarısız (istisna)', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Görseli en fazla MAX_WIDTH'e küçültüp EXIF orientation düzeltir ve
     * JPEG base64 döndürür. EXIF (GPS dahil) strip edilir — API'ye konum
     * sızmaz.
     *
     * @return array{0: string, 1: string} [base64, mediaType]
     */
    private function prepareImage(string $realPath): array
    {
        $manager = new ImageManager(new Driver);
        $image = $manager->decode($realPath);

        if ($image->width() > self::MAX_WIDTH) {
            $image->scaleDown(width: self::MAX_WIDTH);
        }

        $encoded = (string) $image->encode(new JpegEncoder(quality: 80));

        return [base64_encode($encoded), 'image/jpeg'];
    }

    /**
     * Aktif ürün kategorileri (type=urun|ikisi, alt kategoriler). Model
     * yalnızca bu slug'lardan seçebilsin diye enum + prompt olarak verilir.
     *
     * @return Collection<int, Category>
     */
    private function productCategories()
    {
        return Category::query()
            ->whereNotNull('parent_id')
            ->where('is_active', true)
            ->whereIn('type', ['urun', 'ikisi'])
            ->whereHas('parent', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);
    }

    /** @return array<string, mixed> */
    private function requestBody(string $base64, string $mediaType, $categories): array
    {
        $slugs = $categories->pluck('slug')->all();
        $catList = $categories
            ->map(fn (Category $c) => "- {$c->slug}: {$c->name}")
            ->implode("\n");

        $prompt = <<<PROMPT
        Bu fotoğraf, yurt dışındaki Türklerin ikinci el eşya/ürün pazaryerinde
        satılacak bir ürüne ait. Fotoğrafı incele ve ilan taslağı hazırla.

        Kurallar:
        - baslik: en az 5 karakter, Türkçe, ürünü net tanımlayan kısa bir başlık.
        - kategori_slug: SADECE aşağıdaki listeden en uygun slug'ı seç.
        - aciklama: en az 20 karakter, Türkçe, ürünün görünen özelliklerini
          (renk, malzeme, tahmini durum) anlatan samimi bir açıklama. Fiyat,
          iletişim veya uydurma teknik detay yazma.
        - durum: ürünün görünen durumu (ör. "Sıfır", "Az kullanılmış", "İyi",
          "Yıpranmış"); emin değilsen boş bırak.
        - fiyat_tahmini: ikinci el için makul bir tahmini fiyat (sayı, EUR);
          emin değilsen null.

        Kategori listesi:
        {$catList}
        PROMPT;

        return [
            'model' => config('services.anthropic.model'),
            'max_tokens' => 1024,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'image',
                        'source' => [
                            'type' => 'base64',
                            'media_type' => $mediaType,
                            'data' => $base64,
                        ],
                    ],
                    ['type' => 'text', 'text' => $prompt],
                ],
            ]],
            'output_config' => [
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'baslik' => ['type' => 'string'],
                            'kategori_slug' => ['type' => 'string', 'enum' => array_values($slugs)],
                            'aciklama' => ['type' => 'string'],
                            'durum' => ['type' => ['string', 'null']],
                            'fiyat_tahmini' => ['type' => ['number', 'null']],
                        ],
                        'required' => ['baslik', 'kategori_slug', 'aciklama'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * API yanıtından yapılandırılmış JSON'ı çıkar ve slug'ı category_id'ye
     * çevir.
     *
     * @return array{title: string, category_id: ?int, description: string, price: ?float, condition: ?string}|null
     */
    private function parseResponse(array $json, $categories): ?array
    {
        // stop_reason "refusal" ise içerik güvenli değil — sessizce vazgeç.
        if (($json['stop_reason'] ?? null) === 'refusal') {
            return null;
        }

        $text = collect($json['content'] ?? [])
            ->firstWhere('type', 'text')['text'] ?? null;

        if (! $text) {
            return null;
        }

        $data = json_decode($text, true);
        if (! is_array($data) || empty($data['baslik'])) {
            return null;
        }

        $categoryId = $categories->firstWhere('slug', $data['kategori_slug'] ?? null)?->id;

        return [
            'title' => trim((string) $data['baslik']),
            'category_id' => $categoryId,
            'description' => trim((string) ($data['aciklama'] ?? '')),
            'price' => isset($data['fiyat_tahmini']) && is_numeric($data['fiyat_tahmini'])
                ? (float) $data['fiyat_tahmini']
                : null,
            'condition' => filled($data['durum'] ?? null) ? trim((string) $data['durum']) : null,
        ];
    }
}
