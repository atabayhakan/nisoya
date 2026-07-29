<?php

namespace App\Mcp\Araclar\Demo;

use App\Services\Demo\DemoFabrikasi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

/**
 * Sahibin isteğinin karşılığı: "on tane kullanıcı aç, resim yükle,
 * deneme anlaşması yap" dediğinde bunu yapan araç.
 *
 * `shouldRegister()` yüzünden ayar kapalıyken `tools/list`'te HİÇ görünmez.
 */
#[Name('demo-uret')]
#[Title('Örnek veri üret')]
#[Description(
    'Örnek (demo) üye, ilan, görsel, sohbet, anlaşma ve değerlendirme üretir. Üretilen her kayıt '.
    'deftere yazılır ve demo-sil ile eksiksiz geri alınabilir (dosyalar dâhil). '.
    'VARSAYILAN GİZLİ: ilanlar taslak doğar, sitede görünmez, üretim sitesinde hiçbir şey değişmez. '.
    'gorunur=true verilirse ilanlar yayınlanır ama "ÖRNEK" işareti taşır ve onlara mesaj gönderilemez. '.
    'Kâhya teşhisi bu kayıtları hiçbir koşulda saymaz.'
)]
class DemoUret extends DemoAraci
{
    public function __construct(private readonly DemoFabrikasi $fabrika) {}

    /** Ayar kapalıysa araç hiç kaydedilmez — paket bu metodu container'dan çağırır. */
    public function shouldRegister(): bool
    {
        return self::kapiAcikMi();
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'uye' => $schema->integer()->description('Kaç örnek üye üretilsin. Varsayılan 4, en fazla 50.'),
            'ilan' => $schema->integer()->description('Üye başına kaç ilan. Varsayılan 2, en fazla 10.'),
            'gorunur' => $schema->boolean()->description(
                'true ise ilanlar sitede yayınlanır ("ÖRNEK" işaretiyle). Varsayılan false (taslak, görünmez).'
            ),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        // Sınırlar KODDA: şema istemci tarafında bir öneridir. 50×10 bile
        // 500 ilan + 500 görsel demektir; üstü kazara bir sayıyla sunucuyu
        // dakikalarca meşgul ederdi.
        $uye = min(50, max(1, (int) $request->get('uye', 4)));
        $ilan = min(10, max(0, (int) $request->get('ilan', 2)));
        $gorunur = (bool) $request->get('gorunur', false);

        $sonuc = $this->fabrika->uret($uye, $ilan, $gorunur);

        return $sonuc + [
            'gorunur' => $gorunur,
            'ne_yapildi' => $gorunur
                ? 'İlanlar YAYINDA ve "ÖRNEK" işareti taşıyor; bu ilanlara mesaj gönderilemez.'
                : 'İlanlar taslak — sitede görünmüyorlar. Yayınlamak için gorunur=true ile yeni parti üret.',
            'geri_alma' => "Bu partiyi geri almak için demo-sil aracını parti='{$sonuc['parti']}' ile çağır.",
        ];
    }
}
