<?php

namespace App\Services\Growth\Discovery;

use App\Services\GeocodingService;
use App\Support\Growth\TurkishLexicon;
use Illuminate\Support\Facades\Http;

/**
 * ÜCRETSİZ keşif kaynağı — OpenStreetMap "Overpass API". API anahtarı, kart ve
 * faturalandırma GEREKTİRMEZ. Metin araması değil alan+etiket tabanlıdır: bir
 * şehrin merkezi etrafında (GeocodingService/Nominatim ile bulunur) belirli bir
 * OSM etiketine (ör. amenity=restaurant) sahip işletmeleri çeker. Türk süzmesini
 * yine TurkishBusinessDetector yapar (Overpass "Türk" araması yapamaz — tümünü
 * getirir, tespit motoru filtreler).
 *
 * Nisoya zaten harita/geocoding için OSM (Nominatim) kullanıyor — tutarlı ve
 * bedava. Overpass topluluk servisi olduğundan nazik kullanılır (User-Agent,
 * makul timeout, sınırlı sonuç).
 */
final class OverpassDiscoverySource implements BusinessDiscoverySource
{
    private const ENDPOINT = 'https://overpass-api.de/api/interpreter';

    /** Şehir merkezi etrafında tarama yarıçapı (metre). */
    private const RADIUS_METERS = 15000;

    /** Overpass'ın kendi sorgu bütçesi (saniye) — HTTP timeout'undan küçük olmalı. */
    private const QUERY_TIMEOUT = 40;

    public function __construct(private readonly GeocodingService $geocoder) {}

    public function name(): string
    {
        return 'openstreetmap';
    }

    public function isConfigured(): bool
    {
        return true; // ücretsiz, anahtar yok
    }

    /**
     * @param  array{key: string, tr: string, en: string, osm?: string, local?: string}  $trade
     * @return list<DiscoveredBusiness>
     */
    public function discover(string $city, string $country, array $trade, int $limit = 20): array
    {
        $osm = $trade['osm'] ?? '';
        [$key, $value] = array_pad(explode('=', $osm, 2), 2, null);
        if (! $key || ! $value) {
            return []; // bu meslek için OSM etiketi tanımsız
        }

        $coords = $this->geocoder->locate($city, $country);
        if ($coords['latitude'] === null || $coords['longitude'] === null) {
            return []; // şehir koordinatı çözülemedi
        }

        try {
            $response = Http::asForm()
                ->withHeaders(['User-Agent' => 'Nisoya/1.0 (+https://nisoya.com)'])
                ->timeout(self::QUERY_TIMEOUT + 15)
                ->post(self::ENDPOINT, [
                    'data' => $this->buildQuery($coords['latitude'], $coords['longitude'], $key, $value, $limit),
                ]);
        } catch (\Throwable $e) {
            throw new KesifKaynagiHatasi('Overpass\'a ulaşılamadı: '.$e->getMessage(), previous: $e);
        }

        return $this->mapElements(
            $this->cozumleVeyaPatlat($response->status(), $response->body()),
            $country,
            $city,
            $trade
        );
    }

    /**
     * Yanıtı `elements` dizisine çevirir; ARIZAYI SONUÇSUZLUKTAN AYIRIR.
     *
     * Ağdan bağımsız (saf) — testler gerçek Overpass yanıtlarını buraya verir.
     *
     * @return list<array<string, mixed>>
     */
    public function cozumleVeyaPatlat(int $status, string $body): array
    {
        if ($status < 200 || $status >= 300) {
            throw new KesifKaynagiHatasi("Overpass HTTP {$status} döndü.");
        }

        $json = json_decode($body, true);

        /*
         * HTTP 200 + JSON DEĞİL: Overpass aşırı yüklendiğinde JSON yerine bir
         * HTML hata sayfası döner ("The server is probably too busy...").
         * Eski kod `json('elements') ?? []` diyerek bunu boş sonuç sanıyordu.
         */
        if (! is_array($json)) {
            throw new KesifKaynagiHatasi('Overpass JSON yerine hata sayfası döndü (sunucu meşgul olabilir).');
        }

        /*
         * HTTP 200 + `remark`: sorgu Overpass'ın kendi bütçesini aştı. Gövdede
         * `elements` boş gelir — yani "hiçbir şey bulunamadı" ile birebir aynı
         * görünür. Ayıran tek şey bu alan.
         */
        if (isset($json['remark']) && is_string($json['remark'])) {
            throw new KesifKaynagiHatasi('Overpass: '.$json['remark']);
        }

        $elements = $json['elements'] ?? null;

        if (! is_array($elements)) {
            throw new KesifKaynagiHatasi('Overpass yanıtında `elements` yok.');
        }

        return array_values(array_filter($elements, 'is_array'));
    }

