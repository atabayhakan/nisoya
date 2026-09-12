<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_ilan_detay')]
#[Title('İlan Detayı — Tekil ilan inceleme')]
#[Description(
    'Belirtilen ID\'ye sahip ilanın tam açıklamasını, fiyatını, görsellerini, satıcı profilini '.
    've moderasyon durumunu ayrıntılı olarak getirir.'
)]
class IlanDetay extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ilan_id' => $schema->integer()
                ->description('İncelenecek ilanın benzersiz ID numarası.')
                ->required(),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $id = (int) $request->get('ilan_id');
        $ilan = Listing::with(['user', 'category', 'images'])->find($id);

        if (! $ilan) {
            return [
                'hata' => "ID'si {$id} olan ilan bulunamadı.",
            ];
        }

        return [
            'id' => $ilan->id,
            'baslik' => $ilan->title,
            'slug' => $ilan->slug,
            'tur' => $ilan->type->value ?? 'standart',
            'durum' => $ilan->status->getLabel(),
            'durum_kodu' => $ilan->status->value,
            'fiyat' => $ilan->price ? number_format((float) $ilan->price, 2).' '.($ilan->currency ?? 'EUR') : null,
            'fiyat_birimi' => $ilan->price_unit?->value,
            'ulke_kodu' => $ilan->country_code,
            'sehir' => $ilan->city,
            'aciklama' => $ilan->description,
            'kategori' => $ilan->category?->name,
            'satici' => [
                'id' => $ilan->user?->id,
                'ad' => $ilan->user?->name,
                'email' => $ilan->user?->email,
                'kayit_tarihi' => $ilan->user?->created_at?->toIso8601String(),
            ],
            'gorseller' => $ilan->images->map(function (ListingImage $img): array {
                $path = $img->path_medium ?: $img->path_large;

                return [
                    'id' => $img->id,
                    'dosya_yolu' => $path,
                    'url' => $path ? asset('storage/'.$path) : null,
                ];
            })->all(),
            'guvenlik_ve_denetim' => [
                'dolandiricilik_sebebi' => $ilan->fraud_reason,
                'denetim_zamani' => $ilan->fraud_checked_at?->toIso8601String(),
                'yayinlanma_tarihi' => $ilan->created_at?->toIso8601String(),
                'son_guncelleme' => $ilan->updated_at?->toIso8601String(),
            ],
            'site_linki' => url('/ilan/'.$ilan->slug),
        ];
    }
}
