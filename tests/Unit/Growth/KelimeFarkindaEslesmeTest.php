<?php

namespace Tests\Unit\Growth;

use App\Contracts\AiProvider;
use App\Services\Growth\BusinessSignal;
use App\Services\Growth\DetectionResult;
use App\Services\Growth\TurkishBusinessDetector;
use App\Support\Growth\TurkishLexicon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Kültürel terim eşleşmesi kelime farkında mı? (2026-08-07)
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Tespit motoru kültürel terimleri `str_contains` ile arıyordu — alt-dize.
 * Sözlükteki kısa terimler İngilizce kelimelerin içinde geçiyor ve her biri
 * TEK BAŞINA 0.6 puan getiriyordu; eşik de tam 0.6. Yani şu adlar insan
 * onayına bile uğramadan "kesin Türk" havuzuna giriyordu:
 *
 *   Ro·manti·c  ·  Lon·doner  ·  Ra·pide  ·  Meze·h
 *
 * Sonuncusu uydurma değil: 2026-08-07'de Clifton (NJ) taramasında gelen
 * gerçek bir aday ("Mezeh Mediterranean Grill" — Akdeniz zinciri, Türk değil).
 *
 * ---------------------------------------------------------------------------
 * NE MÜHÜRLENİYOR
 *
 * Düzeltme "tam kelime ara" DEĞİL — öyle olsaydı en büyük pazardaki bitişik
 * adlar ("Dönerhaus") ve Türkçe ekli adlar ("Pideci") kaybolurdu. Üç kademe
 * var ve testlerin yarısı KAYBETMEDİĞİMİZİ mühürlüyor.
 */
