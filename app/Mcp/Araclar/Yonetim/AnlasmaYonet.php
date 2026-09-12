<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Enums\DealStatus;
use App\Models\Deal;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_anlasma_yonet')]
#[Title('Anlaşmalar & İtiraz Yönetimi — Pazaryeri işlemlerini ve sorunlu anlaşmaları incele')]
#[Description(
    'Nisoya pazaryerinde alıcı ve satıcı arasındaki anlaşmaları (Deal) görüntüler, '.
    'özellikle sorun bildirilmiş (dispute) işlemleri inceler ve durumunu günceller. '.
    'islem="listele" (anlaşmaları listeler), islem="detay" (belirli bir anlaşmayı inceler), '.
    'islem="durum_guncelle" (tamamlandi/iptal/sorunlu durumuna getirir).'
)]
class AnlasmaYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "listele", "detay", "durum_guncelle".')
                ->required(),
            'anlasma_id' => $schema->integer()
                ->description('İncelenecek veya güncellenecek anlaşma ID numarası.'),
            'sadece_sorunlular' => $schema->boolean()
                ->description('Yalnızca sorun bildirilmiş (status="sorunlu") anlaşmaları getir.'),
            'yeni_durum' => $schema->string()
                ->description('Hedef durum: "tamamlandi", "iptal", "sorunlu".'),
            'limit' => $schema->integer()
                ->description('Listelenecek en fazla anlaşma adedi (varsayılan 15).'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'listele'));

        return match ($islem) {
            'detay' => $this->detay($request),
            'durum_guncelle' => $this->durumGuncelle($request),
            default => $this->listele($request),
        };
    }

    /** @return array<string, mixed> */
    private function listele(Request $request): array
    {
        $query = Deal::query()->with(['buyer:id,name,email', 'seller:id,name,email', 'listing:id,title,price,currency']);

        if ($request->get('sadece_sorunlular')) {
            $query->where('status', DealStatus::Sorunlu);
        }

        $limit = min(50, max(1, (int) $request->get('limit', 15)));
        $anlasmalar = $query->latest('id')->limit($limit)->get();

        return [
            'toplam_sorunlu_adet' => Deal::query()->where('status', DealStatus::Sorunlu)->count(),
            'listelenen_adet' => $anlasmalar->count(),
            'anlasmalar' => $anlasmalar->map(fn (Deal $d) => [
                'id' => $d->id,
                'alici' => $d->buyer ? $d->buyer->name : 'Bilinmiyor',
                'alici_email' => $d->buyer ? $d->buyer->email : null,
                'satici' => $d->seller ? $d->seller->name : 'Bilinmiyor',
                'satici_email' => $d->seller ? $d->seller->email : null,
                'ilan_id' => $d->listing_id,
                'ilan_baslik' => $d->listing ? $d->listing->title : 'İlan Silinmiş',
                'tutar' => $d->amount ? number_format((float) $d->amount, 2).' '.$d->currency : 'Görüşülür',
                'durum' => $d->status->value,
                'durum_etiketi' => $d->status->getLabel(),
                'sorun_notu' => $d->dispute_note,
                'olusturulma_tarihi' => $d->created_at?->toIso8601String(),
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function detay(Request $request): array
    {
        $id = (int) $request->get('anlasma_id');
        $deal = Deal::with(['buyer', 'seller', 'listing'])->find($id);

        if (! $deal) {
            return [
                'basarili' => false,
                'mesaj' => "ID'si {$id} olan anlaşma bulunamadı.",
            ];
        }

        return [
            'basarili' => true,
            'anlasma' => [
                'id' => $deal->id,
                'alici' => [
                    'id' => $deal->buyer_id,
                    'name' => $deal->buyer ? $deal->buyer->name : null,
                    'email' => $deal->buyer ? $deal->buyer->email : null,
                ],
                'satici' => [
                    'id' => $deal->seller_id,
                    'name' => $deal->seller ? $deal->seller->name : null,
                    'email' => $deal->seller ? $deal->seller->email : null,
                ],
                'ilan' => [
                    'id' => $deal->listing_id,
                    'title' => $deal->listing ? $deal->listing->title : null,
                    'price' => $deal->listing ? $deal->listing->price : null,
                ],
                'amount' => $deal->amount ? number_format((float) $deal->amount, 2).' '.$deal->currency : 'Görüşülür',
                'status' => $deal->status->value,
                'status_etiket' => $deal->status->getLabel(),
                'dispute_note' => $deal->dispute_note,
                'accepted_at' => $deal->accepted_at?->toIso8601String(),
                'completed_at' => $deal->completed_at?->toIso8601String(),
                'cancelled_at' => $deal->cancelled_at?->toIso8601String(),
                'disputed_at' => $deal->disputed_at?->toIso8601String(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function durumGuncelle(Request $request): array
    {
        $id = (int) $request->get('anlasma_id');
        $deal = Deal::find($id);

        if (! $deal) {
            return [
                'basarili' => false,
                'mesaj' => "ID'si {$id} olan anlaşma bulunamadı.",
            ];
        }

        $yeniStr = strtolower((string) $request->get('yeni_durum'));
        $yeniStatus = match ($yeniStr) {
            'tamamlandi', 'completed' => DealStatus::Tamamlandi,
            'iptal', 'cancelled' => DealStatus::Iptal,
            'sorunlu', 'disputed' => DealStatus::Sorunlu,
            default => null,
        };

        if ($yeniStatus === null) {
            return [
                'basarili' => false,
                'mesaj' => "Geçersiz durum '{$yeniStr}'. Geçerli değerler: tamamlandi, iptal, sorunlu.",
            ];
        }

        $deal->status = $yeniStatus;
        if ($yeniStatus === DealStatus::Tamamlandi) {
            $deal->completed_at = now();
        } elseif ($yeniStatus === DealStatus::Iptal) {
            $deal->cancelled_at = now();
        } elseif ($yeniStatus === DealStatus::Sorunlu) {
            $deal->disputed_at = now();
        }

        $deal->save();

        return [
            'basarili' => true,
            'mesaj' => "Anlaşma (#{$id}) durumu '{$yeniStatus->getLabel()}' olarak güncellendi.",
            'yeni_durum' => $yeniStatus->value,
            'yeni_durum_etiketi' => $yeniStatus->getLabel(),
        ];
    }
}
