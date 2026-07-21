<?php

namespace Tests\Feature;

use App\Services\ProfanityFilterService;
use Tests\TestCase;

class ProfanityFilterTest extends TestCase
{
    private ProfanityFilterService $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new ProfanityFilterService;
    }

    public function test_detects_clean_text(): void
    {
        $this->assertFalse($this->filter->containsProfanity('Harika bir ürün, çok teşekkürler!'));
        $this->assertNull($this->filter->validateText('Berlin içi nakliye hizmeti vermekteyiz.'));
    }

    public function test_detects_explicit_profanity(): void
    {
        $this->assertTrue($this->filter->containsProfanity('Bu ne biçim siktir git iş!'));
        $this->assertNotNull($this->filter->validateText('amk böyle işin'));
    }

    public function test_detects_leetspeak_and_spaced_manipulations(): void
    {
        $this->assertTrue($this->filter->containsProfanity('s!kt!r g!t burdan'));
        $this->assertTrue($this->filter->containsProfanity('@mk sen kimsin'));
        $this->assertTrue($this->filter->containsProfanity('o.ç adam ol'));
    }

    public function test_censors_bad_words(): void
    {
        $censored = $this->filter->censor('Bu tamamen siktir bir durum amk');
        $this->assertStringContainsString('***', $censored);
        $this->assertStringNotContainsString('siktir', $censored);
    }

    public function test_does_not_flag_legitimate_words(): void
    {
        // 'kalpak' (kürk şapka) meşru bir üründür; substring eşleşmesiyle
        // yanlışlıkla bloklanıyordu (denetim #9).
        $this->assertFalse($this->filter->containsProfanity('Satılık kalpak, geleneksel kürk şapka.'));

        // Bilinçli karar: 'ı' harfi 'i'ye KATLANMAZ — aksi hâlde "sıkı",
        // "sıkışık", "sıkıntı" gibi çok yaygın meşru kelimeler vulgar
        // 'siki'/'sikis' ile karışırdı. Nadir "sıkeyim" atlatmasından çok daha
        // fazla yanlış-pozitif üretirdi.
        $this->assertFalse($this->filter->containsProfanity('Sıkı bir dostluk ve sıkışık trafik.'));
    }
}
