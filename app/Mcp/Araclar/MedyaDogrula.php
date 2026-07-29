<?php

namespace App\Mcp\Araclar;

use App\Services\Kahya\MedyaDogrulayici;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * Veritabanının "var" dediği medya dosyası diskte gerçekten var mı?
 *
 * Bu aracın var olma sebebi somut: 2026-07-29'da canlıda bir ilanın kapak
 * görselinin üç varyantı da 404 veriyordu. Veritabanı kaydı sapasağlamdı;
 * eksik olan diskteki dosyaydı. Hiçbir ekran, hiçbir log, hiçbir uyarı bunu
 * göstermiyordu — ziyaretçi kırık görsel görüyor, sahip hiçbir şey görmüyordu.
 */
#[Name('kahya-medya-dogrula')]
#[Title('Medya doğrulama — diskte gerçekten var mı?')]
#[Description(
    'İlan görsellerini ve kullanıcı avatarlarını tarayıp veritabanında kayıtlı olduğu hâlde '.
    'diskte BULUNMAYAN dosyaları listeler — yani ziyaretçinin kırık görsel gördüğü içerikleri. '.
    'En yeni kayıttan geriye doğru tarar. Dosya sistemine kayıt başına birkaç kez dokunduğu için '.
    'limiti düşük tut (varsayılan 100). Boş varyant kolonu KAYIP SAYILMAZ: hiç üretilmemiş '.
    'varyant ile silinmiş dosya farklı sorunlardır.'
)]
#[IsReadOnly]
class MedyaDogrula extends KahyaAraci
{
    public function __construct(private readonly MedyaDogrulayici $medya) {}

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Kaç kayıt taransın (en yeniden geriye). Varsayılan 100, en fazla 500.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function topla(Request $request): array
    {
        $limit = min(500, max(1, (int) $request->get('limit', 100)));

        $sonuc = $this->medya->dogrula($limit);

        return ['limit' => $limit] + $sonuc + [
            'ne_yapmali' => $sonuc['kayip'] > 0
                ? 'Kayıp dosyalar yeniden yüklenmeli. Kâhya dosya yazamaz; '.
                  'yükleme yönetim panelinden ya da ilan sahibi tarafından yapılır.'
                : 'Taranan kayıtların hepsinin dosyası yerinde.',
        ];
    }
}
