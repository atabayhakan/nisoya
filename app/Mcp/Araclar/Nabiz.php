<?php

namespace App\Mcp\Araclar;

use App\Models\KahyaCalismasi;
use App\Services\Kahya\BekleyenIsler;
use App\Services\Kahya\KahyaTeshisi;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * İLK ÇAĞRILACAK ARAÇ. Ucuz (~12 COUNT sorgusu), dosya sistemine hiç
 * dokunmaz, log okumaz.
 *
 * `tam-teshis` bunun pahalı akrabası: o ~26 sorgu + 2000'e kadar dosya
 * kontrolü + storage/logs'un tamamının okunması demektir ve günde BİR kez
 * çalışmak üzere tasarlandı. "Sitede ne durumda?" sorusunun cevabı çoğu zaman
 * burada.
 */
#[Name('kahya-nabiz')]
#[Title('Nabız — sitenin şu anki durumu')]
#[Description(
    'Nisoya\'nın anlık durumu: gerçek envanter (aktif ilan VE benzersiz satıcı sayısı), '.
    'son 24 saatte kaç yeni üye/ilan geldiği, sahibi bekleyen moderasyon işleri ve '.
    'Kâhya\'nın günlük raporunun en son ne zaman koştuğu. UCUZ — serbestçe çağrılabilir. '.
    'Buradan başla; ayrıntı gerekirse kahya-tam-teshis, kahya-hata-kayitlari, '.
    'kahya-medya-dogrula veya kahya-eksik-alanlar araçlarına geç.'
)]
#[IsReadOnly]
class Nabiz extends KahyaAraci
{
    public function __construct(
        private readonly KahyaTeshisi $teshis,
        private readonly BekleyenIsler $bekleyen,
    ) {}

    /** @return array<string, mixed> */
    protected function topla(Request $request): array
    {
        $kuyruklar = $this->bekleyen->topla();
        $yas = KahyaCalismasi::sonKosuYasiSaat();

        return [
            'olcum_ani' => now()->toAtomString(),
            'ortam' => (string) app()->environment(),

            'envanter' => $this->teshis->gercekEnvanter(),

            // DİKKAT: 'yeni_ilan' durum filtresi UYGULAMAZ — taslak ve
            // moderasyondaki ilanlar da sayılır. 'envanter.ilan' ise yalnız
            // aktif olanları sayar. İki sayı aynı şeyi ölçmez.
            'son_24_saat' => $this->teshis->sonYirmiDortSaat(),

            'bekleyen_isler' => [
                // Adedi sıfır olan kuyruk hiç dönmez: bu bir yapılacak-iş
                // listesidir, boş satır gürültüdür.
                'kuyruklar' => $kuyruklar,
                'kuyruk_sayisi' => count($kuyruklar),
                'toplam_adet' => array_sum(array_column($kuyruklar, 'adet')),
            ],

            'kahya_raporu' => [
                'son_kosu_yasi_saat' => $yas,
                'durum' => match (true) {
                    $yas === null => 'HİÇ ÇALIŞMADI — zamanlayıcı (cron) çalışmıyor olabilir',
                    $yas > 36 => 'ESKİ — günlük olması gerekiyor, zamanlayıcı ya da kuyruk durmuş olabilir',
                    default => 'düzenli',
                },
            ],
        ];
    }
}
