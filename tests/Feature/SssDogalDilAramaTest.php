<?php

namespace Tests\Feature;

use App\Models\SssSorusu;
use App\Services\SssDogalDilArama;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nisoya AI arama — SSS tarafı sorgu katmanı (AI çağırmaz, bkz.
 * SssDogalDilArama docblock'u). Rehber/Yaşam'ın aksine ülke/kategori
 * boyutu yok — tek eksen anahtar kelime. docs/plans/2026-08-25-…
 */
class SssDogalDilAramaTest extends TestCase
{
    use RefreshDatabase;

    private function soru(array $overrides = []): SssSorusu
    {
        return SssSorusu::query()->create(array_merge([
            'soru' => 'Nisoya ücretli mi?',
            'cevap' => 'Hayır, kayıt olmak ve ilan vermek tamamen ücretsiz.',
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    public function test_anahtar_kelime_soru_metninde_arar(): void
    {
        $this->soru();

        $sonuc = app(SssDogalDilArama::class)->ara(['ücretli']);

        $this->assertCount(1, $sonuc);
        $this->assertSame('Nisoya ücretli mi?', $sonuc->first()['baslik']);
        $this->assertStringContainsString('/sss#soru-', $sonuc->first()['url']);
    }

    public function test_anahtar_kelime_cevap_metninde_de_arar(): void
    {
        $this->soru();

        $sonuc = app(SssDogalDilArama::class)->ara(['ücretsiz']);

        $this->assertCount(1, $sonuc);
    }

    public function test_anahtar_kelime_yoksa_bos_doner(): void
    {
        $this->soru();

        $sonuc = app(SssDogalDilArama::class)->ara([]);

        $this->assertTrue($sonuc->isEmpty());
    }

    public function test_eslesme_yoksa_bos_doner(): void
    {
        $this->soru();

        $sonuc = app(SssDogalDilArama::class)->ara(['alakasız', 'kelimeler']);

        $this->assertTrue($sonuc->isEmpty());
    }

    public function test_pasif_soru_sonuca_sizmaz(): void
    {
        $this->soru(['is_active' => false]);

        $sonuc = app(SssDogalDilArama::class)->ara(['ücretli']);

        $this->assertTrue($sonuc->isEmpty());
    }

    public function test_url_dogru_soruya_isaret_eder(): void
    {
        $soru = $this->soru();

        $sonuc = app(SssDogalDilArama::class)->ara(['ücretli']);

        $this->assertStringContainsString('#soru-'.$soru->id, $sonuc->first()['url']);
    }
}
