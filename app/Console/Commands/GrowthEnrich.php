<?php

namespace App\Console\Commands;

use App\Models\OutreachTarget;
use App\Services\Growth\EnrichmentRunner;
use Illuminate\Console\Command;

/**
 * Keşfedilen adayların web sitelerinden iletişim e-postası çıkarır. Yalnızca
 * gönderim-izinli bölgeler zenginleştirilir (AB/TR/RU atlanır — GDPR).
 *
 *   php artisan growth:enrich
 *   php artisan growth:enrich US --limit=100
 */
class GrowthEnrich extends Command
{
    protected $signature = 'growth:enrich {country? : Ülke kodu (boşsa tümü)} {--limit=50}';

    protected $description = 'Adayların sitelerinden iletişim e-postası çıkarır (yalnız gönderim-izinli bölgeler).';

    public function handle(EnrichmentRunner $runner): int
    {
        $country = $this->argument('country');
        $this->components->info('Zenginleştirme başlıyor'.($country ? ": {$country}" : ' (tüm ülkeler)'));

        $stats = $runner->run($country ? strtoupper($country) : null, (int) $this->option('limit'));

        $this->newLine();
        $this->components->twoColumnDetail('Aday (site var, e-posta yok)', (string) $stats['candidates']);
        $this->components->twoColumnDetail('<fg=green>E-posta bulundu</>', (string) $stats['enriched']);
        $this->components->twoColumnDetail('E-posta bulunamadı', (string) $stats['no_email']);
        $this->components->twoColumnDetail('<fg=red>Atlandı (AB/TR/RU — GDPR)</>', (string) $stats['skipped_blocked']);

        $enriched = OutreachTarget::query()
            ->whereNotNull('contact_email')
            ->when($country, fn ($q) => $q->where('country', strtoupper($country)))
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get(['name', 'country', 'contact_email']);

        if ($enriched->isNotEmpty()) {
            $this->newLine();
            $this->components->info('İletişimi olan adaylar:');
            $this->table(
                ['İşletme', 'Ülke', 'E-posta'],
                $enriched->map(fn (OutreachTarget $t): array => [$t->name, $t->country, $t->contact_email])->all(),
            );
        }

        return self::SUCCESS;
    }
}
