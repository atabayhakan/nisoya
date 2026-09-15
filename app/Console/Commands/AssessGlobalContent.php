<?php

namespace App\Console\Commands;

use App\Support\GlobalCommand\ContentQuality;
use App\Support\GlobalCommand\QualityScanner;
use Illuminate\Console\Command;

class AssessGlobalContent extends Command
{
    protected $signature = 'global-command:assess-content {--limit=100}';

    protected $description = 'Kalıcı ilerleme kaydıyla içerik kataloğunun kalite denetimini sürdürür.';

    public function handle(ContentQuality $quality, QualityScanner $scanner): int
    {
        if (! config('global-command.enabled')) {
            return self::SUCCESS;
        }
        $limit = max(1, min(500, (int) $this->option('limit')));
        $count = $scanner->scan($quality, $limit);
        $this->info($count.' içerik kontrol edildi; yeni sürümler inceleme kuyruğuna alındı.');

        return self::SUCCESS;
    }
}
