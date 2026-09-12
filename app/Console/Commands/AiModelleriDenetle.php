<?php

namespace App\Console\Commands;

use App\Services\Ai\AiModelRegistry;
use App\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Günlük çalışan AI model denetim ve senkronizasyon komutu.
 *
 * Her gün sabah 04:30'da (yedekten sonra, günlük rapordan önce) çalışarak:
 * 1. OpenRouter, NVIDIA, Groq vb. sağlayıcıların API'lerinden en güncel modelleri çeker.
 * 2. Aktif sağlayıcı ve seçili varsayılan modelin anlık sağlığını (ping) test eder.
 * 3. Sorun varsa loglar ve sahibin paneline bildirim üretilmesini sağlar.
 */
class AiModelleriDenetle extends Command
{
    protected $signature = 'ai:modelleri-denetle {--force : Önbelleği temizleyip tüm modelleri taze olarak çek}';

    protected $description = 'Yapay zekâ sağlayıcılarının model listelerini günceller ve aktif modelin sağlığını denetler.';

    public function handle(AiModelRegistry $registry): int
    {
        $this->info('AI model kataloğu senkronizasyonu başlatılıyor...');
        $force = (bool) $this->option('force');

        $providers = ['openrouter', 'nvidia', 'groq', 'deepseek', 'mistral', 'openai', 'anthropic', 'gemini'];
        $rows = [];

        foreach ($providers as $p) {
            $models = $registry->getAvailableModels($p, forceRefresh: $force);
            $rows[] = [
                'Sağlayıcı' => $p,
                'Model Sayısı' => count($models),
                'Örnek Model' => array_key_first($models) ?? '-',
            ];
        }

        $this->table(['Sağlayıcı', 'Model Sayısı', 'Örnek Model'], $rows);

        // Aktif sağlayıcı ve modeli denetle
        $activeProvider = trim((string) Settings::get('ai.saglayici')) ?: (string) config('ai.default', 'openrouter');
        $activeModel = trim((string) Settings::get('ai.model')) ?: (string) config("ai.providers.{$activeProvider}.model");

        $this->newLine();
        $this->info("Aktif Sağlayıcı ve Model Denetleniyor: [{$activeProvider}] / [{$activeModel}]");

        $health = $registry->checkHealth($activeProvider, $activeModel);

        if ($health['message'] === 'API anahtarı girilmemiş.') {
            $this->warn("! BİLGİ: [{$activeProvider}] için API anahtarı henüz yapılandırılmamış.");

            return self::SUCCESS;
        }

        if ($health['ok']) {
            $this->info("✓ BAŞARILI: {$health['message']}");
            Log::info("AI Günlük Denetim: [{$activeProvider}] / [{$activeModel}] sağlıklı ({$health['latency_ms']}ms).");

            return self::SUCCESS;
        }

        $this->error("✗ BAŞARISIZ: {$health['message']}");
        Log::warning("AI Günlük Denetim Hatası: [{$activeProvider}] / [{$activeModel}] yanıt vermedi: {$health['message']}");

        return self::FAILURE;
    }
}
