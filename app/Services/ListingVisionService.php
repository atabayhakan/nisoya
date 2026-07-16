<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

/**
 * Kamera-önce hızlı ilan (Faz M3). Bir ürün fotoğrafından yapay zeka ile
 * başlık/kategori/açıklama/fiyat önerisi üretir. Sonuç mevcut ilan formuna
 * önceden doldurulur — kullanıcı onaylamadan hiçbir şey yayınlanmaz
 * (bkz. QuickListingController).
 *
 * Sağlayıcıdan bağımsız: gerçek API çağrısı App\Contracts\AiProvider'a
 * (Claude/OpenAI/Gemini — config/ai.php ile seçilir) devredilir. Bu sınıf
 * yalnızca görsel hazırlama, prompt/şema üretimi ve slug→kategori eşlemesini
 * yapar; hangi AI'ın çalıştığını bilmez.
 */
class ListingVisionService
{
    /** Analiz maliyetini sınırlamak için görsel bu genişliğe küçültülür. */
    private const MAX_WIDTH = 1024;

    public function __construct(private readonly AiProvider $ai) {}

    public function isEnabled(): bool
    {
        return (bool) config('ai.features.quick_listing') && $this->ai->isConfigured();
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

            $data = $this->ai->analyzeImage(
                $base64,
                $mediaType,
                $this->buildPrompt($categories),
                $this->buildSchema($categories),
            );

            return $this->mapResult($data, $categories);
        } catch (\Throwable $e) {
            Log::warning('Hızlı ilan analizi başarısız (istisna)', ['exception' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Görseli en fazla MAX_WIDTH'e küçültüp JPEG base64 döndürür. EXIF (GPS
     * dahil) strip edilir — sağlayıcıya konum sızmaz.
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
     * Aktif ürün kategorileri (type=urun|ikisi, alt kategoriler).
     *
     * @return Collection<int, Category>
     */
    private function productCategories(): Collection
    {
        return Category::query()
            ->whereNotNull('parent_id')
            ->where('is_active', true)
            ->whereIn('type', ['urun', 'ikisi'])
            ->whereHas('parent', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);
    }

    /** @param  Collection<int, Category>  $categories */
    private function buildPrompt(Collection $categories): string
    {
        $catList = $categories
            ->map(fn (Category $c) => "- {$c->slug}: {$c->name}")
            ->implode("\n");

        return <<<PROMPT
        Bu fotoğraf, yurt dışındaki Türklerin ikinci el eşya/ürün pazaryerinde
        satılacak bir ürüne ait. Fotoğrafı incele ve ilan taslağı hazırla.
        SADECE aşağıdaki JSON anahtarlarını içeren bir JSON nesnesi döndür:

        - baslik: en az 5 karakter, Türkçe, ürünü net tanımlayan kısa başlık.
        - kategori_slug: SADECE aşağıdaki listeden en uygun slug'ı seç.
        - aciklama: en az 20 karakter, Türkçe, ürünün görünen özelliklerini
          (renk, malzeme, tahmini durum) anlatan samimi bir açıklama. Fiyat,
          iletişim veya uydurma teknik detay yazma.
        - durum: ürünün görünen durumu (ör. "Sıfır", "Az kullanılmış", "İyi",
          "Yıpranmış"); emin değilsen null.
        - fiyat_tahmini: ikinci el için makul tahmini fiyat (sayı, EUR); emin
          değilsen null.

        Kategori listesi:
        {$catList}
        PROMPT;
    }

    /**
     * Sağlayıcıya verilecek JSON Schema. Tüm alanlar required + opsiyoneller
     * nullable — hem Anthropic hem OpenAI strict modu tek şemayı kabul etsin.
     *
     * @param  Collection<int, Category>  $categories
     * @return array<string, mixed>
     */
    private function buildSchema(Collection $categories): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'baslik' => ['type' => 'string'],
                'kategori_slug' => ['type' => 'string', 'enum' => $categories->pluck('slug')->values()->all()],
                'aciklama' => ['type' => 'string'],
                'durum' => ['type' => ['string', 'null']],
                'fiyat_tahmini' => ['type' => ['number', 'null']],
            ],
            'required' => ['baslik', 'kategori_slug', 'aciklama', 'durum', 'fiyat_tahmini'],
            'additionalProperties' => false,
        ];
    }

    /**
     * Sağlayıcıdan gelen ham JSON'ı form alanlarına çevir; slug→category_id.
     *
     * @param  array<string, mixed>|null  $data
     * @param  Collection<int, Category>  $categories
     * @return array{title: string, category_id: ?int, description: string, price: ?float, condition: ?string}|null
     */
    private function mapResult(?array $data, Collection $categories): ?array
    {
        if (! is_array($data) || empty($data['baslik'])) {
            return null;
        }

        return [
            'title' => trim((string) $data['baslik']),
            'category_id' => $categories->firstWhere('slug', $data['kategori_slug'] ?? null)?->id,
            'description' => trim((string) ($data['aciklama'] ?? '')),
            'price' => isset($data['fiyat_tahmini']) && is_numeric($data['fiyat_tahmini'])
                ? (float) $data['fiyat_tahmini']
                : null,
            'condition' => filled($data['durum'] ?? null) ? trim((string) $data['durum']) : null,
        ];
    }
}
