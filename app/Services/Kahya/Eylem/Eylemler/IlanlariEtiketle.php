<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Models\Listing;
use App\Models\Tag;
use App\Services\Ai\AiManager;
use App\Services\Kahya\Eylem\Eylem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Etiketi olmayan aktif ilanları yapay zekâ ile TOPLU etiketler.
 *
 * "Etiketler bölümünü Kâhya otomatik yapsın" isteğinin karşılığı: sahip tek
 * tek ilan açıp etiket seçmek zorunda kalmasın. Eylem, etiketsiz ilanları
 * TEK bir yapay zekâ çağrısıyla değerlendirir; önce MEVCUT etiketlerden
 * seçtirir, uygun etiket yoksa yenisini açar.
 *
 * ---------------------------------------------------------------------------
 * NEDEN YÜKSEK RİSK
 *
 * Etiketler ilan sayfasında HERKESE görünür ve bu eylem onlarca kaydı birden
 * değiştirir. Tek tek geri alınabilir olsa da "sitenin yüzü" toplu değişir —
 * EylemRiski doktrinindeki ikinci soru. Sahip onaylamadan hiçbir şey yazılmaz.
 *
 * ---------------------------------------------------------------------------
 * SINIRLAR MODELE DEĞİL KODA GÜVENİR
 *
 * Model ilan başına en çok 3 etiket, toplamda en çok 10 YENİ etiket önersin
 * diye YÖNLENDİRİLİR ama sınırı uygulayan kod: fazlası sessizce değil,
 * sayarak kırpılır ve sonuç metninde görünür. Model uydurma ilan kimliği
 * dönerse o satır atlanır — uydurulmuş bir kimliğe yazılmaz.
 */
class IlanlariEtiketle extends Eylem
{
    /** Tek seferde taranacak ilan sayısının tavanı ve varsayılanı. */
    private const VARSAYILAN_SINIR = 15;

    private const UST_SINIR = 30;

    /** Bir ilana bağlanacak en çok etiket. */
    private const ILAN_BASI_ETIKET = 3;

    /** Bir koşuda açılabilecek en çok YENİ etiket. */
    private const YENI_ETIKET_TAVANI = 10;

    public function __construct(private readonly AiManager $ai) {}

    public function ad(): string
    {
        return 'ilanlari-etiketle';
    }

    public function baslik(): string
    {
        return 'İlanları otomatik etiketle';
    }

    public function aciklama(): string
    {
        return 'Etiketi hiç olmayan AKTİF ilanları bulur ve her birine en uygun 1-3 etiketi '
            .'yapay zekâ ile seçip bağlar. Önce mevcut etiket listesinden seçer; hiçbiri uymuyorsa '
            .'yeni etiket açabilir. İlan metnine, fiyatına, durumuna DOKUNMAZ — yalnız etiket bağlar. '
            .'Tek bir etiket eklemek için etiket-ekle var; bu eylem toplu çalışır ve sahibin '
            .'onayından sonra uygulanır.';
    }

    public function sema(): array
    {
        return [
            'sinir' => 'En fazla kaç ilan etiketlensin (1-'.self::UST_SINIR.'). Sahip söylemediyse boş bırak; varsayılan '.self::VARSAYILAN_SINIR.'.',
        ];
    }

    public function kurallar(): array
    {
        return [
            'sinir' => ['nullable', 'integer', 'min:1', 'max:'.self::UST_SINIR],
        ];
    }

    public function risk(): EylemRiski
    {
        // Toplu iş + ilan sayfalarında herkese görünen değişiklik: önce onay.
        return EylemRiski::Yuksek;
    }

