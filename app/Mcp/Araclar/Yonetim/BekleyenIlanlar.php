<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Enums\ListingStatus;
use App\Models\Listing;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_bekleyen_ilanlar')]
#[Title('Bekleyen İlanlar — Moderasyon ve onay kuyruğu')]
#[Description(
    'Yayınlanmak üzere onay bekleyen (status=beklemede) ilanları getirir. '.
    'İlanın başlığı, açıklaması, satıcısı ve varsa sistemin tespit ettiği şüpheli fraud/ahlak işaretlerini içerir.'
)]
class BekleyenIlanlar extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Kaç adet bekleyen ilan getirilsin (varsayılan 10, en fazla 30).'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $limit = min(30, max(1, (int) $request->get('limit', 10)));

        $ilanlar = Listing::query()
            ->where('status', ListingStatus::Beklemede)
            ->with(['user:id,name,email', 'category:id,name', 'images:id,listing_id,image_path'])
            ->latest('id')
            ->limit($limit)
            ->get();

        return [
            'bekleyen_toplam_adet' => Listing::query()->where('status', ListingStatus::Beklemede)->count(),
            'listelenen_adet' => $ilanlar->count(),
            'ilanlar' => $ilanlar->map(fn (Listing $i) => [
                'id' => $i->id,
                'baslik' => $i->title,
                'aciklama_ozet' => mb_substr((string) $i->description, 0, 180).'...',
                'fiyat' => $i->price ? number_format((float) $i->price, 2).' '.($i->currency ?? 'EUR') : 'Belirtilmedi',
                'konum' => "{$i->city}, {$i->country_code}",
                'satici' => $i->user->name ?? 'Bilinmiyor',
                'satici_email' => $i->user->email ?? null,
                'gorsel_sayisi' => $i->images->count(),
                'dolandiricilik_suphesi' => $i->fraud_reason ?? 'Temiz',
                'denetim_zamani' => $i->fraud_checked_at?->toIso8601String(),
                'olusturulma_tarihi' => $i->created_at->diffForHumans() ?? 'Bilinmiyor',
            ])->all(),
        ];
    }
}
