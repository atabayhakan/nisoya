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

#[Name('nisoya_ilan_ara')]
#[Title('İlan Arama — Nisoya pazarındaki ilanları sorgula')]
#[Description(
    'Nisoya üzerindeki ilanları başlık, açıklama, ülke, şehir veya duruma göre filtreleyerek arar. '.
    'Örnek: "Berlin\'deki emlak ilanları", "Almanya oto tamir", "Onay bekleyen ilanlar".'
)]
class IlanArama extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'arama' => $schema->string()
                ->description('Aranacak kelime veya ifade (başlık ve açıklamada aranır).'),
            'durum' => $schema->string()
                ->description('İlan durumu: aktif, beklemede, taslak, reddedildi, hepsi. Varsayılan: aktif.'),
            'ulke_kodu' => $schema->string()
                ->description('2 harfli ISO ülke kodu (ör. DE, FR, NL, AT, US, TR).'),
            'sehir' => $schema->string()
                ->description('Şehir adı (ör. Berlin, Köln, Frankfurt, Paris).'),
            'limit' => $schema->integer()
                ->description('Döndürülecek maksimum ilan sayısı (1-50 arası, varsayılan 15).'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $limit = min(50, max(1, (int) $request->get('limit', 15)));
        $query = Listing::query()->with(['user:id,name,email', 'category:id,name']);

        $durum = (string) $request->get('durum', 'aktif');
        if ($durum !== 'hepsi') {
            $statusEnum = match (strtolower($durum)) {
                'aktif', 'active' => ListingStatus::Aktif,
                'beklemede', 'pending' => ListingStatus::Beklemede,
                'taslak', 'draft' => ListingStatus::Taslak,
                'reddedildi', 'rejected' => ListingStatus::Reddedildi,
                'pasif', 'passive' => ListingStatus::Pasif,
                default => ListingStatus::Aktif,
            };
            $query->where('status', $statusEnum);
        }

        if ($request->filled('arama')) {
            $arama = (string) $request->get('arama');
            $query->where(function ($q) use ($arama) {
                $q->where('title', 'like', "%{$arama}%")
                    ->orWhere('description', 'like', "%{$arama}%");
            });
        }

        if ($request->filled('ulke_kodu')) {
            $query->where('country_code', strtoupper((string) $request->get('ulke_kodu')));
        }

        if ($request->filled('sehir')) {
            $query->where('city', 'like', '%'.$request->get('sehir').'%');
        }

        $ilanlar = $query->latest('id')->limit($limit)->get();

        return [
            'toplam_bulunan' => $ilanlar->count(),
            'filtreler' => [
                'durum' => $durum,
                'arama' => $request->get('arama'),
                'ulke_kodu' => $request->get('ulke_kodu'),
                'sehir' => $request->get('sehir'),
                'limit' => $limit,
            ],
            'ilanlar' => $ilanlar->map(fn (Listing $i) => [
                'id' => $i->id,
                'baslik' => $i->title,
                'fiyat' => $i->price ? number_format((float) $i->price, 2).' '.($i->currency ?? 'EUR') : 'Belirtilmedi',
                'ulke' => $i->country_code,
                'sehir' => $i->city,
                'durum' => $i->status->getLabel(),
                'durum_kodu' => $i->status->value,
                'kategori' => $i->category->name ?? 'Kategorisiz',
                'satici' => $i->user->name ?? 'Anonim',
                'dolandiricilik_uyarisi' => $i->fraud_reason,
                'tarih' => $i->created_at->toIso8601String() ?? null,
            ])->all(),
        ];
    }
}