    public function onizleme(array $p): string
    {
        $sinir = $this->sinir($p);
        $aday = $this->adaySorgusu()->count();
        $taranacak = min($aday, $sinir);

        if ($aday === 0) {
            return 'Etiketi olmayan aktif ilan yok — eylem onaylansa da bir şey değişmeyecek.';
        }

        return "Etiketi olmayan {$aday} aktif ilandan en yeni {$taranacak} tanesi taranacak; "
            .'her birine en fazla '.self::ILAN_BASI_ETIKET.' etiket bağlanacak, gerekirse en fazla '
            .self::YENI_ETIKET_TAVANI.' yeni etiket açılacak. İlanların kendisine dokunulmayacak.';
    }

    public function uygula(array $p): array
    {
        $ilanlar = $this->adaySorgusu()
            ->with('category')
            ->latest()
            ->limit($this->sinir($p))
            ->get();

        if ($ilanlar->isEmpty()) {
            return [
                'sonuc' => 'Etiketi olmayan aktif ilan yok; hiçbir şey değişmedi.',
                'geri_alma' => ['baglar' => [], 'yeni_etiketler' => []],
            ];
        }

        /*
         * Yapay zekâ çağrısı İLK iş — henüz hiçbir satır yazılmadı. Çağrı
         * EylemCalistirici'nin işlemi (transaction) içinde koşar ama yazma
         * kilitleri ancak ilk yazmayla alınır; model düşünürken veritabanı
         * beklemez. Model cevap veremezse istisna atılır ve defter "hata"
         * yazar — yarım etiketlenmiş ilan kalmaz.
         */
        $karar = $this->ai->provider()->analyzeText($this->yonerge($ilanlar), $this->jsonSemasi(), 120);

        if ($karar === null) {
            throw new RuntimeException('Yapay zekâ etiket önerisi veremedi: '.($this->ai->provider()->lastError() ?? 'sebep bilinmiyor'));
        }

        $mevcutlar = Tag::query()->get()->keyBy(fn (Tag $t): string => $t->slug);
        $baglar = [];
        $yeniEtiketler = [];
        $yeniAdlar = [];

        foreach ((array) ($karar['ilanlar'] ?? []) as $satir) {
            $ilan = $ilanlar->firstWhere('id', (int) ($satir['id'] ?? 0));

            if ($ilan === null) {
                continue; // Model kimlik uydurdu; uydurulmuş kimliğe yazılmaz.
            }

            $adlar = array_slice(array_filter(array_map(
                fn ($ad): string => trim((string) $ad),
                (array) ($satir['etiketler'] ?? []),
            ), fn (string $ad): bool => $ad !== '' && mb_strlen($ad) <= 60), 0, self::ILAN_BASI_ETIKET);

            foreach ($adlar as $ad) {
                $slug = Str::slug($ad);

                if ($slug === '') {
                    continue;
                }

                $etiket = $mevcutlar->get($slug);

                if ($etiket === null) {
                    // Yeni etiket tavanı: model savurganlaşırsa liste
                    // çöplüğe dönmesin; fazlası bağlanmadan atlanır.
                    if (count($yeniEtiketler) >= self::YENI_ETIKET_TAVANI) {
                        continue;
                    }

                    $etiket = Tag::create(['name' => $ad, 'slug' => $slug]);
                    $mevcutlar->put($slug, $etiket);
                    $yeniEtiketler[] = $etiket->id;
                    $yeniAdlar[] = $ad;
                }

                $ilan->tags()->syncWithoutDetaching([$etiket->id]);
                $baglar[] = ['ilan' => $ilan->id, 'etiket' => $etiket->id];
            }
        }

        $ilanSayisi = count(array_unique(array_column($baglar, 'ilan')));

        $yeniMetni = $yeniAdlar === []
            ? ''
            : ' Yeni açılan etiketler: '.implode(', ', $yeniAdlar).'.';

        return [
            'sonuc' => "{$ilanSayisi} ilana toplam ".count($baglar)." etiket bağlandı.{$yeniMetni}",
            'geri_alma' => ['baglar' => $baglar, 'yeni_etiketler' => $yeniEtiketler],
        ];
    }

