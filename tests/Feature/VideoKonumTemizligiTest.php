<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\User;
use App\Services\VideoKonumTemizleyici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Video konum temizliği (açık işler envanteri: "MP4'lerde GPS temizlenmiyor").
 *
 * İki katman sınanır: (1) temizleyicinin kendisi — sentetik ama yapısal
 * olarak doğru MP4 kutu ağaçlarıyla; (2) HTTP yolu — misafirin yüklediği
 * videonun DİSKTEKİ kopyası koordinat içermemeli. Ayrıca en önemli negatif
 * sözleşme: temizleyici bir dosyayı ASLA bozmaz — konum yoksa ya da dosya
 * MP4 değilse bayt bayt aynı kalır.
 */
class VideoKonumTemizligiTest extends TestCase
{
    use RefreshDatabase;

    private const ISO6709 = '+37.3349-122.0090+000.000/';

    // ------------------------------------------------- Sentetik MP4 üretimi

    private function kutu(string $tip, string $icerik): string
    {
        return pack('N', 8 + strlen($icerik)).$tip.$icerik;
    }

    private function ftyp(): string
    {
        return $this->kutu('ftyp', 'isom'.pack('N', 0x200).'isomiso2avc1mp41');
    }

    private function mdat(): string
    {
        return $this->kutu('mdat', 'VIDEO-VERISI-SABIT-KALMALI');
    }

    /** ©xyz + loci (udta) + Apple meta (keys/ilst) — üç konum taşıyıcısı birden. */
    private function gpsliMp4(): string
    {
        $xyz = $this->kutu("\xA9xyz", pack('n', strlen(self::ISO6709)).pack('n', 0x15C7).self::ISO6709);
        $loci = $this->kutu('loci', "\0\0\0\0".pack('n', 0)."Evim\0\0".pack('N', 0x11223344).pack('N', 0x55667788).pack('N', 0));
        $udta = $this->kutu('udta', $xyz.$loci);

        $anahtar = 'com.apple.quicktime.location.ISO6709';
        $keys = $this->kutu('keys', "\0\0\0\0".pack('N', 1).pack('N', 8 + strlen($anahtar)).'mdta'.$anahtar);
        $data = $this->kutu('data', pack('N', 1).pack('N', 0).self::ISO6709);
        $ilst = $this->kutu('ilst', $this->kutu(pack('N', 1), $data));
        $meta = $this->kutu('meta', "\0\0\0\0".$keys.$ilst);

        $moov = $this->kutu('moov', $this->kutu('mvhd', str_repeat("\0", 100)).$udta.$meta);

        return $this->ftyp().$moov.$this->mdat();
    }

    private function gpssizMp4(): string
    {
        $moov = $this->kutu('moov', $this->kutu('mvhd', str_repeat("\0", 100)));

        return $this->ftyp().$moov.$this->mdat();
    }

    private function gecici(string $icerik): string
    {
        $yol = tempnam(sys_get_temp_dir(), 'nisoya-video-');
        file_put_contents($yol, $icerik);

        return $yol;
    }

    // ------------------------------------------------------- Temizleyici

    public function test_uc_konum_tasiyicisi_da_silinir_ve_dosya_bozulmaz(): void
    {
        $yol = $this->gecici($this->gpsliMp4());
        $eskiBoyut = filesize($yol);

        $this->assertTrue(app(VideoKonumTemizleyici::class)->temizle($yol));

        $yeni = file_get_contents($yol);

        // Koordinatlar hiçbir kopyada kalmadı (©xyz + Apple meta değeri + loci adı).
        $this->assertStringNotContainsString('+37.3349', $yeni);
        $this->assertStringNotContainsString('-122.0090', $yeni);
        $this->assertStringNotContainsString('Evim', $yeni);
        $this->assertStringNotContainsString(pack('N', 0x11223344), $yeni);

        // Dosya BOZULMADI: boyut aynı, video verisi ve kutu iskeleti yerinde.
        $this->assertSame($eskiBoyut, strlen($yeni));
        $this->assertStringContainsString('VIDEO-VERISI-SABIT-KALMALI', $yeni);
        $this->assertStringContainsString('ftyp', $yeni);
        $this->assertStringContainsString('moov', $yeni);

        unlink($yol);
    }

    public function test_konumsuz_mp4_bayt_bayt_ayni_kalir(): void
    {
        $icerik = $this->gpssizMp4();
        $yol = $this->gecici($icerik);

        $this->assertFalse(app(VideoKonumTemizleyici::class)->temizle($yol));
        $this->assertSame(md5($icerik), md5_file($yol));

        unlink($yol);
    }

    public function test_mp4_olmayan_dosyaya_dokunulmaz(): void
    {
        // WebM (EBML) imzası + rastgele gövde — kutu olarak parse edilemez.
        $icerik = "\x1A\x45\xDF\xA3".random_bytes(256);
        $yol = $this->gecici($icerik);

        $this->assertFalse(app(VideoKonumTemizleyici::class)->temizle($yol));
        $this->assertSame(md5($icerik), md5_file($yol));

        unlink($yol);
    }

    public function test_bozuk_kutu_boylari_temizleyiciyi_sasirtmaz(): void
    {
        // moov var ama içindeki kutu boyu sınır aşıyor — alt ağaç atlanır,
        // dosya olduğu gibi kalır, istisna fırlamaz.
        $bozukIc = pack('N', 999999).'udta'.self::ISO6709;
        $icerik = $this->ftyp().$this->kutu('moov', $bozukIc).$this->mdat();
        $yol = $this->gecici($icerik);

        $this->assertFalse(app(VideoKonumTemizleyici::class)->temizle($yol));
        $this->assertSame(md5($icerik), md5_file($yol));

        unlink($yol);
    }

    // ------------------------------------------------------------ HTTP yolu

    public function test_misafirin_yukledigi_videonun_konumu_diske_inmeden_silinir(): void
    {
        Storage::fake('public');

        $host = User::factory()->create(['email_verified_at' => now(), 'country_code' => 'DE']);
        $event = Event::create([
            'user_id' => $host->id,
            'type' => 'dugun',
            'title' => 'Test Düğünü',
            'starts_at' => now()->subDay(),
            'theme' => '',
            'is_active' => true,
        ]);
        /** @var EventGuest $guest */
        $guest = $event->guests()->create(['name' => 'Misafir Ayşe', 'status' => 'geliyor', 'party_size' => 1]);

        $this->withCookie('davet_misafir_'.$event->id, $guest->token)
            ->post('/davet/'.$event->token.'/medya', [
                // mimeType() şart: Testing\File MIME'ı içerikten değil addan
                // türetir ve 'application/mp4' döndürür — controller da onu
                // görsel sanır (EventMediaTest'teki create(...,'video/mp4')
                // ile aynı gerekçe).
                'files' => [UploadedFile::fake()->createWithContent('dans.mp4', $this->gpsliMp4())->mimeType('video/mp4')],
            ])
            ->assertRedirect(route('davet.show', $event->token));

        $video = $event->media()->where('type', 'video')->firstOrFail();
        $diskteki = Storage::disk('public')->get($video->path);

        $this->assertStringNotContainsString('+37.3349', $diskteki);
        $this->assertStringContainsString('VIDEO-VERISI-SABIT-KALMALI', $diskteki);
    }
}
