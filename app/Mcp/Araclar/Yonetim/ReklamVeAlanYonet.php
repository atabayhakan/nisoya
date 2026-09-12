<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Models\Zone;
use App\Services\Ai\GrowthMarketingAiAssistant;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_reklam_ve_alan_yonet')]
#[Title('Reklam & İçerik Alanları — Sitedeki banner alanlarını ve kampanya bloklarını yönet')]
#[Description(
    'Platformun anasayfa, ilan detay ve listeleme sayfalarındaki reklam ve içerik alanlarını (Zone) yönetir. '.
    'islem="alanlari_listele" (tüm alanları, aktiflik durumlarını ve blok sayılarını listeler), '.
    'islem="durum_degistir" (alan_anahtari ve aktif=true/false ile alanı yayına alır veya gizler), '.
    'islem="ai_reklam_uret" (alan_anahtari, kampanya_hedefi ve ton ile yüksek dönüşümlü banner ve CTA üretir), '.
    'islem="blok_ekle" (alan_anahtari ile üretilen ya da özel CTA/reklam bloğunu alana ekler).'
)]
class ReklamVeAlanYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "alanlari_listele", "durum_degistir", "ai_reklam_uret", "blok_ekle".')
                ->required(),
            'alan_anahtari' => $schema->string()
                ->description('Alan anahtarı (Zone key, ör: "anasayfa_orta", "ilan_liste_alti").'),
            'aktif' => $schema->boolean()
                ->description('durum_degistir işlemi için aktiflik durumu.'),
            'kampanya_hedefi' => $schema->string()
                ->description('ai_reklam_uret işlemi için kampanya amacı (örn: "Esnaf Vitrin Sahiplendirme", "Ücretsiz İlan Teşviki").'),
            'ton' => $schema->string()
                ->description('ai_reklam_uret için ton: "samimi", "kurumsal", "aciliyet".'),
            'baslik' => $schema->string()
                ->description('blok_ekle için buton veya başlık metni.'),
            'buton_metni' => $schema->string()
                ->description('blok_ekle için buton etiketi.'),
            'buton_url' => $schema->string()
                ->description('blok_ekle için buton hedef linki.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'alanlari_listele'));

        return match ($islem) {
            'durum_degistir' => $this->durumDegistir($request),
            'ai_reklam_uret' => $this->aiReklamUret($request),
            'blok_ekle' => $this->blokEkle($request),
            default => $this->alanlariListele(),
        };
    }

    /** @return array<string, mixed> */
    private function alanlariListele(): array
    {
        $zones = Zone::query()->orderBy('name')->get();

        $rows = [];
        foreach ($zones as $z) {
            $startsAt = $z->starts_at instanceof \DateTimeInterface ? $z->starts_at->toIso8601String() : null;
            $endsAt = $z->ends_at instanceof \DateTimeInterface ? $z->ends_at->toIso8601String() : null;

            $rows[] = [
                'id' => $z->id,
                'name' => $z->name,
                'key' => $z->key,
                'location_note' => $z->location_note,
                'is_active' => (bool) $z->is_active,
                'blok_sayisi' => count($z->blocks ?? []),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ];
        }

        return [
            'durum' => 'basarili',
            'toplam_alan' => count($rows),
            'aktif_alan_sayisi' => $zones->where('is_active', true)->count(),
            'alanlar' => $rows,
        ];
    }

    /** @return array<string, mixed> */
    private function durumDegistir(Request $request): array
    {
        $key = (string) $request->get('alan_anahtari');
        $zone = Zone::byKey($key) ?: Zone::query()->where('key', $key)->first();

        if (! $zone) {
            return ['durum' => 'hata', 'mesaj' => "'{$key}' anahtarlı reklam/içerik alanı bulunamadı."];
        }

        $aktif = (bool) $request->get('aktif', true);
        $zone->update(['is_active' => $aktif]);

        return [
            'durum' => 'basarili',
            'mesaj' => "'{$zone->name}' alanı ".($aktif ? 'yayına alındı (Aktif).' : 'gizlendi (Pasif).'),
            'alan_anahtari' => $zone->key,
            'is_active' => $aktif,
        ];
    }

    /** @return array<string, mixed> */
    private function aiReklamUret(Request $request): array
    {
        $key = (string) $request->get('alan_anahtari', 'anasayfa_orta');
        $goal = (string) $request->get('kampanya_hedefi', 'Esnaf Vitrin Sahiplendirme');
        $tone = (string) $request->get('ton', 'samimi');

        $assistant = app(GrowthMarketingAiAssistant::class);
        $res = $assistant->generateAdBannerCopy($key, $goal, $tone);

        return [
            'durum' => 'basarili',
            'alan_anahtari' => $key,
            'kampanya_hedefi' => $goal,
            'rozet' => $res['badge'],
            'baslik' => $res['title'],
            'aciklama' => $res['description'],
            'buton_etiketi' => $res['button_label'],
            'buton_url' => $res['button_url'],
            'html_onizleme' => $res['tailwind_preview_html'],
        ];
    }

    /** @return array<string, mixed> */
    private function blokEkle(Request $request): array
    {
        $key = (string) $request->get('alan_anahtari');
        $zone = Zone::byKey($key) ?: Zone::query()->where('key', $key)->first();

        if (! $zone) {
            return ['durum' => 'hata', 'mesaj' => "'{$key}' anahtarlı reklam/içerik alanı bulunamadı."];
        }

        $title = (string) $request->get('baslik');
        $btnText = (string) $request->get('buton_metni', 'İncele');
        $btnUrl = (string) $request->get('buton_url', '/ilanlar');

        if (blank($title)) {
            return ['durum' => 'hata', 'mesaj' => 'Eklenecek blok için "baslik" parametresi zorunludur.'];
        }

        $blocks = $zone->blocks ?? [];
        $blocks[] = [
            'type' => 'cta',
            'data' => [
                'title' => $title,
                'button_text' => $btnText,
                'button_url' => $btnUrl,
            ],
        ];

        $zone->update(['blocks' => $blocks]);

        return [
            'durum' => 'basarili',
            'mesaj' => "'{$zone->name}' alanına yeni CTA kampanya bloğu eklendi.",
            'alan_anahtari' => $zone->key,
            'yeni_blok_sayisi' => count($blocks),
        ];
    }
}
