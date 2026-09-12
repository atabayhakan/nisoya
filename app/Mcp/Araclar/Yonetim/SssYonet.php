<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Models\SssSorusu;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_sss_yonet')]
#[Title('SSS Yönetimi — Sıkça Sorulan Soruları listele, ekle, güncelle veya sil')]
#[Description(
    'Nisoya Sıkça Sorulan Sorular (SSS) içeriğini yönetir. '.
    'islem: "listele" (tüm soruları arar/listeler), "ekle" (yeni soru ve cevap ekler), '.
    '"guncelle" (mevcut soruyu düzenler), "sil" (soruyu kaldırır).'
)]
class SssYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "listele", "ekle", "guncelle", "sil".')
                ->required(),
            'id' => $schema->integer()
                ->description('Güncellenecek veya silinecek SSS ID numarası.'),
            'soru' => $schema->string()
                ->description('SSS soru metni.'),
            'cevap' => $schema->string()
                ->description('SSS cevap metni.'),
            'is_active' => $schema->boolean()
                ->description('Sorunun sitede yayında olup olmadığı.'),
            'sort_order' => $schema->integer()
                ->description('Görüntülenme sıra numarası.'),
            'arama' => $schema->string()
                ->description('Listeleme sırasında soru içinde aranacak kelime.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'listele'));

        return match ($islem) {
            'ekle' => $this->ekle($request),
            'guncelle' => $this->guncelle($request),
            'sil' => $this->sil($request),
            default => $this->listele($request),
        };
    }

    /** @return array<string, mixed> */
    private function listele(Request $request): array
    {
        $query = SssSorusu::query();

        if ($request->has('arama') && blank($request->get('arama')) === false) {
            $arama = (string) $request->get('arama');
            $query->where(function ($q) use ($arama) {
                $q->where('soru', 'like', "%{$arama}%")
                    ->orWhere('cevap', 'like', "%{$arama}%");
            });
        }

        $sorular = $query->orderBy('sort_order')->get();

        return [
            'toplam_adet' => $sorular->count(),
            'sorular' => $sorular->map(fn (SssSorusu $s) => [
                'id' => $s->id,
                'soru' => $s->soru,
                'cevap' => $s->cevap,
                'is_active' => (bool) $s->is_active,
                'sort_order' => $s->sort_order,
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function ekle(Request $request): array
    {
        $soru = trim((string) $request->get('soru', ''));
        $cevap = trim((string) $request->get('cevap', ''));

        if ($soru === '' || $cevap === '') {
            return [
                'basarili' => false,
                'mesaj' => 'Yeni SSS eklemek için "soru" ve "cevap" alanları zorunludur.',
            ];
        }

        $kayit = SssSorusu::create([
            'soru' => $soru,
            'cevap' => $cevap,
            'is_active' => $request->has('is_active') ? (bool) $request->get('is_active') : true,
            'sort_order' => (int) $request->get('sort_order', 0),
        ]);

        return [
            'basarili' => true,
            'mesaj' => 'Yeni SSS sorusu başarıyla eklendi.',
            'id' => $kayit->id,
            'soru' => $kayit->soru,
        ];
    }

    /** @return array<string, mixed> */
    private function guncelle(Request $request): array
    {
        $id = (int) $request->get('id');
        $kayit = SssSorusu::find($id);

        if (! $kayit) {
            return [
                'basarili' => false,
                'mesaj' => "ID'si {$id} olan SSS kaydı bulunamadı.",
            ];
        }

        if ($request->has('soru')) {
            $kayit->soru = trim((string) $request->get('soru'));
        }
        if ($request->has('cevap')) {
            $kayit->cevap = trim((string) $request->get('cevap'));
        }
        if ($request->has('is_active')) {
            $kayit->is_active = (bool) $request->get('is_active');
        }
        if ($request->has('sort_order')) {
            $kayit->sort_order = (int) $request->get('sort_order');
        }

        $kayit->save();

        return [
            'basarili' => true,
            'mesaj' => "SSS sorusu (#{$id}) başarıyla güncellendi.",
            'kayit' => [
                'id' => $kayit->id,
                'soru' => $kayit->soru,
                'cevap' => $kayit->cevap,
                'is_active' => (bool) $kayit->is_active,
                'sort_order' => $kayit->sort_order,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function sil(Request $request): array
    {
        $id = (int) $request->get('id');
        $kayit = SssSorusu::find($id);

        if (! $kayit) {
            return [
                'basarili' => false,
                'mesaj' => "ID'si {$id} olan SSS kaydı bulunamadı.",
            ];
        }

        $soruMetni = $kayit->soru;
        $kayit->delete();

        return [
            'basarili' => true,
            'mesaj' => "SSS sorusu (#{$id}: \"{$soruMetni}\") başarıyla silindi.",
        ];
    }
}