    /**
     * Sorgu — SINIR KUTUSU (bbox) ile, `around:` ile DEĞİL.
     *
     * ÖLÇÜLDÜ (2026-08-06, New York + shop=hairdresser):
     *   `nwr(around:15000,...)[tag]` → 26 saniyede ZAMAN AŞIMI, sıfır sonuç
     *   `nwr[tag](güney,batı,kuzey,doğu)` → 13 saniyede sonuç
     *
     * `around:` her adayı merkeze olan mesafesiyle tek tek ölçer; sınır kutusu
     * doğrudan Overpass'ın uzamsal indeksine düşer. Etiket süzgecini kutudan
     * ÖNCE yazmak da bilinçli — arama önce etikete daralır.
     *
     * Kutu daireden biraz geniştir (köşeler yarıçapı aşar); bu kabul edilebilir,
     * çünkü fazladan gelen aday zaten Türk-tespit süzgecinden geçiyor.
     */
    private function buildQuery(float $lat, float $lng, string $key, string $value, int $limit): string
    {
        [$guney, $bati, $kuzey, $dogu] = $this->sinirKutusu($lat, $lng, self::RADIUS_METERS);

        /*
         * İSİM DESENİ SORGUNUN İÇİNDE — sonradan süzmek işe yaramıyor.
         * ÖLÇÜLDÜ: desensiz New York+New Jersey'de 80 lokanta geldi, Türk
         * çıkan SIFIR (bölgede binlerce lokanta var, 40'ı rastgele geliyordu).
         * Desenle aynı bölgeden 30 aday geldi. Gerekçe: TurkishLexicon.
         */
        return sprintf(
            '[out:json][timeout:%d];nwr["%s"="%s"]["name"~"%s",i](%s,%s,%s,%s);out center %d;',
            self::QUERY_TIMEOUT,
            $this->esc($key),
            $this->esc($value),
            TurkishLexicon::overpassIsimDeseni(),
            $guney,
            $bati,
            $kuzey,
            $dogu,
            min(max($limit, 1), 60),
        );
    }

    /**
     * Merkez + yarıçaptan sınır kutusu: [güney, batı, kuzey, doğu].
     *
     * Boylam derecesi enleme göre daralır (kutupta sıfıra iner), o yüzden
     * kosinüsle bölünür; kosinüs sıfıra yaklaşırken kutunun dünyayı sarmaması
     * için alt sınır konur.
     *
     * @return array{string, string, string, string}
     */
    private function sinirKutusu(float $lat, float $lng, int $metre): array
    {
        $enlemDelta = $metre / 111_320.0;
        $boylamDelta = $metre / (111_320.0 * max(cos(deg2rad($lat)), 0.01));

        return [
            number_format($lat - $enlemDelta, 6, '.', ''),
            number_format($lng - $boylamDelta, 6, '.', ''),
            number_format($lat + $enlemDelta, 6, '.', ''),
            number_format($lng + $boylamDelta, 6, '.', ''),
        ];
    }

    /**
     * Overpass "elements" yanıtını DiscoveredBusiness listesine çevirir (saf —
     * ağ yok, test edilebilir). Adsız kayıtlar atlanır; web sitesi varsa alınır
     * (e-posta ayrı zenginleştirmeyle siteden çıkarılır).
     *
     * @param  list<array<string, mixed>>  $elements
     * @param  array{key: string, tr: string, en: string, osm?: string, local?: string}  $trade
     * @return list<DiscoveredBusiness>
     */
    public function mapElements(array $elements, string $country, string $city, array $trade): array
    {
        $out = [];

        foreach ($elements as $element) {
            $tags = $element['tags'] ?? [];
            $name = $tags['name'] ?? null;
            if (! is_string($name) || $name === '') {
                continue;
            }

            $out[] = new DiscoveredBusiness(
                name: $name,
                category: $trade['en'] ?? null,
                country: $country,
                city: $tags['addr:city'] ?? $city,
                website: $tags['website'] ?? $tags['contact:website'] ?? null,
                externalId: 'osm-'.($element['type'] ?? 'x').'-'.($element['id'] ?? ''),
                sector: $trade['key'] ?? null,
            );
        }

        return $out;
    }

    /** Overpass sorgusuna enjeksiyonu önlemek için etiket anahtar/değerini temizle. */
    private function esc(string $value): string
    {
        return str_replace(['"', '\\', '[', ']', ';', '(', ')'], '', $value);
    }
}
