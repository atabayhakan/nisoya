<?php

namespace App\Mcp\Araclar\Demo;

use App\Services\Demo\DemoDefteri;
use App\Services\Demo\DemoTemizleyici;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

/**
 * Üretilmiş bir demo partisini geri alır.
 *
 * `#[IsDestructive]` gerçek bir bilgi taşıyor: bu araç veri siler. Ama yalnız
 * DEFTERDE YAZAN kayıtları siler — defter dışı hiçbir şeye dokunamaz, yani
 * gerçek veriyi silmesi mümkün değil.
 */
#[Name('demo-sil')]
#[Title('Örnek veriyi geri al')]
#[Description(
    'Bir örnek (demo) veri partisini kayıtlarıyla ve diskteki dosyalarıyla birlikte siler. '.
    'YALNIZ DEFTERDE YAZAN kayıtlara dokunur — gerçek veriyi silemez. '.
    'Parti kimliğini demo-durum aracından alabilirsin; hepsi=true ile bütün partiler silinir.'
)]
#[IsDestructive]
class DemoSil extends DemoAraci
{
    public function __construct(
        private readonly DemoDefteri $defter,
        private readonly DemoTemizleyici $temizleyici,
    ) {}

    public function shouldRegister(): bool
    {
        return self::kapiAcikMi();
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'parti' => $schema->string()->description('Silinecek parti kimliği (demo-durum aracından).'),
            'hepsi' => $schema->boolean()->description('true ise bütün demo partileri silinir.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        if ((bool) $request->get('hepsi', false)) {
            return $this->temizleyici->hepsiniSil();
        }

        $parti = trim((string) $request->get('parti', ''));

        if ($parti === '') {
            return [
                'hata' => 'Parti kimliği gerekli. demo-durum aracıyla listeyi gör, ya da hepsi=true ver.',
                'partiler' => $this->defter->partiler(),
            ];
        }

        if (! $this->defter->partiVarMi($parti)) {
            return [
                'hata' => "Defterde [{$parti}] diye bir parti yok.",
                'partiler' => $this->defter->partiler(),
            ];
        }

        return $this->temizleyici->sil($parti);
    }
}
