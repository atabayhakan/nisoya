<?php

namespace App\Mcp\Araclar;

use App\Models\KahyaCalismasi;
use App\Services\BackupService;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;

/**
 * Altyapının sessiz arızaları: takılı kuyruk, koşmayan yedek, dolan disk.
 *
 * TEŞHİSİN DEĞİL ALTYAPININ ARACI. `kahya-nabiz` sitenin İÇERİK durumunu
 * söyler ("kaç ilan, kaç bekleyen iş"); burası sitenin ALTINDAKİ makinenin
 * durumunu söyler. İkisi ayrı ayrı bozulur ve ayrı ayrı sessizdir.
 *
 * ---------------------------------------------------------------------------
 * ADMIN PANOSUNDAKİ SAĞLIK WIDGET'I BURADA YENİDEN KULLANILAMADI
 *
 * `SystemHealthWidget::checkCache()` ve `checkStorage()` sağlığı ÖLÇMEK İÇİN
 * YAZAR: `Cache::put(...)` ve `Storage::put('sys_health.txt')`. Bir insan
 * panele bakarken bu doğru bir testtir; Kâhya için sözün ihlalidir. Bu yüzden
 * buradaki kontroller yalnız okuyanlarla sınırlı — önbellek ve depolama
 * "çalışıyor mu" sorusu bilerek yanıtsız bırakıldı.
 * ---------------------------------------------------------------------------
 */
#[Name('kahya-sistem-sagligi')]
#[Title('Sistem sağlığı — altyapı')]
#[Description(
    'Sitenin altındaki makinenin durumu: veritabanı gecikmesi, kuyrukta bekleyen ve başarısız '.
    'iş sayısı, son yedeğin yaşı, boş disk alanı, e-postanın yapılandırılmış olup olmadığı ve '.
    'üretimde hata ayıklamanın açık kalıp kalmadığı. Kuyruğun takılması, yedeğin koşmaması ve '.
    'diskin dolması hiçbir ekranda hata vermeyen, sessizce büyüyen arızalardır. '.
    'İçerik durumu için kahya-nabiz kullan.'
)]
#[IsReadOnly]
class SistemSagligi extends KahyaAraci
{
    public function __construct(private readonly BackupService $yedek) {}

    /** @return array<string, mixed> */
    protected function topla(Request $request): array
    {
        $uretim = app()->environment('production');
        $hataAyiklama = (bool) config('app.debug');

        return [
            'ortam' => (string) app()->environment(),

            'veritabani' => [
                'surucu' => (string) config('database.default'),
                'gecikme_ms' => $this->gecikme(),
            ],

            'kuyruk' => [
                'surucu' => (string) config('queue.default'),
                'bekleyen' => $this->bekleyenIs(),
                'basarisiz' => $this->basarisizIs(),
            ],

            'yedek' => $this->yedekDurumu(),

            'disk' => [
                'bos_bayt' => $bos = (float) (@disk_free_space(base_path()) ?: 0),
                'bos_okunabilir' => BackupService::humanSize($bos),
                'uyari' => $bos < 2 * (1024 ** 3)
                    ? 'Boş disk 2 GB\'ın altında — yedekleme ve görsel yükleme başarısız olabilir.'
                    : null,
            ],

            'eposta' => [
                // YALNIZ "yapılandırılmış mı" — host/kullanıcı/parola DEĞERLERİ
                // hiçbir koşulda taşınmaz. Aynı tabloda SMTP parolası duruyor.
                'yapilandirildi' => $this->epostaHazirMi(),
                'surucu' => (string) config('mail.default'),
            ],

            'guvenlik' => [
                'hata_ayiklama_acik' => $hataAyiklama,
                'uyari' => $uretim && $hataAyiklama
                    ? 'ÜRETİMDE APP_DEBUG AÇIK. Hata sayfaları yığın izini, dosya yollarını ve '.
                      'ortam değişkenlerini ziyaretçiye gösterir. Derhal kapatılmalı.'
                    : null,
            ],

            'kahya_raporu' => [
                'son_kosu_yasi_saat' => KahyaCalismasi::sonKosuYasiSaat(),
                'kayitli_kosu_sayisi' => KahyaCalismasi::query()->gunlukRapor()->count(),
            ],
        ];
    }

    private function gecikme(): float
    {
        $basla = microtime(true);

        try {
            DB::select('select 1');
        } catch (Throwable) {
            return -1.0;
        }

        return round((microtime(true) - $basla) * 1000, 2);
    }

    private function bekleyenIs(): ?int
    {
        try {
            return match (config('queue.default')) {
                'database' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
                // llen bir OKUMA komutudur; Redis'e hiçbir şey yazmaz.
                'redis' => (int) Redis::llen('queues:default'),
                default => 0,
            };
        } catch (Throwable) {
            return null;
        }
    }

    private function basarisizIs(): ?int
    {
        try {
            return Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string, mixed> */
    private function yedekDurumu(): array
    {
        try {
            $istatistik = $this->yedek->stats();
        } catch (Throwable) {
            return ['okunamadi' => true];
        }

        $son = $istatistik['latest'] ?? null;
        $yasGun = $son?->diffInDays(now());

        return [
            'adet' => $istatistik['count'] ?? 0,
            'son_yedek' => $son?->toAtomString(),
            'yas_gun' => $yasGun !== null ? (int) $yasGun : null,
            'toplam_boyut' => BackupService::humanSize((float) ($istatistik['total_size'] ?? 0)),
            'uyari' => match (true) {
                $son === null => 'HİÇ YEDEK YOK. Yedekleme 04:00\'te zamanlanmış bir komuttur '.
                    've zamanlanmış komutlar sessizce ölür.',
                $yasGun !== null && $yasGun > 7 => 'Son yedek bir haftadan eski — yedekleme durmuş olabilir.',
                default => null,
            },
        ];
    }

    private function epostaHazirMi(): bool
    {
        $surucu = config('mail.default');
        $host = Settings::get('mail.host') ?: config('mail.mailers.smtp.host');

        return ! in_array($surucu, ['log', 'array'], true)
            && ! empty($host)
            && $host !== '127.0.0.1';
    }
}
