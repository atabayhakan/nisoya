<?php

namespace Tests\Feature;

use App\Filament\Widgets\SystemHealthWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kuyruk sağlığı göstergesi.
 *
 * ---------------------------------------------------------------------------
 * NE KORUYOR
 *
 * Gösterge eskiden yalnız `kuyruk > 1000` iken uyarıyordu. Ama worker
 * ÖLDÜĞÜNDE binlerce iş birikmez — üç beş iş takılır ve gösterge yeşil kalır.
 * Yani göstergenin en çok işe yarayacağı arıza, göremediği tek arızaydı.
 *
 * 2026-08-12'de canlıda bir ilan görseli kayboldu: `ListingImage` kaydı
 * YALNIZCA `ProcessListingImage` kuyruk işinde oluşuyor, iş koşmazsa görsel
 * hiç doğmuyor ve kimse fark etmiyor.
 *
 * Doğru sinyal SAYI değil YAŞ: sağlıklı kuyrukta iş saniyeler içinde tükenir.
 */
class KuyrukSagligiGostergesiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * phpunit.xml'de QUEUE_CONNECTION=sync. Yaş ölçümü yalnız `database`
         * sürücüsünde anlamlı (jobs tablosu), o yüzden burada açıkça
         * ayarlanıyor — üretimdeki sürücü bu. Bu satır olmadan test, ölçmek
         * istediği dalı hiç çalıştırmıyordu ve sessizce yeşil veriyordu.
         */
        config(['queue.default' => 'database']);
    }

    private function is(int $kacDakikaOnce): void
    {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time() - ($kacDakikaOnce * 60),
            'created_at' => time() - ($kacDakikaOnce * 60),
        ]);
    }

    /** @return array{aciklama: string, ikon: string, renk: string} */
    private function saglik(int $boyut): array
    {
        $m = new \ReflectionMethod(SystemHealthWidget::class, 'kuyrukSagligi');

        return $m->invoke(new SystemHealthWidget, $boyut);
    }

    public function test_bos_kuyruk_saglikli(): void
    {
        $this->assertSame('success', $this->saglik(0)['renk']);
    }

    public function test_taze_is_alarm_vermez(): void
    {
        // Yeni atılmış iş normaldir; yanlış alarm üretmemeli.
        $this->is(0);

        $this->assertSame('success', $this->saglik(1)['renk']);
    }

    public function test_tek_bir_takili_is_bile_alarm_verir(): void
    {
        /*
         * ASIL BEKÇİ. Eski kuralla bu senaryo YEŞİL görünüyordu: kuyrukta
         * yalnız 3 iş var, 1000'in çok altında. Oysa 20 dakikadır bekleyen
         * bir iş, worker'ın çalışmadığının kesin işareti.
         */
        $this->is(20);
        $this->is(19);
        $this->is(18);

        $sonuc = $this->saglik(3);

        $this->assertSame('danger', $sonuc['renk'],
            'Worker ölü ama gösterge hâlâ sağlıklı diyor — eski hata geri gelmiş.');
        $this->assertStringContainsString('worker çalışmıyor', $sonuc['aciklama']);
    }

    public function test_dort_dakika_henuz_alarm_degil(): void
    {
        // Eşik 5 dk: geçici yoğunlukta yanlış alarm vermemeli.
        $this->is(4);

        $this->assertNotSame('danger', $this->saglik(1)['renk']);
    }

    /*
     * ---------------------------------------------------------------------
     * REDIS — ÜRETİMİN GERÇEKTEN KULLANDIĞI SÜRÜCÜ
     *
     * İlk sürümde yalnız `database` dalı vardı ve testi de `database`e
     * ayarlayıp yazmıştım. Test yeşildi ama CANLIDA SÜRÜCÜ REDIS: yaş
     * ölçülemediği için gösterge her hâlükârda "başarılı" diyordu, yani
     * alarm hiç çalışamazdı. Ölçtüğün dalın üretimde koşan dal olduğunu
     * doğrulamadan test yazma.
     * ---------------------------------------------------------------------
     */

    public function test_redis_bos_kuyruk_saglikli(): void
    {
        config(['queue.default' => 'redis']);

        $this->assertSame('success', $this->saglik(0)['renk']);
    }

    public function test_redis_yeni_birikme_hemen_alarm_vermez(): void
    {
        config(['queue.default' => 'redis']);

        // İlk gözlem: kuyruk daha yeni görüldü, tükenip tükenmediği bilinmiyor.
        $sonuc = $this->saglik(7);

        $this->assertNotSame('danger', $sonuc['renk']);
        $this->assertStringContainsString('7 iş bekliyor', $sonuc['aciklama']);
    }

    public function test_redis_azalmayan_kuyruk_alarm_verir(): void
    {
        /*
         * ASIL BEKÇİ (redis kanadı). Redis'te işlerin üzerinde zaman damgası
         * yok, o yüzden yaş ölçülemiyor. Ama "azalmıyor" bilgisi aynı soruya
         * cevap veriyor: worker iş alıyor mu?
         */
        config(['queue.default' => 'redis']);

        // 10 dakika önce 5 iş görülmüş ve o günden beri azalmamış.
        Cache::put('kuyruk_gozlem', ['en_dusuk' => 5, 'zaman' => time() - 600], now()->addHour());

        $sonuc = $this->saglik(5);

        $this->assertSame('danger', $sonuc['renk'],
            'Redis kuyruğu azalmıyor ama gösterge sağlıklı diyor — üretimdeki dal yine kör.');
        $this->assertStringContainsString('worker çalışmıyor', $sonuc['aciklama']);
    }

    public function test_redis_azalan_kuyruk_alarm_vermez(): void
    {
        // Kuyruk tükeniyor: gözlem sıfırlanmalı, alarm çalmamalı.
        config(['queue.default' => 'redis']);

        Cache::put('kuyruk_gozlem', ['en_dusuk' => 40, 'zaman' => time() - 600], now()->addHour());

        $this->assertNotSame('danger', $this->saglik(12)['renk']);
    }

    public function test_kuyruk_bosalinca_gozlem_temizlenir(): void
    {
        /*
         * Temizlenmezse: kuyruk boşalır, sonra yeni bir iş gelir ve ESKİ
         * gözlemin zamanı yüzünden anında yanlış alarm çalar.
         */
        config(['queue.default' => 'redis']);

        Cache::put('kuyruk_gozlem', ['en_dusuk' => 3, 'zaman' => time() - 600], now()->addHour());

        $this->saglik(0);

        $this->assertNull(Cache::get('kuyruk_gozlem'));
    }
}
