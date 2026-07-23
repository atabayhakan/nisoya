<?php

namespace App\Console\Commands;

use App\Services\Growth\ContactEnricher;
use Illuminate\Console\Command;

/**
 * İletişim çıkarımının canlı demosu — ağ gerektirmez. Gerçekçi HTML örneklerinde
 * e-posta çıkarma mantığını (rol tabanlı tercih + çöp ayıklama) gösterir.
 *
 *   php artisan growth:enrich-demo
 */
class GrowthEnrichDemo extends Command
{
    protected $signature = 'growth:enrich-demo';

    protected $description = 'İletişim e-postası çıkarımını örnek HTML üzerinde gösterir (ağ gerekmez).';

    public function handle(ContactEnricher $enricher): int
    {
        $samples = [
            'Rol tabanlı + kişisel bir arada' => '<footer>Bize ulaşın: <a href="mailto:mehmet@anadolukebap.com">Mehmet</a> veya <a href="mailto:info@anadolukebap.com">info@anadolukebap.com</a></footer>',
            'Sadece düz metin' => '<p>Rezervasyon için iletisim@istanbulcafe.com numaralı adresten yazın.</p>',
            'Çöp (asset/3P) + gerçek' => '<img src="logo@2x.png"><script>sentry@wixpress.com</script><span>satis@bishkekmobilya.kg</span>',
            'Hiç e-posta yok' => '<p>Telefon: +1 555 0100. Adres: 5. Cadde.</p>',
        ];

        $rows = [];
        foreach ($samples as $label => $html) {
            $email = $enricher->extractFromHtml($html);
            $rows[] = [$label, $email ?? '<fg=gray>— bulunamadı</>'];
        }

        $this->components->info('İletişim çıkarımı — örnek HTML üzerinde');
        $this->table(['Senaryo', 'Seçilen e-posta'], $rows);
        $this->line('   → Rol tabanlı (info@/iletisim@/satis@) kişisele tercih edilir; asset/3P adresler ayıklanır.');

        return self::SUCCESS;
    }
}
