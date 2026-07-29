<?php

namespace App\Console\Commands;

use App\Services\Demo\DemoFabrikasi;
use Illuminate\Console\Command;

/**
 * Örnek (demo) veri üretir.
 *
 *   php artisan demo:uret                      # 4 üye, üye başına 2 ilan, GİZLİ
 *   php artisan demo:uret --uye=10 --ilan=3    # daha büyük parti
 *   php artisan demo:uret --gorunur            # ilanlar sitede görünsün ("ÖRNEK" rozetiyle)
 *   php artisan demo:uret --force              # üretimde çalışmaya izin ver
 *
 * VARSAYILAN GİZLİ. Bu şemada bir ilanın herkese açık görünmesinin TEK koşulu
 * `status === 'aktif'`; demo ilanlar `taslak` doğar ve on dört herkese açık
 * sorgu noktasının hepsi onları görmez. Yani varsayılan çağrı üretim sitesinde
 * hiçbir şeyi değiştirmez.
 */
class DemoUret extends Command
{
    protected $signature = 'demo:uret
                            {--uye=4 : Üretilecek demo üye sayısı}
                            {--ilan=2 : Üye başına ilan sayısı}
                            {--gorunur : İlanlar sitede görünsün (aktif); varsayılan taslak}
                            {--force : Üretim ortamında çalışmaya izin ver}';

    protected $description = 'Örnek (demo) veri üretir — üye, ilan, görsel, sohbet, anlaşma, değerlendirme';

    public function handle(DemoFabrikasi $fabrika): int
    {
        $uye = max(1, (int) $this->option('uye'));
        $ilan = max(0, (int) $this->option('ilan'));
        $gorunur = (bool) $this->option('gorunur');

        /*
         * ÜRETİM KAPISI. Bu depoda başka hiçbir komutta ortam kontrolü yok ve
         * çoğunda gerek de yok. Burada var çünkü sahte veri üretmek, canlı bir
         * pazaryerinde diğer bakım komutlarından farklı bir şey: yanlışlıkla
         * çalıştırılması geri alınabilir ama fark edilmeyebilir.
         */
        if (app()->isProduction() && ! $this->option('force')) {
            $this->components->error('Üretim ortamı. Gerçekten istiyorsan --force ekle.');
            $this->line('  Üretilen her şey deftere yazılır ve `demo:sil` ile eksiksiz geri alınabilir.');

            return self::FAILURE;
        }

        if ($gorunur) {
            $this->components->warn('GÖRÜNÜR kip: demo ilanlar sitede yayınlanacak.');
            $this->line('  Kartlarda ve detay sayfasında "ÖRNEK" işareti çıkar, mesaj gönderilemez.');
            $this->line('  Kâhya teşhisi bu ilanları saymaz — "gerçek envanter" ölçüsü bozulmaz.');
        }

        $sonuc = $fabrika->uret($uye, $ilan, $gorunur);

        $this->components->info("Demo partisi üretildi: {$sonuc['parti']}");
        $this->table(
            ['Üye', 'İlan', 'Görsel', 'Sohbet', 'Anlaşma', 'Değerlendirme'],
            [[$sonuc['uye'], $sonuc['ilan'], $sonuc['gorsel'], $sonuc['sohbet'], $sonuc['anlasma'], $sonuc['degerlendirme']]],
        );
        $this->line("  Geri almak için: php artisan demo:sil {$sonuc['parti']}");

        return self::SUCCESS;
    }
}
