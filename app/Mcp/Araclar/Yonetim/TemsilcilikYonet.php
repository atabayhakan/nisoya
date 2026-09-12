<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Models\Temsilcilik;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_temsilcilik_yonet')]
#[Title('Temsilcilikler — Dış temsilcilikleri (başkonsolosluk/büyükelçilik) listele veya güncelle')]
#[Description(
    'Nisoya Ülke Rehberi dış temsilciliklerini (başkonsolosluk, büyükelçilik) yönetir. '.
    'islem="listele" (ülke ve şehre göre listeler), '.
    'islem="detay" (temsilcilik_id veya slug ile tam iletişim ve mesai saatlerini inceler), '.
    'islem="guncelle" (telefon, eposta, adres, randevu_url veya mesai saatlerini günceller).'
)]
class TemsilcilikYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "listele", "detay", "guncelle".')
                ->required(),
            'temsilcilik_id' => $schema->integer()
                ->description('İncelenecek veya güncellenecek temsilcilik ID numarası.'),
            'country_code' => $schema->string()
                ->description('Ülke kodu filtresi (örn: DE, FR, AT, NL).'),
            'sehir' => $schema->string()
                ->description('Şehir adı filtresi veya güncellenecek şehir.'),
            'adres' => $schema->string()
                ->description('Güncellenecek fiziksel adres.'),
            'resmi_url' => $schema->string()
                ->description('Güncellenecek resmi web adresi.'),
            'yonlendirme_notu' => $schema->string()
                ->description('Güncellenecek randevu/yönlendirme notu.'),
            'is_active' => $schema->boolean()
                ->description('Aktiflik durumu.'),
            'limit' => $schema->integer()
                ->description('Listelenecek azami temsilcilik sayısı (varsayılan: 20).'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'listele'));

        return match ($islem) {
            'detay' => $this->detay($request),
            'guncelle' => $this->guncelle($request),
            default => $this->listele($request),
        };
    }

    /** @return array<string, mixed> */
    private function listele(Request $request): array
    {
        $query = Temsilcilik::query()->withCount('islemler');

        if ($request->has('country_code') && filled($request->get('country_code'))) {
            $query->where('country_code', strtoupper((string) $request->get('country_code')));
        }

        if ($request->has('sehir') && filled($request->get('sehir'))) {
            $sehir = (string) $request->get('sehir');
            $query->where('sehir', 'like', "%{$sehir}%");
        }

        $limit = min(50, max(1, (int) $request->get('limit', 20)));
        $list = $query->orderBy('country_code')->orderBy('sort_order')->limit($limit)->get();

        return [
            'toplam_bulunan' => $list->count(),
            'temsilcilikler' => $list->map(fn (Temsilcilik $t) => [
                'id' => $t->id,
                'ad' => $t->ad,
                'slug' => $t->slug,
                'tur' => $t->turEtiketi(),
                'ulke' => $t->country_code,
                'sehir' => $t->sehir,
                'adres' => $t->adres,
                'resmi_url' => $t->resmi_url,
                'islem_sayisi' => $t->islemler_count,
                'aktif_mi' => (bool) $t->is_active,
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function detay(Request $request): array
    {
        $id = (int) $request->get('temsilcilik_id');
        $t = Temsilcilik::withCount('islemler')->find($id);

        if (! $t) {
            return [
                'basarili' => false,
                'mesaj' => "ID'si {$id} olan temsilcilik bulunamadı.",
            ];
        }

        return [
            'basarili' => true,
            'temsilcilik' => [
                'id' => $t->id,
                'ad' => $t->ad,
                'slug' => $t->slug,
                'tur' => $t->turEtiketi(),
                'country_code' => $t->country_code,
                'sehir' => $t->sehir,
                'adres' => $t->adres,
                'resmi_url' => $t->resmi_url,
                'yonlendirme_notu' => $t->yonlendirme_notu,
                'islem_sayisi' => $t->islemler_count,
                'is_active' => (bool) $t->is_active,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function guncelle(Request $request): array
    {
        $id = (int) $request->get('temsilcilik_id');
        $t = Temsilcilik::find($id);

        if (! $t) {
            return [
                'basarili' => false,
                'mesaj' => "ID'si {$id} olan temsilcilik bulunamadı.",
            ];
        }

        $fields = ['adres', 'sehir', 'resmi_url', 'yonlendirme_notu'];
        $degisen = [];

        foreach ($fields as $f) {
            if ($request->has($f) && filled($request->get($f))) {
                $t->{$f} = trim((string) $request->get($f));
                $degisen[] = $f;
            }
        }

        if ($request->has('is_active')) {
            $t->is_active = (bool) $request->get('is_active');
            $degisen[] = 'is_active';
        }

        if (empty($degisen)) {
            return [
                'basarili' => false,
                'mesaj' => 'Güncellenecek en az bir alan belirtilmelidir (adres, sehir, resmi_url, yonlendirme_notu, is_active).',
            ];
        }

        $t->save();

        return [
            'basarili' => true,
            'mesaj' => "Temsilcilik (#{$t->id} - {$t->ad}) bilgileri güncellendi.",
            'degisen_alanlar' => $degisen,
        ];
    }
}
