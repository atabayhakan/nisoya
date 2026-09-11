<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Models\KahyaHarcamasi;
use App\Models\TelegramSohbeti;
use App\Services\Kahya\Dis\TelegramDinleyici;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Kâhya'nın Telegram grup dinleyicisi.
 *
 * ---------------------------------------------------------------------------
 * NE KORUYOR
 *
 * Tasarım kararı (2026-09-11): "üçüncü taraf gruplara girmek yerine kendi
 * grubumuzu kur, Kâhya normal bir üye gibi katılsın — reklam gibi görünmesin,
 * hacim/bütçe riskini kontrolsüz büyütmesin." Bu üç şeyden ödün veremez:
 *
 *   1. YALNIZ İZİNLİ GRUPTA konuşur — bota rastgele eklenen bir grupta değil.
 *   2. HER MESAJA cevap vermez — yalnız soru gibi görünenlere (bütçe + "her
 *      yerde konuşan bot" izlenimi).
 *   3. AYLIK LİMİT dolunca susar — canlı bir grup tek kullanıcılı admin
 *      sohbetinden çok farklı bir hacim riski taşır.
 */
class TelegramDinleyiciTest extends TestCase
{
    use RefreshDatabase;

    private function sahteAi(?array $donen): void
    {
        $sahte = new class($donen) implements AiProvider
        {
            public function __construct(private ?array $donen) {}

            public function isConfigured(): bool
            {
                return true;
            }

            public function name(): string
            {
                return 'sahte';
            }

            public function lastError(): ?string
            {
                return null;
            }

            public function analyzeImage(string $b, string $m, string $pr, ?array $s = null, ?int $t = null): ?array
            {
                return null;
            }

            public function analyzeText(string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
            {
                return $this->donen;
            }
        };

        $this->app->instance(AiProvider::class, $sahte);
    }

    private function ayarla(array $ustuneYaz = []): void
    {
        Settings::setMany(array_merge([
            'kahya.telegram.bot_token' => 'sahte-token',
            'kahya.telegram.izinli_grup_id' => '-100',
            'kahya.telegram.aylik_mesaj_limiti' => '600',
        ], $ustuneYaz));
    }

    /** @return array<string, mixed> */
    private function grupMesaji(string $metin, string $chatId = '-100'): array
    {
        return [
            'message' => [
                'text' => $metin,
                'chat' => ['id' => $chatId, 'type' => 'group'],
                'from' => ['id' => '555', 'username' => 'test_kullanici'],
            ],
        ];
    }

    public function test_izinli_olmayan_gruptan_gelen_mesaj_yok_sayilir(): void
    {
        $this->ayarla();
        $this->sahteAi(['cevap' => 'Olmamalı', 'ihtiyac' => null]);
        Http::fake();

        app(TelegramDinleyici::class)->gelenGuncelleme($this->grupMesaji('Vize süresi ne kadar?', chatId: '-999'));

        Http::assertNothingSent();
        $this->assertSame(0, TelegramSohbeti::count());
    }

    public function test_soru_gibi_olmayan_mesaj_cevaplanmiyor(): void
    {
        $this->ayarla();
        $this->sahteAi(['cevap' => 'Olmamalı', 'ihtiyac' => null]);
        Http::fake();

        app(TelegramDinleyici::class)->gelenGuncelleme($this->grupMesaji('herkese günaydın'));

        Http::assertNothingSent();
        $this->assertSame(0, TelegramSohbeti::count());
    }

    public function test_gercek_soru_cevaplaniyor_ve_kaydediliyor(): void
    {
        $this->ayarla();
        // Bilerek "vize/hukuk/para" bekçi kelimelerinden ARINDIRILMIŞ bir soru —
        // bu test normal (bekçisiz) yolu kontrol ediyor, bekçi ayrı test'te.
        $this->sahteAi(['cevap' => 'Market genelde akşam 21:00\'de kapanır.', 'ihtiyac' => null]);
        Http::fake();

        app(TelegramDinleyici::class)->gelenGuncelleme($this->grupMesaji('Marketler saat kaçta kapanıyor?'));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === '-100');

        $sohbet = TelegramSohbeti::sole();
        $this->assertSame('-100', $sohbet->telegram_chat_id);
        $this->assertFalse($sohbet->needs_review);
        $this->assertSame(1, KahyaHarcamasi::where('kaynak', TelegramDinleyici::KAYNAK)->count());
    }

    public function test_hassas_konu_isaretleniyor_ve_uyari_ekleniyor(): void
    {
        $this->ayarla();
        $this->sahteAi(['cevap' => 'Durumunu şöyle çözebilirsin...', 'ihtiyac' => null]);
        Http::fake();

        app(TelegramDinleyici::class)->gelenGuncelleme($this->grupMesaji('Vizem bitti deport oldum ne yapmalıyım?'));

        $sohbet = TelegramSohbeti::sole();
        $this->assertTrue($sohbet->needs_review);
        $this->assertSame('vize_hukuk', $sohbet->kategori);
        $this->assertStringContainsString('avukat', $sohbet->cevap_metni);
    }

    public function test_ihtiyac_belirtilince_grupta_dm_daveti_ekleniyor(): void
    {
        $this->ayarla(['kahya.telegram.bot_kullanici_adi' => 'NisoyaYardimBot']);
        $this->sahteAi(['cevap' => 'Anladım, sana yardımcı olabilecek biri olabilir.', 'ihtiyac' => 'ev arıyor']);
        Http::fake();

        app(TelegramDinleyici::class)->gelenGuncelleme($this->grupMesaji('Moskova\'da ev arıyorum, bilen var mı?'));

        $this->assertStringContainsString('t.me/NisoyaYardimBot', TelegramSohbeti::sole()->cevap_metni);
    }

    public function test_aylik_limit_dolunca_cevap_verilmiyor(): void
    {
        $this->ayarla(['kahya.telegram.aylik_mesaj_limiti' => '1']);
        $this->sahteAi(['cevap' => 'Olmamalı', 'ihtiyac' => null]);
        Http::fake();

        KahyaHarcamasi::create(['kaynak' => TelegramDinleyici::KAYNAK, 'saglayici' => 'sahte', 'model' => '']);

        app(TelegramDinleyici::class)->gelenGuncelleme($this->grupMesaji('Vize süresi ne kadar?'));

        Http::assertNothingSent();
        $this->assertSame(0, TelegramSohbeti::count());
    }

    public function test_kapaliyken_hicbir_seye_cevap_verilmiyor(): void
    {
        $this->ayarla(['kahya.telegram.aktif' => '0']);
        $this->sahteAi(['cevap' => 'Olmamalı', 'ihtiyac' => null]);
        Http::fake();

        app(TelegramDinleyici::class)->gelenGuncelleme($this->grupMesaji('Vize süresi ne kadar?'));

        Http::assertNothingSent();
    }

    public function test_ozel_mesajda_start_komutu_ai_cagirmadan_karsilaniyor(): void
    {
        $this->ayarla();
        $this->sahteAi(null); // AI çağrılırsa test kırılır (schema'ya uymayan null döner, cevap boş kalır).
        Http::fake();

        app(TelegramDinleyici::class)->gelenGuncelleme([
            'message' => [
                'text' => '/start',
                'chat' => ['id' => '777', 'type' => 'private'],
                'from' => ['id' => '555', 'username' => 'test_kullanici'],
            ],
        ]);

        Http::assertSent(fn ($request) => str_contains((string) ($request['text'] ?? ''), 'Merhaba'));
        $this->assertSame(0, TelegramSohbeti::count(), '/start bir günlük satırı açmamalı.');
    }

    public function test_ozel_mesaj_soru_isareti_olmadan_da_cevaplaniyor(): void
    {
        $this->ayarla();
        $this->sahteAi(['cevap' => 'Merhaba, tabii ki yardımcı olabilirim.', 'ihtiyac' => null]);
        Http::fake();

        app(TelegramDinleyici::class)->gelenGuncelleme([
            'message' => [
                'text' => 'merhaba yardım lazımdı',
                'chat' => ['id' => '777', 'type' => 'private'],
                'from' => ['id' => '555', 'username' => 'test_kullanici'],
            ],
        ]);

        $this->assertSame(1, TelegramSohbeti::count(), 'DM zaten kişinin kendi isteğiyle açılmış bir kanal — soru filtresi aranmamalı.');
    }
}
