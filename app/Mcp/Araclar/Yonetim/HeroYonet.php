<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Support\Hero;
use App\Support\Settings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_hero_yonet')]
#[Title('Hero Vitrin Yönetimi — Ana sayfa manşet ve CTA butonlarını oku/güncelle')]
#[Description(
    'Nisoya ana sayfasındaki Hero vitrin metinlerini (rozet, başlık, vurgulu satır, alt başlık, birincil ve ikincil butonlar) '.
    'okur veya günceller. islem="oku" mevcut ayarları döner, islem="guncelle" ile alanlar güncellenir.'
)]
class HeroYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('Gerçekleştirilecek işlem: "oku" veya "guncelle".')
                ->required(),
            'duzen' => $schema->string()
                ->description('Hero yerleşim düzeni (ör: "bento", "split", "minimal").'),
            'rozet' => $schema->string()
                ->description('Başlığın üstündeki küçük çip rozet metni.'),
            'baslik' => $schema->string()
                ->description('1. satır başlık metni.'),
            'vurgu' => $schema->string()
                ->description('Vurgulu satır (marka rengi) metni.'),
            'alt_baslik' => $schema->string()
                ->description('Alt başlık / açıklama metni (maksimum 140 karakter önerilir).'),
            'cta1_etiket' => $schema->string()
                ->description('Birincil eylem butonu etiketi.'),
            'cta1_url' => $schema->string()
                ->description('Birincil buton URL adresi.'),
            'cta2_aktif' => $schema->boolean()
                ->description('İkincil buton gösterilsin mi?'),
            'cta2_etiket' => $schema->string()
                ->description('İkincil eylem butonu etiketi.'),
            'cta2_url' => $schema->string()
                ->description('İkincil buton URL adresi.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'oku'));

        if ($islem === 'guncelle') {
            $guncellenecek = [];

            if ($request->has('duzen')) {
                $duzen = (string) $request->get('duzen');
                if (in_array($duzen, Hero::DUZENLER, true)) {
                    $guncellenecek['hero.duzen'] = $duzen;
                }
            }
            if ($request->has('rozet')) {
                $guncellenecek['hero.rozet'] = (string) $request->get('rozet');
            }
            if ($request->has('baslik')) {
                $guncellenecek['hero.baslik'] = (string) $request->get('baslik');
            }
            if ($request->has('vurgu')) {
                $guncellenecek['hero.vurgu'] = (string) $request->get('vurgu');
            }
            if ($request->has('alt_baslik')) {
                $guncellenecek['hero.alt_baslik'] = (string) $request->get('alt_baslik');
            }
            if ($request->has('cta1_etiket')) {
                $guncellenecek['hero.cta1_etiket'] = (string) $request->get('cta1_etiket');
            }
            if ($request->has('cta1_url')) {
                $guncellenecek['hero.cta1_url'] = (string) $request->get('cta1_url');
            }
            if ($request->has('cta2_aktif')) {
                $guncellenecek['hero.cta2_aktif'] = $request->get('cta2_aktif') ? '1' : '0';
            }
            if ($request->has('cta2_etiket')) {
                $guncellenecek['hero.cta2_etiket'] = (string) $request->get('cta2_etiket');
            }
            if ($request->has('cta2_url')) {
                $guncellenecek['hero.cta2_url'] = (string) $request->get('cta2_url');
            }

            if ($guncellenecek !== []) {
                Settings::setMany($guncellenecek);
            }

            return [
                'basarili' => true,
                'mesaj' => 'Hero vitrin ayarları başarıyla güncellendi.',
                'guncel_durum' => $this->mevcutAyarlar(),
            ];
        }

        return [
            'basarili' => true,
            'hero' => $this->mevcutAyarlar(),
        ];
    }

    /** @return array<string, mixed> */
    private function mevcutAyarlar(): array
    {
        return [
            'duzen' => Hero::duzen(),
            'rozet' => Settings::get('hero.rozet') ?: '',
            'baslik' => Settings::get('hero.baslik') ?: '',
            'vurgu' => Settings::get('hero.vurgu') ?: '',
            'alt_baslik' => Settings::get('hero.alt_baslik') ?: '',
            'cta1_etiket' => Settings::get('hero.cta1_etiket') ?: '',
            'cta1_url' => Settings::get('hero.cta1_url') ?: '',
            'cta2_aktif' => Settings::get('hero.cta2_aktif', '0') === '1',
            'cta2_etiket' => Settings::get('hero.cta2_etiket') ?: '',
            'cta2_url' => Settings::get('hero.cta2_url') ?: '',
            'arkaplan_tipi' => Hero::arkaplanTipi(),
        ];
    }
}
