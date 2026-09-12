<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Enums\FeatureRequestStatus;
use App\Models\FeatureRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_one_cikarma_yonet')]
#[Title('Öne Çıkarma Talepleri — Vitrin başvurularını listele, onayla veya reddet')]
#[Description(
    'İlanların vitrinde öne çıkması için yapılan başvuruları (FeatureRequest) yönetir. '.
    'islem="listele" (talepleri listeler), '.
    'islem="karar_ver" (talep_id ve karar="onayla"|"reddet" ile talebi işler ve vitrin süresini uzatır).'
)]
class OneCikarmaYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "listele", "karar_ver".')
                ->required(),
            'talep_id' => $schema->integer()
                ->description('Karar verilecek öne çıkarma talebi ID numarası.'),
            'karar' => $schema->string()
                ->description('Karar: "onayla" veya "reddet".'),
            'sadece_bekleyenler' => $schema->boolean()
                ->description('Yalnızca onay bekleyen talepleri listele (varsayılan: true).'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'listele'));

        return match ($islem) {
            'karar_ver' => $this->kararVer($request),
            default => $this->listele($request),
        };
    }

    /** @return array<string, mixed> */
    private function listele(Request $request): array
    {
        $query = FeatureRequest::with(['listing:id,title,price,currency,is_featured,featured_until', 'user:id,name,email']);

        $sadeceBekleyen = $request->has('sadece_bekleyenler') ? (bool) $request->get('sadece_bekleyenler') : true;
        if ($sadeceBekleyen) {
            $query->where('status', FeatureRequestStatus::Beklemede);
        }

        $talepler = $query->latest('id')->limit(20)->get();

        return [
            'toplam_bekleyen' => FeatureRequest::where('status', FeatureRequestStatus::Beklemede)->count(),
            'listelenen' => $talepler->count(),
            'talepler' => $talepler->map(fn (FeatureRequest $t) => [
                'id' => $t->id,
                'ilan_id' => $t->listing_id,
                'ilan_baslik' => $t->listing ? $t->listing->title : 'İlan Yok',
                'talep_eden' => $t->user ? $t->user->name : 'Bilinmiyor',
                'talep_eden_email' => $t->user ? $t->user->email : null,
                'gun_sayisi' => $t->days,
                'durum' => $t->status->value,
                'durum_etiketi' => $t->status->getLabel(),
                'mevcut_one_cikan_mi' => $t->listing ? (bool) $t->listing->is_featured : false,
                'mevcut_bitis_tarihi' => $t->listing && $t->listing->featured_until ? $t->listing->featured_until->toIso8601String() : null,
                'talep_tarihi' => $t->created_at ? $t->created_at->toIso8601String() : null,
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function kararVer(Request $request): array
    {
        $id = (int) $request->get('talep_id');
        $talep = FeatureRequest::with('listing')->find($id);

        if (! $talep) {
            return [
                'basarili' => false,
                'mesaj' => "ID'si {$id} olan öne çıkarma talebi bulunamadı.",
            ];
        }

        $kararStr = strtolower((string) $request->get('karar'));
        if ($kararStr === 'onayla') {
            $talep->status = FeatureRequestStatus::Onaylandi;
            $talep->save();

            return [
                'basarili' => true,
                'mesaj' => "Öne çıkarma talebi (#{$id}) onaylandı. İlan {$talep->days} gün boyunca vitrinde öne çıkarıldı.",
                'ilan_id' => $talep->listing_id,
                'yeni_bitis_tarihi' => $talep->listing?->fresh()?->featured_until?->toIso8601String(),
            ];
        } elseif ($kararStr === 'reddet') {
            $talep->status = FeatureRequestStatus::Reddedildi;
            $talep->save();

            return [
                'basarili' => true,
                'mesaj' => "Öne çıkarma talebi (#{$id}) reddedildi.",
                'ilan_id' => $talep->listing_id,
            ];
        }

        return [
            'basarili' => false,
            'mesaj' => "Geçersiz karar '{$kararStr}'. 'onayla' veya 'reddet' belirtilmelidir.",
        ];
    }
}
