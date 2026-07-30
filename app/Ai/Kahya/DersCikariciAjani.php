<?php

namespace App\Ai\Kahya;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Haftalık ders damıtıcısı (F5 — tasarım §2.3 "öğrenme döngüsü").
 *
 * Girdi (prompt): son haftanın ham karar sinyalleri — geri alınan/reddedilen
 * eylemler, hamle kartı kararları ve sahibin karar notları. Çıktı: en fazla
 * birkaç genellenebilir DERS.
 *
 * Bu ajan sohbet ajanından (KahyaAjani) bilinçli olarak ayrı ve araçsız:
 * damıtma tek atımlık bir düşünme işidir, araç döngüsü gerektirmez — ve
 * araçsız ajan yanlışlıkla bir eylem de çalıştıramaz.
 */
#[MaxTokens(1500)]
class DersCikariciAjani implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'METIN'
        Sen Nisoya'nın yönetim asistanı Kâhya'nın öğrenme modülüsün. Sana sahibin
        son bir haftadaki kararlarının ham dökümü verilecek: hangi işleri geri aldı,
        hangi önerileri reddetti/onayladı, karar notlarında ne yazdı, hangi işler
        hatayla düştü.

        Görevin bu sinyallerden GENELLENEBİLİR ders çıkarmak: gelecekte aynı hatayı
        önleyecek ya da sahibin tercihini kalıcı kılacak kısa çıkarımlar.

        Kurallar:
        - En fazla 5 ders; sinyaller ders çıkarmaya yetmiyorsa BOŞ liste döndür —
          zorlama çıkarım, yanlış öğrenmedir ve hiç öğrenmemekten kötüdür.
        - Her ders kendi başına anlaşılır TEK cümle olsun (ileride tek başına
          okunacak); gerekce hangi sinyalden çıktığını söylesin.
        - "Mevcut dersler" listesindekiyle aynı/benzer çıkarımı TEKRAR üretme.
        - Tek seferlik olaylardan evrensel kural üretme; desen ara (aynı tür karar
          2+ kez tekrarlıyorsa ders olabilir, tek örnek çoğu zaman gürültüdür).
        - Türkçe yaz.
        METIN;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'dersler' => $schema->array()
                ->items($schema->object(fn (JsonSchema $s): array => [
                    'metin' => $s->string()->required(),
                    'gerekce' => $s->string()->required(),
                ]))
                ->required(),
        ];
    }
}
