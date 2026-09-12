<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Models\YasamKategorisi;
use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonuOnerisi;
use App\Models\YasamKonusu;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_yasam_rehberi_yonet')]
#[Title('Yaşam Rehberi Yönetimi — Konuları, ülke içeriklerini ve önerileri yönet')]
#[Description(
    'Nisoya Yaşam Rehberi konularını, ülke içeriklerini ve topluluk önerilerini yönetir. '.
    'islem="konular" (tüm kategori ve yaşam konularını listeler), '.
    'islem="icerikler" (ülke bazlı yaşam rehberi içeriklerini listeler), '.
    'islem="durum_guncelle" (içerik_id ve yeni_durum="taslak"|"yayinda" ile yayın durumunu günceller), '.
    'islem="oneriler" (topluluk önerilerini listeler), '.
    'islem="oneri_karar" (oneri_id ve karar="onayla"|"reddet" ile topluluk önerisini işler).'
)]
class YasamRehberiYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "konular", "icerikler", "durum_guncelle", "oneriler", "oneri_karar".')
                ->required(),
            'icerik_id' => $schema->integer()
                ->description('İncelenecek veya güncellenecek YasamKonuIcerigi ID numarası.'),
            'country_code' => $schema->string()
                ->description('Ülke kodu filtresi (örn: DE, AT, NL).'),
            'yeni_durum' => $schema->string()
                ->description('Hedef durum: "taslak", "yayinda".'),
            'oneri_id' => $schema->integer()
                ->description('Karar verilecek topluluk öneri ID numarası.'),
            'karar' => $schema->string()
                ->description('Öneri kararı: "onayla" veya "reddet".'),
            'limit' => $schema->integer()
                ->description('Listelenecek azami kayıt adedi (varsayılan: 20).'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'konular'));

        return match ($islem) {
            'icerikler' => $this->icerikler($request),
            'durum_guncelle' => $this->durumGuncelle($request),
            'oneriler' => $this->oneriler($request),
            'oneri_karar' => $this->oneriKarar($request),
            default => $this->konular($request),
        };
    }

    /** @return array<string, mixed> */
    private function konular(Request $request): array
    {
        $kategoriler = YasamKategorisi::with(['konular' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return [
            'toplam_kategori' => $kategoriler->count(),
            'toplam_konu' => YasamKonusu::count(),
            'kategoriler' => $kategoriler->map(fn (YasamKategorisi $k) => [
                'id' => $k->id,
                'ad' => $k->ad,
                'slug' => $k->slug,
                'ikon' => $k->ikon ?: '📘',
                'konular' => $k->konular->map(fn (YasamKonusu $konu) => [
                    'id' => $konu->id,
                    'baslik' => $konu->baslik,
                    'slug' => $konu->slug,
                    'kisa_aciklama' => $konu->kisa_aciklama,
                ])->all(),
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function icerikler(Request $request): array
    {
        $query = YasamKonuIcerigi::with(['konu.kategori']);

        if ($request->has('country_code') && filled($request->get('country_code'))) {
            $query->where('country_code', strtoupper((string) $request->get('country_code')));
        }

        $limit = min(50, max(1, (int) $request->get('limit', 20)));
        $list = $query->latest('id')->limit($limit)->get();

        return [
            'toplam_taslak' => YasamKonuIcerigi::where('status', YasamKonuIcerigi::STATUS_TASLAK)->count(),
            'toplam_yayin' => YasamKonuIcerigi::where('status', YasamKonuIcerigi::STATUS_YAYIN)->count(),
            'listelenen' => $list->count(),
            'icerikler' => $list->map(fn (YasamKonuIcerigi $i) => [
                'id' => $i->id,
                'kategori' => $i->konu && $i->konu->kategori ? $i->konu->kategori->ad : 'Genel',
                'konu' => $i->konu ? $i->konu->baslik : 'Bilinmiyor',
                'ulke' => $i->country_code,
                'status' => $i->status,
                'yazan_tur' => $i->yazan_tur,
                'blok_sayisi' => is_array($i->icerik) ? count($i->icerik) : 0,
                'kaynak_url' => $i->kaynak_url,
                'dogrulanma_tarihi' => $i->dogrulanma_tarihi ? $i->dogrulanma_tarihi->format('Y-m-d') : null,
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function durumGuncelle(Request $request): array
    {
        $id = (int) $request->get('icerik_id');
        $i = YasamKonuIcerigi::find($id);

        if (! $i) {
            return [
                'basarili' => false,
                'mesaj' => "ID'si {$id} olan yaşam rehberi içeriği bulunamadı.",
            ];
        }

        $yeni = strtolower((string) $request->get('yeni_durum'));
        if (! in_array($yeni, [YasamKonuIcerigi::STATUS_TASLAK, YasamKonuIcerigi::STATUS_YAYIN], true)) {
            return [
                'basarili' => false,
                'mesaj' => "Geçersiz durum '{$yeni}'. 'taslak' veya 'yayinda' girilmelidir.",
            ];
        }

        $i->status = $yeni;
        if ($yeni === YasamKonuIcerigi::STATUS_YAYIN) {
            $i->dogrulanma_tarihi = now();
        }
        $i->save();

        return [
            'basarili' => true,
            'mesaj' => "Yaşam rehberi içeriği (#{$id}) durumu '{$yeni}' olarak güncellendi.",
            'yeni_durum' => $yeni,
        ];
    }

    /** @return array<string, mixed> */
    private function oneriler(Request $request): array
    {
        $query = YasamKonuOnerisi::with(['icerik.konu', 'kullanici:id,name,email']);

        $limit = min(50, max(1, (int) $request->get('limit', 15)));
        $list = $query->latest('id')->limit($limit)->get();

        return [
            'toplam_bekleyen' => YasamKonuOnerisi::where('durum', YasamKonuOnerisi::DURUM_BEKLIYOR)->count(),
            'listelenen' => $list->count(),
            'oneriler' => $list->map(fn (YasamKonuOnerisi $o) => [
                'id' => $o->id,
                'icerik_id' => $o->yasam_konu_icerigi_id,
                'konu' => $o->icerik && $o->icerik->konu ? $o->icerik->konu->baslik : 'Bilinmiyor',
                'ulke' => $o->icerik ? $o->icerik->country_code : null,
                'onerilen_metin' => $o->onerilen_metin,
                'durum' => $o->durum,
                'kullanici' => $o->kullanici ? $o->kullanici->name : 'Misafir',
                'tarih' => $o->created_at ? $o->created_at->toIso8601String() : null,
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function oneriKarar(Request $request): array
    {
        $id = (int) $request->get('oneri_id');
        $o = YasamKonuOnerisi::find($id);

        if (! $o) {
            return [
                'basarili' => false,
                'mesaj' => "ID'si {$id} olan topluluk önerisi bulunamadı.",
            ];
        }

        $karar = strtolower((string) $request->get('karar'));
        if ($karar === 'onayla') {
            $o->durum = YasamKonuOnerisi::DURUM_ONAYLANDI;
            $o->save();

            return [
                'basarili' => true,
                'mesaj' => "Topluluk önerisi (#{$id}) onaylandı.",
                'yeni_durum' => $o->durum,
            ];
        } elseif ($karar === 'reddet') {
            $o->durum = YasamKonuOnerisi::DURUM_REDDEDILDI;
            $o->save();

            return [
                'basarili' => true,
                'mesaj' => "Topluluk önerisi (#{$id}) reddedildi.",
                'yeni_durum' => $o->durum,
            ];
        }

        return [
            'basarili' => false,
            'mesaj' => "Geçersiz karar '{$karar}'. 'onayla' veya 'reddet' girilmelidir.",
        ];
    }
}
