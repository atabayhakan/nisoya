<?php

namespace App\Mcp\Araclar\Demo;

use App\Mcp\Araclar\KahyaAraci;
use App\Support\Settings;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Throwable;

/**
 * Demo (örnek veri) araçlarının ortak tabanı.
 *
 * ---------------------------------------------------------------------------
 * NEDEN `KahyaAraci`'DAN TÜREMİYOR
 *
 * {@see KahyaAraci} gövdesini salt-okunur kipte çalıştırır ve
 * bu tam olarak istenen şeydir — Kâhya yazamaz. Bu araçlar ise YAZMAK için
 * var. İkisini aynı tabanda birleştirmek, salt-okunurluğu "çoğu zaman açık"
 * bir bayrağa indirgerdi; o bayrağın bir gün yanlış kalması an meselesidir.
 *
 * Bu yüzden ayrılar: iki taban, iki sunucu, iki `.mcp.json` girdisi. Kâhya
 * sunucusunun yazamadığı hâlâ testlerle kanıtlı ve bu dosya ona hiç
 * dokunmuyor.
 *
 * ---------------------------------------------------------------------------
 * KAPI: PANELDEN AÇILAN BİR AYAR
 *
 * Yazma araçları `demo.mcp_acik` ayarı açık değilse KAYDEDİLMEZ — `tools/list`
 * içinde hiç görünmezler. Kapıyı açan şey bir insan tıklamasıdır (Filament'te
 * "Örnek Veri" sayfası), ve bir kez açılır: her çağrıda onay istemek, sahibin
 * "ajana söyleyince yapsın" isteğini boşa çıkarırdı.
 *
 * Ayar KAPALIYKEN {@see DemoDurum} yine görünür ve kapının kapalı olduğunu
 * söyler — sessizce kaybolan bir yetenek, hata ayıklanamaz bir yetenektir.
 */
abstract class DemoAraci extends Tool
{
    public static function kapiAcikMi(): bool
    {
        return Settings::get('demo.mcp_acik') === '1';
    }

    final public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $veri = $this->calistir($request);
        } catch (Throwable $e) {
            report($e);

            // İstisna metni dışarı çıkmaz — gerekçesi KahyaAraci ile aynı:
            // paketin kendi hata yolu APP_DEBUG açıkken ham mesajı yapay
            // zekâya yollar ve sorgu mesajları kullanıcı verisi içerir.
            return Response::error(
                'Demo aracı hata verdi: '.class_basename($e).
                '. Ayrıntı sunucunun log dosyasına yazıldı.'
            );
        }

        return $veri === [] ? Response::text('Sonuç boş.') : Response::structured($veri);
    }

    /** @return array<string, mixed> */
    abstract protected function calistir(Request $request): array;
}
