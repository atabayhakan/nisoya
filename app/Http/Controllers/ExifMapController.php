<?php

namespace App\Http\Controllers;

use App\Models\ListingImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin için EXIF haritası API'si.
 * Filament ExifMapPage bu endpoint'i çağırarak marker verilerini çeker.
 * Public erişim yok — routes/web.php'de 'auth' + 'active.user' + 'admin.role'
 * middleware'leriyle korunur (sadece admin.role gerçek rol kontrolüdür;
 * "/yonetim" URL öneki tek başına koruma sağlamaz).
 */
class ExifMapController extends Controller
{
    /** Kümeleme O(n²) çalıştığı için taranacak görsel sayısı üst sınırı. */
    private const CLUSTER_LIMIT = 3000;

    /**
     * GET /yonetim/harita/gorseller
     *
     * Query params:
     *   - bbox: lat1,lng1,lat2,lng2 (opsiyonel, görünür alan filtresi)
     *   - sensitive: 1 (sadece hassas EXIF olanlar)
     *   - limit: int (default 500, max 2000)
     */
    public function images(Request $request): JsonResponse
    {
        $request->validate([
            'bbox' => 'nullable|string',
            'sensitive' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:2000',
        ]);

        $limit = (int) ($request->input('limit', 500));

        $query = ListingImage::query()
            ->withGps()
            ->with(['listing:id,title,slug,user_id', 'listing.user:id,name,email']);

        // BBox filtresi (görünür harita alanı)
        if ($request->filled('bbox')) {
            $parts = explode(',', $request->input('bbox'));
            if (count($parts) === 4) {
                [$minLat, $minLng, $maxLat, $maxLng] = array_map('floatval', $parts);
                $query->withinBounds($minLat, $maxLat, $minLng, $maxLng);
            }
        }

        // Hassas EXIF filtresi
        if ($request->boolean('sensitive')) {
            $query->withSensitiveExif();
        }

        $images = $query->latest('created_at')->limit($limit)->get();

        // Harita için optimize JSON
            $markers = $images->map(function (ListingImage $img) {
                return [
                    'id' => $img->id,
                    'lat' => (float) $img->gps_lat,
                    'lng' => (float) $img->gps_lng,
                    'thumb' => $img->url('thumb') ?? \Illuminate\Support\Facades\Storage::url($img->path),
                    'had_gps' => $img->had_gps,
                    'sensitive' => $img->has_sensitive_exif,
                    'listing' => $img->listing ? [
                        'id' => $img->listing->id,
                        'title' => $img->listing->title,
                        'url' => route('listings.show', [$img->listing->id, $img->listing->slug ?? null]),
                    ] : null,
                    'user' => $img->listing?->user ? [
                        'id' => $img->listing->user->id,
                        'name' => $img->listing->user->name,
                    ] : null,
                    'camera' => $img->exif_metadata['Make'] ?? null,
                    'model' => $img->exif_metadata['Model'] ?? null,
                    'reverse' => [
                        'country_code' => $img->reverse_country_code,
                        'country_name' => $img->reverse_country_name,
                        'city' => $img->reverse_city,
                        'state' => $img->reverse_state,
                        'label' => $img->reverseLocationLabel(),
                    ],
                    'uploaded_at' => $img->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'count' => $markers->count(),
            'markers' => $markers,
            'bounds' => $this->calculateBounds($images),
        ]);
    }

    /**
     * GET /yonetim/harita/cluster
     * Şehir/ülke bazlı gruplanmış istatistik (duplicate tespiti için).
     */
    public function clusters(Request $request): JsonResponse
    {
        $request->validate([
            'tolerance' => 'nullable|numeric|min:0.0001|max:1',
        ]);

        $tolerance = (float) ($request->input('tolerance', 0.01));
        $result = $this->computeClusters($tolerance);

        return response()->json([
            'total_images' => $result['total_images'],
            'cluster_count' => $result['cluster_count'],
            'tolerance_degrees' => $tolerance,
            'clusters' => $result['clusters'],
        ]);
    }

    /**
     * GET /yonetim/harita/istatistik
     * Genel istatistikler (üste widget'ta kullanılabilir).
     * Kısa süreli cache'lenir — kümeleme O(n²) olduğu için sık widget
     * yenilemelerinde her seferinde yeniden hesaplanmasını önler.
     */
    public function stats(): JsonResponse
    {
        $data = \Illuminate\Support\Facades\Cache::remember('exif_map_stats', 60, function () {
            $result = $this->computeClusters(0.01);

            return [
                'total_gps_images' => $result['total_images'],
                'cluster_count' => $result['cluster_count'],
                'largest_cluster' => $result['clusters'][0] ?? null,
            ];
        });

        return response()->json($data);
    }

    /**
     * Yakın GPS koordinatlarını (~1km hassasiyetle) kümeler — kopya/şüpheli
     * konum tespiti içindir. Bellekte O(n²) çalıştığı için satır sayısı
     * sınırlanır (self::CLUSTER_LIMIT); daha büyük veri kümelerinde tam
     * tarama yerine örnekleme yapılmış olur.
     *
     * @return array{total_images: int, cluster_count: int, clusters: array}
     */
    private function computeClusters(float $tolerance): array
    {
        // ~0.01 derece ~= 1 km (yaklaşık şehir merkezi ölçeği)
        $images = ListingImage::query()->withGps()
            ->latest('created_at')
            ->limit(self::CLUSTER_LIMIT)
            ->get(['id', 'gps_lat', 'gps_lng', 'listing_id']);

        $clusters = [];
        $assigned = [];

        foreach ($images as $i => $img) {
            if (in_array($i, $assigned, true)) {
                continue;
            }

            $cluster = [
                'lat' => (float) $img->gps_lat,
                'lng' => (float) $img->gps_lng,
                'count' => 1,
                'ids' => [$img->id],
                'listing_ids' => [$img->listing_id],
            ];

            // Aynı koordinata yakın diğer görselleri bul
            $near = $images->where('id', '!=', $img->id)
                ->filter(function ($other, $key) use ($img, $tolerance, &$assigned) {
                    if (in_array($key, $assigned, true)) {
                        return false;
                    }
                    $latDiff = abs($other->gps_lat - $img->gps_lat);
                    $lngDiff = abs($other->gps_lng - $img->gps_lng);

                    return $latDiff < $tolerance && $lngDiff < $tolerance;
                });

            foreach ($near as $key => $other) {
                $cluster['count']++;
                $cluster['ids'][] = $other->id;
                $cluster['listing_ids'][] = $other->listing_id;
                $cluster['lat'] = ($cluster['lat'] * ($cluster['count'] - 1) + $other->gps_lat) / $cluster['count'];
                $cluster['lng'] = ($cluster['lng'] * ($cluster['count'] - 1) + $other->gps_lng) / $cluster['count'];
                $assigned[] = $key;
            }

            $clusters[] = $cluster;
            $assigned[] = $i;
        }

        // Çoktan aza sırala (duplicate tespiti için)
        usort($clusters, fn ($a, $b) => $b['count'] <=> $a['count']);

        return [
            'total_images' => $images->count(),
            'cluster_count' => count($clusters),
            'clusters' => array_slice($clusters, 0, 100), // İlk 100 cluster
        ];
    }

    /**
     * Tüm marker'ların kapsadığı dikdörtgen alan.
     */
    private function calculateBounds($images): ?array
    {
        if ($images->isEmpty()) {
            return null;
        }

        $minLat = $images->min('gps_lat');
        $maxLat = $images->max('gps_lat');
        $minLng = $images->min('gps_lng');
        $maxLng = $images->max('gps_lng');

        return [
            'south' => $minLat, 'west' => $minLng,
            'north' => $maxLat, 'east' => $maxLng,
        ];
    }
}