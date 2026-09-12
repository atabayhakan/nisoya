<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Support\Settings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_duyuru_yonet')]
#[Title('Duyuru Bandı Yönetimi — Üst şerit duyurusunu oku veya güncelle')]
#[Description(
    'Sitenin en üstündeki tek satırlık duyuru bandını okur veya günceller. '.
    'islem="oku" mevcut durumu döner. islem="guncelle" ile aktif, metin, link, link_metni, renk ve kapatilabilir alanları değiştirilebilir.'
)]
class DuyuruYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('Gerçekleştirilecek işlem: "oku" veya "guncelle".')
                ->required(),
            'aktif' => $schema->boolean()
                ->description('Duyuru bandının sitede görünüp görünmeyeceği.'),
            'metin' => $schema->string()
                ->description('Duyuru metni (en fazla 300 karakter).'),
            'link' => $schema->string()
                ->description('Tıklanınca gidilecek URL bağlantısı.'),
            'link_metni' => $schema->string()
                ->description('Bağlantı metni (örn: "Detaylar →", "Hemen İncele").'),
            'renk' => $schema->string()
                ->description('Şerit rengi: "marka" (yeşil), "uyari" (amber), "onemli" (kırmızı).'),
            'kapatilabilir' => $schema->boolean()
                ->description('Ziyaretçinin çarpı ikonuna basarak duyuruyu gizleyebilmesi.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'oku'));

        if ($islem === 'guncelle') {
            $guncellenecek = [];

            if ($request->has('aktif')) {
                $guncellenecek['duyuru.aktif'] = $request->get('aktif') ? '1' : '0';
            }
            if ($request->has('metin')) {
                $guncellenecek['duyuru.metin'] = (string) $request->get('metin');
            }
            if ($request->has('link')) {
                $guncellenecek['duyuru.link'] = (string) $request->get('link');
            }
            if ($request->has('link_metni')) {
                $guncellenecek['duyuru.link_metni'] = (string) $request->get('link_metni');
            }
            if ($request->has('renk')) {
                $renk = (string) $request->get('renk');
                if (in_array($renk, ['marka', 'uyari', 'onemli'], true)) {
                    $guncellenecek['duyuru.renk'] = $renk;
                }
            }
            if ($request->has('kapatilabilir')) {
                $guncellenecek['duyuru.kapatilabilir'] = $request->get('kapatilabilir') ? '1' : '0';
            }

            if ($guncellenecek !== []) {
                Settings::setMany($guncellenecek);
            }

            return [
                'basarili' => true,
                'mesaj' => 'Duyuru bandı ayarları başarıyla güncellendi.',
                'guncel_durum' => [
                    'aktif' => Settings::get('duyuru.aktif') === '1',
                    'metin' => Settings::get('duyuru.metin') ?? '',
                    'link' => Settings::get('duyuru.link') ?? '',
                    'link_metni' => Settings::get('duyuru.link_metni') ?? '',
                    'renk' => Settings::get('duyuru.renk') ?: 'marka',
                    'kapatilabilir' => (Settings::get('duyuru.kapatilabilir') ?? '1') === '1',
                ],
            ];
        }

        return [
            'basarili' => true,
            'aktif' => Settings::get('duyuru.aktif') === '1',
            'metin' => Settings::get('duyuru.metin') ?? '',
            'link' => Settings::get('duyuru.link') ?? '',
            'link_metni' => Settings::get('duyuru.link_metni') ?? '',
            'renk' => Settings::get('duyuru.renk') ?: 'marka',
            'kapatilabilir' => (Settings::get('duyuru.kapatilabilir') ?? '1') === '1',
        ];
    }
}
