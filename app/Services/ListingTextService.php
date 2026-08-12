<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Serbest metinden ilan taslağı. `ListingVisionService`'in metin kardeşi:
 * aynı sözleşme, aynı çıktı biçimi, tek farkı girdinin fotoğraf değil yazı
 * olması. Sonuç normal ilan formuna önceden doldurulur — kullanıcı
 * onaylamadan hiçbir şey yayınlanmaz.
 *
 * ---------------------------------------------------------------------------
 * İKİ GİRİŞ KAPISI, TEK SERVİS
 *
 *   · Birkaç kelime:  "iphone 15 pro max gri 256gb"
 *   · Yapıştırılan metin: WhatsApp grubundaki hazır ilan mesajı
 *
 * İkisi de aynı şey: serbest metin → yapılandırılmış taslak. Ayrı servis
 * yazmak aynı prompt'u iki yerde bakmak demekti.
 *
 * NEDEN WHATSAPP: diaspora ticareti bugün zaten WhatsApp gruplarında dönüyor.
 * Bu özellik yeni bir davranış öğretmiyor, var olan davranışı siteye taşıyor —
 * arz darboğazına en yakın duran yer burası.
 *
 * ---------------------------------------------------------------------------
 * ÜÇ SERT KURAL (prompt'ta da yazılı, burada da)
 *
 *   1. UYDURMA YOK. Metinde geçmeyen teknik özellik/ölçü/garanti yazılmaz.
 *      Emin değilse null döner. Sahibin duruşu net: sitedeki her bilgi gerçek.
 *   2. İLETİŞİM BİLGİSİ AYIKLANIR. Yapıştırılan metinde telefon/e-posta/
 *      kullanıcı adı olabiliyor; bunlar ilan metnine TAŞINMAZ. Hem gizlilik
 *      hem de platform dışına çekmenin (dolandırıcılık kalıbı) önü kesilsin.
 *   3. FİYAT TAHMİN DEĞİL, ALINTI. Metinde fiyat yazıyorsa o kullanılır;
 *      yoksa null. Görsel serviste tahmin var çünkü orada başka veri yok;
 *      burada metin varken tahmin uydurmaktır.
 */
class ListingTextService
{
    /** Prompt'a giren metin bu uzunlukta kesilir (maliyet + kötüye kullanım). */
    private const MAX_CHARS = 4000;

    public function __construct(private readonly AiProvider $ai) {}

    public function isEnabled(): bool
    {
        return (bool) config('ai.features.text_listing') && $this->ai->isConfigured();
    }

    /**
     * Serbest metni ilan taslağına çevirir. Hata/kapalı durumda null.
     *
     * Görsel kardeşinden tek farkı `type` alanı: fotoğraf yolu her zaman ürün
     * üretir, metin yolu hizmet de üretebilir (bkz. mapResult).
     *
     * @return array{title: string, category_id: ?int, description: string, price: ?float, condition: ?string, type: string}|null
     */
    public function analyze(string $text, ?string $type = null): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $text = trim(mb_substr(trim($text), 0, self::MAX_CHARS));

        if (mb_strlen($text) < 3) {
            return null;
        }

        try {
            $categories = $this->categories($type);

            if ($categories->isEmpty()) {
                return null;
            }

            $data = $this->ai->analyzeText(
                $this->buildPrompt($text, $categories),
                $this->buildSchema($categories),
            );

            return $this->mapResult($data, $categories);
        } catch (\Throwable $e) {
            Log::warning('Metinden ilan taslağı başarısız (istisna)', ['exception' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Aday kategoriler. Tip verilirse ona göre daraltılır; verilmezse hem
     * ürün hem hizmet aday olur — çünkü kullanıcı "tercüme yapıyorum" da
     * yazabilir, "iphone satıyorum" da. Tipi metinden modelin kendisi
     * anlamıyor; kategoriyi seçerek dolaylı olarak söylüyor.
     *
     * @return Collection<int, Category>
     */
    private function categories(?string $type): Collection
    {
        $tipler = match ($type) {
            'urun' => ['urun', 'ikisi'],
            'hizmet' => ['hizmet', 'ikisi'],
            default => ['urun', 'hizmet', 'ikisi'],
        };

        return Category::query()
            ->whereNotNull('parent_id')
            ->where('is_active', true)
            ->whereIn('type', $tipler)
            ->whereHas('parent', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'type']);
    }

    /** @param  Collection<int, Category>  $categories */
    private function buildPrompt(string $text, Collection $categories): string
    {
        $catList = $categories
            ->map(fn (Category $c) => "- {$c->slug}: {$c->name}")
            ->implode("\n");

        return <<<PROMPT
        Aşağıdaki serbest metin, yurt dışındaki Türklerin pazaryerinde ilan
        açmak isteyen bir kullanıcıdan geliyor. Birkaç kelimelik bir not da
        olabilir, WhatsApp grubundan yapıştırılmış hazır bir ilan metni de.
        Bundan bir ilan taslağı çıkar.

        SADECE aşağıdaki JSON anahtarlarını içeren bir JSON nesnesi döndür:

        - baslik: en az 5 karakter, Türkçe, ilanı net tanımlayan kısa başlık.
        - kategori_slug: SADECE aşağıdaki listeden en uygun slug'ı seç.
        - aciklama: en az 20 karakter, Türkçe, samimi ve düz bir açıklama.
        - durum: ikinci el bir üründen bahsediliyorsa görünen durumu
          (ör. "Sıfır", "Az kullanılmış", "İyi"); hizmet ilanıysa ya da emin
          değilsen null.
        - fiyat: metinde AÇIKÇA yazan fiyat (sayı). Yoksa null.

        KESİN KURALLAR — bunlara uymazsan çıktı kullanılamaz:

        1. UYDURMA. Metinde geçmeyen hiçbir teknik özelliği, ölçüyü, garanti
           ya da özellik iddiasını yazma. Az bilgi varsa açıklama da kısa
           olsun; boşluğu doldurmak için özellik icat etme.
        2. FİYAT TAHMİN ETME. Metinde fiyat yoksa `fiyat` null olacak.
           Tahmini fiyat yazmak yasak.
        3. İLETİŞİM BİLGİSİNİ ÇIKAR. Metinde telefon numarası, e-posta,
           WhatsApp/Instagram kullanıcı adı ya da adres varsa bunları
           başlığa da açıklamaya da YAZMA. Sessizce at.
        4. "Acele", "kaçırma", "son fiyat" gibi baskı ifadelerini taşıma.

        Kategori listesi:
        {$catList}

        Kullanıcının metni:
        ---
        {$text}
        ---
        PROMPT;
    }

    /**
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
                'fiyat' => ['type' => ['number', 'null']],
            ],
            'required' => ['baslik', 'kategori_slug', 'aciklama', 'durum', 'fiyat'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @param  Collection<int, Category>  $categories
     * @return array{title: string, category_id: ?int, description: string, price: ?float, condition: ?string, type: string}|null
     */
    private function mapResult(?array $data, Collection $categories): ?array
    {
        if (! is_array($data) || empty($data['baslik'])) {
            return null;
        }

        $kategori = $categories->firstWhere('slug', $data['kategori_slug'] ?? null);

        return [
            'title' => trim((string) $data['baslik']),
            'category_id' => $kategori?->id,
            /*
             * Tip KATEGORİDEN türetiliyor, modelden ayrıca sorulmuyor: model
             * zaten kategoriyi seçerken tipi örtük olarak söylüyor ve iki ayrı
             * alan sorulunca birbiriyle çelişebiliyorlar. Tek kaynak daha
             * güvenli. `ikisi` tipindeki kategori ürün formuna düşer.
             *
             * `->value` ŞART: `Category::type` enum'a cast ediliyor, dizeyle
             * doğrudan karşılaştırma her zaman false döner ve her ilan sessizce
             * ürün olurdu.
             */
            'type' => $kategori?->type?->value === 'hizmet' ? 'hizmet' : 'urun',
            'description' => trim((string) ($data['aciklama'] ?? '')),
            'price' => isset($data['fiyat']) && is_numeric($data['fiyat'])
                ? (float) $data['fiyat']
                : null,
            'condition' => filled($data['durum'] ?? null) ? trim((string) $data['durum']) : null,
        ];
    }
}
