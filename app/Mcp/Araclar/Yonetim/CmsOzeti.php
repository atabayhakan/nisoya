<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Enums\PageStatus;
use App\Models\HomeHighlight;
use App\Models\Page;
use App\Models\SssSorusu;
use App\Support\Hero;
use App\Support\Settings;
use App\Support\Tema;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_cms_ozet')]
#[Title('CMS ve Tasarım Özeti — Tema, Hero, Duyuru, Sayfa ve SSS durumları')]
#[Description(
    'Nisoya platformunun güncel CMS ve tasarım yapılandırmasını tek raporda özetler: '.
    'aktif tema, hero vitrin başlıkları, duyuru bandı aktiflik ve metni, CMS sayfaları ve SSS soru sayıları.'
)]
class CmsOzeti extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'detayli' => $schema->boolean()
                ->description('Detaylı sayfa ve soru listesini de içersin mi? (varsayılan: false)'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $detayli = (bool) $request->get('detayli', false);

        $sayfalar = Page::query()->select(['id', 'title', 'slug', 'status', 'show_in_footer'])->orderBy('sort_order')->get();
        $sssSorulari = SssSorusu::query()->select(['id', 'soru', 'is_active', 'sort_order'])->orderBy('sort_order')->get();

        $sonuc = [
            'aktif_tema' => Tema::aktif(),
            'hero' => [
                'duzen' => Hero::duzen(),
                'rozet' => Settings::get('hero.rozet') ?: '(Tanımsız)',
                'baslik' => Settings::get('hero.baslik') ?: '(Tanımsız)',
                'vurgu' => Settings::get('hero.vurgu') ?: '(Tanımsız)',
                'alt_baslik' => Settings::get('hero.alt_baslik') ?: '(Tanımsız)',
                'arkaplan_tipi' => Hero::arkaplanTipi(),
                'kampanya_aktif' => (Settings::get('hero.kampanya_aktif', '0') === '1'),
            ],
            'duyuru_bandi' => [
                'aktif' => (Settings::get('duyuru.aktif', '0') === '1'),
                'metin' => Settings::get('duyuru.metin') ?: '(Boş)',
                'link' => Settings::get('duyuru.link') ?: null,
                'link_metni' => Settings::get('duyuru.link_metni') ?: null,
                'renk' => Settings::get('duyuru.renk') ?: 'marka',
            ],
            'sayfalar_istatistik' => [
                'toplam' => $sayfalar->count(),
                'yayinda' => $sayfalar->where('status', PageStatus::Yayin)->count(),
                'taslak' => $sayfalar->where('status', PageStatus::Taslak)->count(),
            ],
            'sss_istatistik' => [
                'toplam' => $sssSorulari->count(),
                'aktif' => $sssSorulari->where('is_active', true)->count(),
            ],
            'vurgu_kartlari' => [
                'buyuk_kartlar' => HomeHighlight::where('slot', 'big')->count(),
                'kucuk_kartlar' => HomeHighlight::where('slot', 'small')->count(),
            ],
        ];

        if ($detayli) {
            $sonuc['sayfa_listesi'] = $sayfalar->map(fn (Page $p) => [
                'id' => $p->id,
                'baslik' => $p->title,
                'slug' => $p->slug,
                'durum' => $p->status->value,
                'footer' => $p->show_in_footer,
            ])->all();

            $sonuc['sss_listesi'] = $sssSorulari->map(fn (SssSorusu $s) => [
                'id' => $s->id,
                'soru' => $s->soru,
                'aktif' => $s->is_active,
                'sira' => $s->sort_order,
            ])->all();
        }

        return $sonuc;
    }
}
