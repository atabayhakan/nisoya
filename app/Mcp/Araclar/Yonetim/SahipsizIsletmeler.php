<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Models\OutreachTarget;
use App\Services\Growth\DetectionResult;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_sahipsiz_isletmeler')]
#[Title('Sahiplenilmeyi Bekleyen İşletmeler — Keşfedilen Türk esnafları')]
#[Description(
    'Büyüme motorunun haritalardan tespit ettiği Türk işletmelerini (OutreachTarget) listeler. '.
    'Güven skoru, sektör, şehir ve vitrin ilan durumlarını gösterir.'
)]
class SahipsizIsletmeler extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ulke_kodu' => $schema->string()
                ->description('Ülke kodu (ör. DE, FR, NL, AT).'),
            'sehir' => $schema->string()
                ->description('Şehir adı (ör. Berlin, Köln, Frankfurt).'),
            'min_guven' => $schema->integer()
                ->description('Minimum tespit güven skoru (0-100 arası, varsayılan 60).'),
            'limit' => $schema->integer()
                ->description('Getirilecek maksimum işletme sayısı (1-30 arası, varsayılan 15).'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $limit = min(30, max(1, (int) $request->get('limit', 15)));
        $minGuven = max(0, min(100, (int) $request->get('min_guven', 60)));

        $query = OutreachTarget::query()
            ->where('detection_band', DetectionResult::BAND_TURKISH)
            ->where('detection_confidence', '>=', $minGuven)
            ->with(['listing:id,title,slug,status']);

        if ($request->filled('ulke_kodu')) {
            $query->where('country', strtoupper((string) $request->get('ulke_kodu')));
        }

        if ($request->filled('sehir')) {
            $query->where('city', 'like', '%'.$request->get('sehir').'%');
        }

        $targets = $query->latest('id')->limit($limit)->get();

        return [
            'toplam_esnaf_sayisi' => $targets->count(),
            'filtreler' => [
                'ulke' => $request->get('ulke_kodu'),
                'sehir' => $request->get('sehir'),
                'min_guven' => $minGuven,
            ],
            'isletmeler' => $targets->map(fn (OutreachTarget $t) => [
                'id' => $t->id,
                'isletme_adi' => $t->name,
                'sektor' => $t->category ?: $t->sector,
                'konum' => "{$t->city}, {$t->country}",
                'guven_skoru' => "%{$t->detection_confidence}",
                'website' => $t->website,
                'vitrin_ilani_var_mi' => $t->listing_id !== null,
                'vitrin_ilan_id' => $t->listing_id,
                'vitrin_ilan_baslik' => $t->listing?->title,
                'vitrin_ilan_url' => $t->listing ? url('/ilan/'.$t->listing->slug) : null,
                'kayit_zamani' => $t->created_at?->toIso8601String(),
            ])->all(),
        ];
    }
}
