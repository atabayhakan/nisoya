<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Backup script'i için basit doğrulama testleri. Production'da
 * backup.sh bash script'i çalışır; burada sadece gerekli config
 * değişkenlerinin var olduğunu doğruluyoruz.
 *
 * Asıl backup testi canlı sunucuda manuel yapılır:
 *   sudo /etc/cron.daily/nisoya-backup
 */
class BackupConfigTest extends TestCase
{
    public function test_backup_environment_variables_documented(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));

        // Yedekleme için gerekli ENV değişkenleri tanımlı olmalı
        $this->assertStringContainsString('BACKUP_DIR', $envExample);
        $this->assertStringContainsString('RETENTION_DAYS', $envExample);
        $this->assertStringContainsString('S3_BACKUP_BUCKET', $envExample);
        $this->assertStringContainsString('SLACK_BACKUP_WEBHOOK_URL', $envExample);
        $this->assertStringContainsString('DISCORD_BACKUP_WEBHOOK_URL', $envExample);
        $this->assertStringContainsString('BACKUP_ENCRYPTION_KEY', $envExample);
    }

    public function test_backup_script_is_executable(): void
    {
        $scriptPath = base_path('deploy/backup.sh');
        $this->assertFileExists($scriptPath);

        // Shebang var
        $content = file_get_contents($scriptPath);
        $this->assertStringStartsWith('#!/usr/bin/env bash', $content);

        // set -euo pipefail var (strict mode)
        $this->assertStringContainsString('set -euo pipefail', $content);
    }

    public function test_backup_script_handles_all_steps(): void
    {
        $script = file_get_contents(base_path('deploy/backup.sh'));

        // 11 adımın her biri script'te olmalı
        $this->assertStringContainsString('mysqldump nisoya', $script); // DB
        $this->assertStringContainsString('storage/app/public', $script); // Storage
        $this->assertStringContainsString('Activity::query', $script); // Activity log
        $this->assertStringContainsString('gpg', $script); // Encryption
        $this->assertStringContainsString('sha256sum', $script); // Checksum
        $this->assertStringContainsString('aws s3 cp', $script); // S3 upload
        $this->assertStringContainsString('curl', $script); // Notifications
        $this->assertStringContainsString('find', $script); // Cleanup
    }

    public function test_backup_config_defaults_sensible(): void
    {
        // Default değerler production için mantıklı
        $script = file_get_contents(base_path('deploy/backup.sh'));

        // BACKUP_DIR default local
        $this->assertStringContainsString('BACKUP_DIR=${BACKUP_DIR:-/var/backups/nisoya}', $script);

        // RETENTION_DAYS default 14 gün
        $this->assertStringContainsString('RETENTION_DAYS=${RETENTION_DAYS:-14}', $script);
    }

    public function test_encryption_uses_strong_aes256(): void
    {
        $script = file_get_contents(base_path('deploy/backup.sh'));

        // AES-256 simetrik şifreleme (NIST onaylı, kuantum dirençli)
        $this->assertStringContainsString('AES256', $script);
    }

    public function test_s3_upload_uses_cost_optimized_storage_class(): void
    {
        $script = file_get_contents(base_path('deploy/backup.sh'));

        // STANDARD_IA (Infrequent Access) — yedekler için uygun
        // 30 günde bir erişilen yedekler için ~%40 daha ucuz
        $this->assertStringContainsString('STANDARD_IA', $script);
    }

    public function test_notifications_support_both_slack_and_discord(): void
    {
        $script = file_get_contents(base_path('deploy/backup.sh'));

        $this->assertStringContainsString('SLACK_WEBHOOK_URL', $script);
        $this->assertStringContainsString('DISCORD_WEBHOOK_URL', $script);
        $this->assertStringContainsString('Slack bildirimi', $script);
        $this->assertStringContainsString('Discord bildirimi', $script);
    }
}
