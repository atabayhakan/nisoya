<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Models\IslemTuru;
use App\Models\RehberGeriBildirimi;
use App\Models\TemsilcilikIslemi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_konsolosluk_rehber_yonet')]
#[Title('Konsolosluk Rehber Yönetimi — İşlem içeriklerini, türlerini ve kullanıcı bildirimlerini yönet')]
#[Description(
    'Konsolosluk işlem içeriklerini (TemsilcilikIslemi), işlem türü kategorilerini (IslemTuru) ve kullanıcı geri bildirimlerini yönetir. '.
    'islem="listele" (rehber içeriklerini taslak/yayın durumuna göre listeler), '.
    'islem="detay" (belirli bir işlem içeriğinin evrak, süre, harç ve notlarını getirir), '.
    'islem="durum_guncelle" (içeriği yayına alır veya taslağa çeker), '.
    'islem="bildirimler" (kullanıcılardan gelen "Bu bilgi güncel mi?" geri bildirimlerini listeler), '.
    'islem="turler" (tanımlı standart işlem türü şablonlarını listeler).'
)]
class KonsoloslukRehberYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "listele", "detay", "durum_guncelle", "bildirimler", "turler".')
                ->required(),
            'icerik_id' => $schema->integer()
                ->description('İncelenecek veya güncellenecek TemsilcilikIslemi ID numarası.'),
            'durum' => $schema->string()
                ->description('Listeleme durum filtresi: "taslak", "yayin".'),
            'yeni_durum' => $schema->string()
                ->description('Güncelleme hedef durumu: "taslak", "yayin".'),
            'sadece_incelenmemis' => $schema->boolean()
                ->description('Bildirimlerde yalnızca incelenmemiş olanları listele (varsayılan: true).'),
            'limit' => $schema->integer()
                ->description('Listelenecek kayıt sayısı (varsayılan: 15).'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'listele'));

        return match ($islem) {
            'detay' => $this->detay($request),
            'durum_guncelle' => $this->durumGuncelle($request),
            'bildirimler' => $this->bildirimler($request),
            'turler' => $this->turler(),
            default => $this->listele($request),
        };
    }

    /** @return array<string, mixed> */
    private function listele(Request $request): array
    {
        $query = TemsilcilikIslemi::with(['temsilcilik:id,ad,country_code,sehir', 'islemTuru:id,ad']);

        if ($request->has('durum') && filled($request->get('durum'))) {
            $query->where('status', (string) $request->get('durum'));
        }

        $limit = min(50, max(1, (int) $request->get('limit', 15)));
        $list = $query->latest('id')->limit($limit)->get();

        return [
            'toplam_taslak' => TemsilcilikIslemi::where('status', TemsilcilikIslemi::STATUS_TASLAK)->count(),
            'toplam_yayin' => TemsilcilikIslemi::where('status', TemsilcilikIslemi::STATUS_YAYIN)->count(),
            'listelenen' => $list->count(),
            'icerikler' => $list->map(fn (TemsilcilikIslemi $i) => [
                'id' => $i->id,
                'temsilcilik' => $i->temsilcilik ? $i->temsilcilik->ad : 'Bilinmiyor',
                'ulke' => $i->temsilcilik ? $i->temsilcilik->country_code : null,
                'islem_turu' => $i->islemTuru ? $i->islemTuru->ad : 'Bilinmiyor',
                'status' => $i->status,
                'evrak_sayisi' => count($i->evraklar),
                'sure_metni' => $i->sure_metni,
                'ucret_metni' => $i->ucret_metni,
                'resmi_kaynak_url' => $i->resmi_kaynak_url,
                'dogrulanma_tarihi' => $i->dogrulanma_tarihi ? $i->dogrulanma_tarihi->format('Y-m-d') : null,
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function detay(Request $request): array
    {
        $id = (int) $request->get('icerik_id');
        $i = TemsilcilikIslemi::with(['temsilcilik', 'islemTuru', 'geriBildirimler'])->find($id);

        if (! $i) {
            return [
                'basarili' => false,
                'mesaj' => "ID'si {$id} olan konsolosluk işlem içeriği bulunamadı.",
            ];
        }

        return [
            'basarili' => true,
            'icerik' => [
                'id' => $i->id,
                'temsilcilik' => $i->temsilcilik ? $i->temsilcilik->ad : null,
                'ulke' => $i->temsilcilik ? $i->temsilcilik->country_code : null,
                'islem_turu' => $i->islemTuru ? $i->islemTuru->ad : null,
                'status' => $i->status,
                'evraklar' => $i->evraklar,
                'sure_metni' => $i->sure_metni,
                'ucret_metni' => $i->ucret_metni,
                'notlar' => $i->notlar,
                'resmi_kaynak_url' => $i->resmi_kaynak_url,
                'dogrulanma_tarihi' => $i->dogrulanma_tarihi ? $i->dogrulanma_tarihi->format('Y-m-d') : null,
                'geri_bildirim_sayisi' => $i->geriBildirimler->count(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function durumGuncelle(Request $request): array
    {
        $id = (int) $request->get('icerik_id');
        $i = TemsilcilikIslemi::find($id);

        if (! $i) {
            return [
                'basarili' => false,
                'mesaj' => "ID'si {$id} olan işlem içeriği bulunamadı.",
            ];
        }

        $yeni = strtolower((string) $request->get('yeni_durum'));
        if (! in_array($yeni, [TemsilcilikIslemi::STATUS_TASLAK, TemsilcilikIslemi::STATUS_YAYIN], true)) {
            return [
                'basarili' => false,
                'mesaj' => "Geçersiz durum '{$yeni}'. 'taslak' veya 'yayin' girilmelidir.",
            ];
        }

        $i->status = $yeni;
        if ($yeni === TemsilcilikIslemi::STATUS_YAYIN) {
            $i->dogrulanma_tarihi = now();
        }
        $i->save();

        return [
            'basarili' => true,
            'mesaj' => "İşlem içeriği (#{$id}) durumu '{$yeni}' olarak güncellendi.",
            'yeni_durum' => $yeni,
        ];
    }

    /** @return array<string, mixed> */
    private function bildirimler(Request $request): array
    {
        $query = RehberGeriBildirimi::with(['islem.temsilcilik', 'islem.islemTuru']);

        $sadeceIncelenmemis = $request->has('sadece_incelenmemis') ? (bool) $request->get('sadece_incelenmemis') : true;
        if ($sadeceIncelenmemis) {
            $query->where('incelendi', false);
        }

        $limit = min(50, max(1, (int) $request->get('limit', 15)));
        $bildirimler = $query->latest('id')->limit($limit)->get();

        return [
            'toplam_bekleyen' => RehberGeriBildirimi::where('incelendi', false)->count(),
            'listelenen' => $bildirimler->count(),
            'bildirimler' => $bildirimler->map(fn (RehberGeriBildirimi $b) => [
                'id' => $b->id,
                'islem_id' => $b->temsilcilik_islemi_id,
                'temsilcilik' => $b->islem && $b->islem->temsilcilik ? $b->islem->temsilcilik->ad : 'Bilinmiyor',
                'islem_turu' => $b->islem && $b->islem->islemTuru ? $b->islem->islemTuru->ad : 'Bilinmiyor',
                'tur' => $b->tur,
                'tur_etiketi' => $b->turEtiketi(),
                'mesaj' => $b->metin,
                'incelendi' => (bool) $b->incelendi,
                'tarih' => $b->created_at ? $b->created_at->toIso8601String() : null,
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function turler(): array
    {
        $turler = IslemTuru::withCount('islemler')->orderBy('sort_order')->get();

        return [
            'toplam_tur' => $turler->count(),
            'turler' => $turler->map(fn (IslemTuru $t) => [
                'id' => $t->id,
                'ad' => $t->ad,
                'slug' => $t->slug,
                'aciklama' => $t->aciklama,
                'kapsanan_temsilcilik' => $t->islemler_count,
                'is_active' => (bool) $t->is_active,
            ])->all(),
        ];
    }
}
