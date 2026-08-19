<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Models\Page;
use Database\Seeders\PilotYasamRehberiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pilot — "pratik yaşam" rehberi (docs/03-buyume-fikirleri.md, 2026-08-19,
 * öneri 2). TASLAK doğar, sahip onaylamadan yayına çıkmaz — bu testin asıl
 * görevi tam da bunu kilitlemek.
 */
class PilotYasamRehberiSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_sayfa_taslak_olarak_olusur_ve_yayinda_degildir(): void
    {
        $this->seed(PilotYasamRehberiSeeder::class);

        $sayfa = Page::query()->where('slug', 'abd-ssn-siz-banka-hesabi-acma')->firstOrFail();

        $this->assertSame(PageStatus::Taslak, $sayfa->status);
        $this->assertFalse($sayfa->show_in_footer);

        // K7'nin aynısı: taslak içerik ziyaretçiye 404 verir, "yakında" bile göstermez.
        $this->get('/abd-ssn-siz-banka-hesabi-acma')->assertNotFound();
    }

    public function test_icerik_gercek_kaynaklara_baglanir(): void
    {
        $this->seed(PilotYasamRehberiSeeder::class);

        $blocks = Page::query()->where('slug', 'abd-ssn-siz-banka-hesabi-acma')->firstOrFail()->blocks;
        $html = $blocks[0]['data']['content'];

        $this->assertStringContainsString('irs.gov', $html);
        $this->assertStringContainsString('consumerfinance.gov', $html);
        $this->assertStringContainsString('wellsfargo.com', $html);
    }

    public function test_tekrar_kosu_ikinci_kayit_olusturmaz(): void
    {
        $this->seed(PilotYasamRehberiSeeder::class);
        $this->seed(PilotYasamRehberiSeeder::class);

        $this->assertSame(1, Page::query()->where('slug', 'abd-ssn-siz-banka-hesabi-acma')->count());
    }

    public function test_slug_rezerve_listesiyle_cakismiyor(): void
    {
        $this->assertNotContains('abd-ssn-siz-banka-hesabi-acma', Page::RESERVED_SLUGS);
    }
}
