<?php

namespace App\Console\Commands;

use App\Models\DiasporaAccount;
use App\Services\Diaspora\DiasporaRankingEngine;
use App\Services\Diaspora\DiasporaSyncEngine;
use Illuminate\Console\Command;

class SyncDiasporaReels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diaspora:sync {--seed-accounts : 5 resmi diaspora topluluk hesabını otomatik kaydeder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takip edilen diaspora Instagram hesaplarını tarar ve yeni Reels videolarını vitrine aktarır.';

    /**
     * Execute the console command.
     */
    public function handle(DiasporaSyncEngine $syncEngine, DiasporaRankingEngine $rankingEngine): int
    {
        $this->info('🚀 Diaspora Reels Senkronizasyonu Başlatılıyor...');

        if ($this->option('seed-accounts') || DiasporaAccount::count() === 0) {
            $this->seedOfficialAccounts();
        }

        $res = $syncEngine->syncAll();

        $this->info("✅ {$res['accounts_count']} diaspora hesabı tarandı.");
        $this->info("📥 {$res['total_created']} yeni içerik aktarıldı ({$res['autopilot_published']} adedi otopilot ile doğrudan yayına alındı).");
        if ($res['total_skipped'] > 0) {
            $this->comment("⏭️ {$res['total_skipped']} içerik daha önceden eklendiği için atlandı.");
        }

        // Akıllı Sıralama & Terfi Motorunu Çalıştır
        $rankRes = $rankingEngine->recalculateAndRank();
        $this->info("⭐ Etkileşim sıralaması güncellendi. ({$rankRes['promoted_featured']} içerik öne çıkan geniş bento karta terfi etti).");

        return self::SUCCESS;
    }

    protected function seedOfficialAccounts(): void
    {
        $accounts = [
            [
                'username' => '@amerikaliturkler',
                'title' => 'Amerikalı Türkler (TRUS)',
                'description' => 'Amerika’daki en büyük Türk topluluk ve haber ağı.',
                'country_code' => 'US',
                'city' => 'New York',
                'is_verified' => true,
                'autopilot' => true,
            ],
            [
                'username' => '@almanyaturkagi',
                'title' => 'Almanya Türk Ağı (ATA)',
                'description' => 'Deutsch-Türkisches Netzwerk für Bildung, Kultur und Gemeinschaft.',
                'country_code' => 'DE',
                'city' => 'Berlin',
                'is_verified' => true,
                'autopilot' => true,
            ],
            [
                'username' => '@turkishcommunityinqatar',
                'title' => 'Turkish Community in Qatar',
                'description' => 'Katar’da yaşayan Türklerin resmi topluluk ve buluşma sayfası.',
                'country_code' => 'QA',
                'city' => 'Doha',
                'is_verified' => true,
                'autopilot' => true,
            ],
            [
                'username' => '@turkishcommunitycentre',
                'title' => 'Turkish Community Centre of Canada',
                'description' => 'Kanada Türk Toplum Mirası Merkezi (TCHCC).',
                'country_code' => 'CA',
                'city' => 'Toronto',
                'is_verified' => true,
                'autopilot' => true,
            ],
            [
                'username' => '@turkishbusinesscouncildubai',
                'title' => 'Turkish Business Council Dubai',
                'description' => 'Körfez ve Dubai Türk iş dünyası ve ticaret konseyi.',
                'country_code' => 'AE',
                'city' => 'Dubai',
                'is_verified' => true,
                'autopilot' => true,
            ],
        ];

        $count = 0;
        foreach ($accounts as $acc) {
            DiasporaAccount::updateOrCreate(
                ['username' => $acc['username']],
                $acc
            );
            $count++;
        }

        $this->info("✨ {$count} adet doğrulanmış diaspora topluluk hesabı kaydedildi.");
    }
}