class KelimeFarkindaEslesmeTest extends TestCase
{
    private function detector(): TurkishBusinessDetector
    {
        return new TurkishBusinessDetector(new class implements AiProvider
        {
            public function isConfigured(): bool
            {
                return false;
            }

            public function name(): string
            {
                return 'Sahte';
            }

            public function lastError(): ?string
            {
                return null;
            }

            public function analyzeImage(string $base64Image, string $mediaType, string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
            {
                return null;
            }

            public function analyzeText(string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
            {
                return null;
            }
        });
    }

    // === Kademe kuralı, doğrudan ===================================

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function eslesmeler(): array
    {
        return [
            // Tam kelime → en güçlü kanıt
            'tam kelime' => ['istanbul kebab house', 'kebab', TurkishLexicon::ESLESME_TAM],
            // Bilinen bileşik: Almanca ve Türkçe ekler
            'almanca bileşik' => ['donerhaus berlin', 'doner', TurkishLexicon::ESLESME_TAM],
            'türkçe ek' => ['pideci mustafa', 'pide', TurkishLexicon::ESLESME_TAM],
            // Kelime başı ama tanınmayan devam → zayıf, elenmez
            'tanınmayan devam' => ['pasadena grill', 'pasa', TurkishLexicon::ESLESME_ZAYIF],
            'tanınmayan devam 2' => ['mezeh mediterranean', 'meze', TurkishLexicon::ESLESME_ZAYIF],
            // Kelime sonu: bileşiğin ikinci parçası olabilir → zayıf
            'kelime sonu' => ['stadtdoner', 'doner', TurkishLexicon::ESLESME_ZAYIF],
            // Kelime ORTASI → kanıt değil, tesadüf
            'kelime ortası' => ['romantic restaurant', 'manti', TurkishLexicon::ESLESME_YOK],
            'hiç yok' => ['sunrise bakery', 'kebab', TurkishLexicon::ESLESME_YOK],
        ];
    }

    #[DataProvider('eslesmeler')]
    public function test_terim_eslesmesi_kademeleri(string $metin, string $terim, int $beklenen): void
    {
        $this->assertSame($beklenen, TurkishLexicon::terimEslesmesi($metin, $terim));
    }

    public function test_ilk_eslesme_tam_olani_zayifa_tercih_eder(): void
    {
        // "pasa" zayıf (Pasadena), "istanbul" tam — sıralamadan bağımsız
        // olarak TAM olan kazanmalı, yoksa güçlü kanıt kaybolur.
        [$terim, $kademe] = TurkishLexicon::ilkEslesme(
            'pasadena istanbul cafe',
            ['pasa', 'istanbul'],
        );

        $this->assertSame('istanbul', $terim);
        $this->assertSame(TurkishLexicon::ESLESME_TAM, $kademe);
    }

    // === Yanlış pozitifler artık "kesin Türk" değil ================

    public function test_kelime_ortasindaki_terim_hic_isaret_saymaz(): void
    {
        // ·manti· ⊂ Romantic. Eskiden 0.6 → KESİN TÜRK.
        $r = $this->detector()->screen(new BusinessSignal('Romantic Restaurant', 'Restaurant', country: 'US'));

        $this->assertSame(DetectionResult::BAND_NOT, $r->band);
        $this->assertFalse($r->isTurkish);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function belirsizBilesikler(): array
    {
        return [
            // Hepsi eskiden tek alt-dizeyle 0.6 alıp KESİN TÜRK oluyordu.
            'Londoner (·doner·)' => ['The Londoner', 'Pub'],
            'Rapide (·pide·)' => ['Pizza Rapide', 'Pizza'],
            // Clifton (NJ) taramasında gelen GERÇEK aday — Türk değil.
            'Mezeh (·meze·)' => ['Mezeh Mediterranean Grill', 'Restaurant'],
            // PR #118 denetiminde kaydedilen en öğretici örnek: İtalyan
            // soyadının içinde "ottoman" geçiyor.
            'Ottomanelli (·ottoman·)' => ['F. Ottomanelli Burgers', 'Burgers'],
        ];
    }

    /**
     * Bunlar ELENMİYOR, "belirsiz"e düşüyor — yani insan onayına gidiyor.
     *
     * Bilinçli: "Stadtdöner" gibi gerçek bileşikler de aynı kalıba düşer ve
     * arzın darboğaz olduğu bir üründe adayı sessizce atmak, kirli listeden
     * daha pahalı. Kural: emin değilsen atma, sor.
     */
    #[DataProvider('belirsizBilesikler')]
    public function test_belirsiz_bilesik_kesin_turk_sayilmaz(string $ad, string $kategori): void
    {
        $r = $this->detector()->screen(new BusinessSignal($ad, $kategori, country: 'US'));

        $this->assertNotSame(DetectionResult::BAND_TURKISH, $r->band, "{$ad} kesin Türk sayılmamalı");
        $this->assertSame(DetectionResult::BAND_AMBIGUOUS, $r->band);
        $this->assertTrue($r->needsHumanReview());
    }

    // === Kaybetmediklerimiz ========================================

    public function test_almanca_bitisik_ad_hala_kesin_turk(): void
    {
        // Almanya en büyük hedef pazar ve adlar orada bitişik yazılıyor.
        // Düz "tam kelime" kuralı bunu kaybederdi.
        $r = $this->detector()->screen(new BusinessSignal('Dönerhaus Berlin', 'Imbiss', country: 'DE'));

        $this->assertSame(DetectionResult::BAND_TURKISH, $r->band);
    }

    public function test_turkce_ekli_ad_hala_kesin_turk(): void
    {
        $r = $this->detector()->screen(new BusinessSignal('Pideci Mustafa', 'Restaurant', country: 'DE'));

        $this->assertSame(DetectionResult::BAND_TURKISH, $r->band);
    }

    /**
     * KAPSAM SINIRI — bu düzeltmenin çözmediği şey.
     *
     * "Afghan Kebab House"da `kebab` TAM bir kelime; dize eşleşmesi doğru,
     * yanlış olan ÇIKARIM. Bu ayrı bir sorun sınıfı (anlamsal yanlış pozitif)
     * ve çözümü ayrı katmanda: LLM doğrulaması + ülke/mutfak bağlamı. Burada
     * mühürlenmesinin sebebi, ileride birinin "kelime eşleşmesi düzeltildi,
     * demek ki bunlar da düzeldi" diye varsaymaması.
     */
    public function test_anlamsal_yanlis_pozitifler_bu_duzeltmenin_kapsaminda_degil(): void
    {
        $r = $this->detector()->screen(new BusinessSignal('Afghan Kebab House', 'Restaurant', country: 'US'));

        $this->assertSame(DetectionResult::BAND_TURKISH, $r->band);
    }

    public function test_diakritikli_bitisik_ad_hala_kesin_turk(): void
    {
        // fold() öncesi "Kebapçım" → "kebapcim": terim "kebap" + ek "cim".
        $r = $this->detector()->screen(new BusinessSignal('Kebapçım', 'Restaurant', country: 'DE'));

        $this->assertSame(DetectionResult::BAND_TURKISH, $r->band);
    }
}
