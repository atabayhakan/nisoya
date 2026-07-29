<?php

namespace App\Mcp\Araclar;

use App\Models\KahyaCalismasi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * Sahibe GERÇEKTEN gönderilmiş raporu defterden okur — TEK satır SELECT,
 * sıfır yeniden hesaplama.
 *
 * Neden ayrı bir araç: "sahip bu sabah ne gördü?" ile "şu an ne oluyor?"
 * farklı sorulardır. Sahiple konuşurken doğru olan birincisidir; onun
 * gördüğü sayıları tekrar hesaplayıp farklı bir sonuç söylemek kafa
 * karıştırır.
 *
 * `gonderildi` alanı ayrıca ÜRETİLDİ ile GÖNDERİLDİ'yi ayırır: rapor
 * üretilip e-posta gönderilemediyse defter yine yazılır ve `hata` dolar.
 * "Rapor gelmiyor" şikâyetinin cevabı çoğunlukla burada.
 */
#[Name('kahya-son-rapor')]
#[Title('Son günlük rapor (defterden)')]
#[Description(
    'Kâhya\'nın sahibe gönderdiği son günlük raporu çalışma defterinden okur: ne zaman koştu, '.
    'kime gitti, gönderilebildi mi, gönderilemediyse sebebi ne, ne kadar sürdü ve o anki tam '.
    'teşhis çıktısı. ÇOK UCUZ (tek satır okuma) ve yeniden hesaplama yapmaz — sahibin gelen '.
    'kutusunda gördüğü sayıların aynısını verir. Geçmişe bakmak için sira parametresini kullan.'
)]
#[IsReadOnly]
class SonRapor extends KahyaAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sira' => $schema->integer()
                ->description('0 = en son rapor, 1 = ondan önceki… Varsayılan 0. En fazla 30 geriye.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function topla(Request $request): array
    {
        $sira = min(30, max(0, (int) $request->get('sira', 0)));

        $kayit = KahyaCalismasi::query()
            ->gunlukRapor()
            ->latest('created_at')
            ->skip($sira)
            ->first();

        if ($kayit === null) {
            $toplam = KahyaCalismasi::query()->gunlukRapor()->count();

            return [
                'bulundu' => false,
                'kayitli_rapor_sayisi' => $toplam,
                'aciklama' => $toplam === 0
                    // null ile 0 farkı: hiç koşmamış olmak bir arızadır.
                    ? 'Kâhya HİÇ çalışmamış. Günlük rapor zamanlanmış bir komuttur '.
                      '(routes/console.php) ve zamanlanmış komutlar sessizce ölür — '.
                      'sunucudaki zamanlayıcı (supervisor/cron) çalışmıyor olabilir.'
                    : "Bu kadar geriye kayıt yok; defterde toplam {$toplam} rapor var.",
            ];
        }

        return [
            'bulundu' => true,
            'sira' => $sira,
            'kosma_zamani' => $kayit->created_at?->toAtomString(),
            'yas_saat' => $kayit->created_at !== null ? (int) $kayit->created_at->diffInHours(now()) : null,
            'gonderildi' => $kayit->gonderildi,
            'alici' => $kayit->alici,
            'hata' => $kayit->hata,
            'sure_ms' => $kayit->sure_ms,
            'teshis' => $kayit->ozet,
        ];
    }
}
