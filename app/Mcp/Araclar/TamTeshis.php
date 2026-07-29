<?php

namespace App\Mcp\Araclar;

use App\Services\Kahya\KahyaTeshisi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * Günlük raporun ürettiği teşhisin AYNISI, ama şu an hesaplanmış hâli.
 *
 * PAHALI: ~26 SQL + `medya_limit` kadar dosya kontrolü + storage/logs'un
 * tamamının okunması. Günde bir kez koşmak üzere tasarlandı ve üretimde
 * 90 saniyeye yaklaşabileceği varsayılmıştı (bkz. KahyaGunlukRapor docblock).
 *
 * Bu yüzden varsayılan `medya_limit` burada 100 — servisin kendi varsayılanı
 * 500. Etkileşimli bir çağrı, günlük bir toplu işin bütçesini kullanmamalı.
 * Sahibin son gerçek raporunu ücretsiz okumak için `kahya-son-rapor`.
 */
#[Name('kahya-tam-teshis')]
#[Title('Tam teşhis — her şeyi şimdi hesapla')]
#[Description(
    'Nisoya\'nın TAM teşhisi, şu an hesaplanmış: envanter, bekleyen moderasyon işleri, '.
    'diskte kayıp medya dosyaları, son 24 saatin hata imzaları ve doldurulmamış kritik ayarlar. '.
    'PAHALI bir araçtır (onlarca sorgu + yüzlerce dosya kontrolü + log dosyalarının okunması). '.
    'Önce kahya-nabiz\'i dene; tam tablo gerçekten gerekiyorsa bunu çağır. '.
    'Sahibe sabah gönderilmiş raporun kendisini okumak için kahya-son-rapor kullan — o bedava.'
)]
#[IsReadOnly]
class TamTeshis extends KahyaAraci
{
    public function __construct(private readonly KahyaTeshisi $teshis) {}

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'medya_limit' => $schema->integer()
                ->description('Kaç medya kaydı taransın (en yeniden geriye). Varsayılan 100, en fazla 500.'),
            'log_saat' => $schema->integer()
                ->description('Hata kayıtlarında kaç saatlik pencereye bakılsın. Varsayılan 24, en fazla 168.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function topla(Request $request): array
    {
        // Sınırlar KODDA kırpılıyor, şemaya güvenilmiyor: JSON Schema
        // istemci tarafında bir öneri, sunucu tarafında bir garanti değil.
        $medyaLimit = min(500, max(1, (int) $request->get('medya_limit', 100)));
        $logSaat = min(168, max(1, (int) $request->get('log_saat', 24)));

        return [
            'parametreler' => ['medya_limit' => $medyaLimit, 'log_saat' => $logSaat],
        ] + $this->teshis->topla($medyaLimit, $logSaat);
    }
}
