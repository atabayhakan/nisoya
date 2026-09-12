<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\AiProvider;
use App\Services\Ai\SystemToolsAiAssistant;
use Mockery;
use Tests\TestCase;

class SystemToolsAiAssistantTest extends TestCase
{
    private function assistantOlustur(?AiProvider $mockAi = null): SystemToolsAiAssistant
    {
        if (! $mockAi) {
            $mockAi = Mockery::mock(AiProvider::class);
            $mockAi->shouldReceive('isConfigured')->andReturn(false);
        }

        return new SystemToolsAiAssistant($mockAi);
    }

    public function test_optimize_email_template_preserves_placeholders(): void
    {
        $assistant = $this->assistantOlustur();
        $sonuc = $assistant->optimizeEmailTemplate(
            'yeni_mesaj',
            'greeting',
            'Selam {ad},'
        );

        $this->assertArrayHasKey('optimized_text', $sonuc);
        $this->assertArrayHasKey('explanation', $sonuc);
        $this->assertArrayHasKey('placeholders_preserved', $sonuc);
        $this->assertTrue($sonuc['placeholders_preserved']);
        $this->assertStringContainsString('{ad}', $sonuc['optimized_text']);
    }

    public function test_diagnose_system_error_categorizes_database_and_smtp_errors(): void
    {
        $assistant = $this->assistantOlustur();

        // 1. Veritabanı hatası
        $dbHata = $assistant->diagnoseSystemError(
            'SQLSTATE[HY000] [2002] Connection refused',
            'Illuminate\Database\QueryException',
            'app/Models/User.php:45'
        );
        $this->assertEquals('kritik', $dbHata['severity']);
        $this->assertStringContainsString('Veritabanı', $dbHata['root_cause']);
        $this->assertNotEmpty($dbHata['solution_steps']);

        // 2. SMTP hatası
        $smtpHata = $assistant->diagnoseSystemError(
            'Connection could not be established with host smtp.hostinger.com:465',
            'Symfony\Component\Mailer\Exception\TransportException',
            'app/Services/MailService.php:80'
        );
        $this->assertEquals('kritik', $smtpHata['severity']);
        $this->assertStringContainsString('E-posta', $smtpHata['root_cause']);
    }

    public function test_generate_recovery_audit_evaluates_risks_accurately(): void
    {
        $assistant = $this->assistantOlustur();

        // Tek admin, 2FA yok, kurtarma kodu yok, smtp yok -> Kritik risk
        $kritikAudit = $assistant->generateRecoveryAudit(1, 0, 0, false);
        $this->assertEquals('kritik', $kritikAudit['level']);
        $this->assertLessThan(50, $kritikAudit['score']);
        $this->assertNotEmpty($kritikAudit['risks']);
        $this->assertNotEmpty($kritikAudit['action_plan']);

        // 2 admin, 2FA tam, 8 kurtarma kodu, SMTP aktif -> Güvenli
        $guvenliAudit = $assistant->generateRecoveryAudit(2, 2, 8, true);
        $this->assertEquals('guvenli', $guvenliAudit['level']);
        $this->assertEquals(100, $guvenliAudit['score']);
        $this->assertEmpty($guvenliAudit['risks']);
    }

    public function test_suggest_country_details_returns_standard_iso_and_currency(): void
    {
        $assistant = $this->assistantOlustur();

        $almanya = $assistant->suggestCountryDetails('Almanya');
        $this->assertEquals('DE', $almanya['code']);
        $this->assertEquals('EUR', $almanya['default_currency']);
        $this->assertEquals('🇩🇪', $almanya['emoji']);

        $isvicre = $assistant->suggestCountryDetails('CH');
        $this->assertEquals('CH', $isvicre['code']);
        $this->assertEquals('CHF', $isvicre['default_currency']);
        $this->assertEquals('🇨🇭', $isvicre['emoji']);
    }
}