    public function geriAl(array $iz): string
    {
        $cozulen = 0;

        foreach ((array) ($iz['baglar'] ?? []) as $bag) {
            $ilan = Listing::query()->find($bag['ilan'] ?? 0);

            if ($ilan !== null) {
                $cozulen += $ilan->tags()->detach($bag['etiket'] ?? 0);
            }
        }

        /*
         * Bu koşunun açtığı etiketlerden başka ilana bağlanmamış olanlar
         * silinir; sahibin ya da başka bir koşunun sonradan kullandıkları
         * kalır — EtiketEkle'nin geri almasıyla aynı dürüstlük: elle kurulmuş
         * bir bağ sessizce koparılmaz.
         */
        $silinen = 0;

        foreach ((array) ($iz['yeni_etiketler'] ?? []) as $etiketId) {
            $etiket = Tag::query()->find($etiketId);

            if ($etiket !== null && $etiket->listings()->count() === 0) {
                $etiket->delete();
                $silinen++;
            }
        }

        return "{$cozulen} etiket bağı çözüldü, kullanılmayan {$silinen} yeni etiket silindi.";
    }

    public function ornekler(): array
    {
        return [
            'ilanları otomatik etiketle',
            'etiketleri sen düzenle, etiketsiz ilanlara uygun etiket bağla',
            'etiket işini otomatik yap',
        ];
    }

    // ------------------------------------------------------------- İçeriden

    /** @param  array<string, mixed>  $p */
    private function sinir(array $p): int
    {
        $sinir = (int) ($p['sinir'] ?? self::VARSAYILAN_SINIR);

        return max(1, min($sinir, self::UST_SINIR));
    }

    /** @return Builder<Listing> */
    private function adaySorgusu()
    {
        return Listing::query()->active()->whereDoesntHave('tags');
    }

    /** @param  Collection<int, Listing>  $ilanlar */
    private function yonerge($ilanlar): string
    {
        $mevcut = Tag::query()->orderBy('name')->pluck('name')->implode(', ');
        $mevcutMetni = $mevcut !== '' ? $mevcut : '(henüz etiket yok)';

        $satirlar = $ilanlar->map(function (Listing $ilan): string {
            $ozet = Str::of((string) $ilan->description)->squish()->limit(160);

            return "- id {$ilan->id} · başlık: \"{$ilan->title}\" · kategori: "
                .$ilan->category->name." · özet: {$ozet}";
        })->implode("\n");

        return <<<METIN
        Nisoya (yurtdışındaki Türkler için Türkçe pazaryeri) ilanlarına etiket seçiyorsun.
        Etiket, ilanın altında görünen kısa bir Türkçe sözcük/ifadedir (ör. "Acil",
        "Öğrenci dostu", "El yapımı"). Kategori DEĞİLDİR.

        ## Mevcut etiketler (önce bunlardan seç)
        {$mevcutMetni}

        ## Etiketlenecek ilanlar
        {$satirlar}

        ## Kurallar
        1. Her ilana en az 1, en çok 3 etiket seç.
        2. ÖNCE mevcut etiketlerden uygun olanı kullan; birebir aynı yazılışla ver.
        3. Mevcutlarda uygun yoksa KISA (en çok 3 kelime), Türkçe, genel-geçer yeni bir
           etiket öner. Toplamda 10'dan fazla yeni etiket önerme.
        4. İlan içeriğinden emin olamıyorsan o ilana yalnız güvendiğin etiketi ver.

        Yanıtını SADECE JSON olarak ver: {"ilanlar": [{"id": 1, "etiketler": ["...", "..."]}]}
        METIN;
    }

    /** @return array<string, mixed> */
    private function jsonSemasi(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ilanlar' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'description' => 'İlanın kimliği — listedekilerden biri.'],
                            'etiketler' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Bu ilana bağlanacak 1-3 Türkçe etiket adı.',
                            ],
                        ],
                        'required' => ['id', 'etiketler'],
                    ],
                ],
            ],
            'required' => ['ilanlar'],
        ];
    }
}
