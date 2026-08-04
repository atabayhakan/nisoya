<?php

namespace App\Console\Commands;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Console\Command;

/**
 * Onaylanmış rehber içeriğini JSON'dan veritabanına aktarır.
 *
 * ---------------------------------------------------------------------------
 * NEDEN AYRI BİR KOMUT (ve neden yayınlamıyor)
 *
 * Aktarma ile yayınlama BİLEREK ayrı: bu komut içeriği TASLAK olarak yazar,
 * `rehber:yayinla` ayrıca çalıştırılır ve kendi kapısından geçirir. Tek komut
 * olsaydı kapı anlamsızlaşırdı — aktaran taraf yayınlayan taraf olamaz.
 *
 * ---------------------------------------------------------------------------
 * KAYNAK DOSYA
 *
 * `database/data/rehber-icerikleri-*.json` — 13 ajanlı araştırma+doğrulama
 * workflow'unun çıktısından türetildi. İçinde YALNIZ doğrulamadan geçen alanlar
 * var: reddedilen hücreler hiç yok, kısmi onaylıların düşen alanları boş.
 *
 * `dogrulanma_tarihi` dosyadaki `onay_tarihi`'nden gelir — uydurulmaz. Bu tarih
 * ziyaretçiye "Son doğrulama" olarak gösteriliyor ve 90 günde bayatlıyor.
 *
 * ---------------------------------------------------------------------------
 * ABD KAYITLARI YOK — OLUŞTURULUR
 *
 * `RehberAlmanyaSeeder` yalnız Almanya için islem kaydı tohumlamıştı; ABD
 * temsilciliklerinde hiç kayıt yok. Bu yüzden updateOrCreate: Almanya'da var
 * olanı günceller, ABD'de yenisini oluşturur.
 */
class RehberIcerikAktar extends Command
{
    protected $signature = 'rehber:icerik-aktar
        {dosya : JSON dosyasının yolu}
        {--rapor : Hiçbir şey yazma, yalnız ne olacağını göster}';

    protected $description = 'Onaylanmış rehber içeriğini JSON dosyasından taslak olarak aktarır';

    public function handle(): int
    {
        $yol = base_path($this->argument('dosya'));

        if (! is_file($yol)) {
            $this->error('Dosya bulunamadı: '.$yol);

            return self::FAILURE;
        }

        $veri = json_decode((string) file_get_contents($yol), true);

        if (! is_array($veri) || ! isset($veri['icerikler']) || ! is_array($veri['icerikler'])) {
            $this->error('Dosya biçimi geçersiz: "icerikler" dizisi yok.');

            return self::FAILURE;
        }

        $onayTarihi = $veri['onay_tarihi'] ?? null;

        if (! is_string($onayTarihi) || $onayTarihi === '') {
            $this->error('Dosyada "onay_tarihi" yok. Doğrulanma tarihi uydurulamaz.');

            return self::FAILURE;
        }

        $rapor = (bool) $this->option('rapor');
        $yazilan = 0;
        $atlanan = [];

        foreach ($veri['icerikler'] as $satir) {
            $temsilcilik = Temsilcilik::query()->where('slug', $satir['temsilcilik'] ?? '')->first();
            $tur = IslemTuru::query()->where('slug', $satir['islem'] ?? '')->first();

            if ($temsilcilik === null || $tur === null) {
                $atlanan[] = ($satir['temsilcilik'] ?? '?').'/'.($satir['islem'] ?? '?').' — temsilcilik veya işlem türü bulunamadı';

                continue;
            }

            if (empty($satir['evraklar'])) {
                $atlanan[] = $temsilcilik->slug.'/'.$tur->slug.' — evrak listesi boş';

                continue;
            }

            $this->line('  '.$temsilcilik->ad.' / '.$tur->ad.' ('.count($satir['evraklar']).' evrak)');

            if ($rapor) {
                $yazilan++;

                continue;
            }

            TemsilcilikIslemi::updateOrCreate(
                ['temsilcilik_id' => $temsilcilik->id, 'islem_turu_id' => $tur->id],
                [
                    'evraklar' => $satir['evraklar'],
                    'sure_metni' => $satir['sure_metni'] ?: null,
                    'notlar' => $satir['notlar'] ?: null,
                    'resmi_kaynak_url' => $satir['kaynak_url'],
                    'dogrulanma_tarihi' => $onayTarihi,

                    // TASLAK kalır. Yayına alma ayrı komutun ve kendi kapısının işi.
                    'status' => TemsilcilikIslemi::STATUS_TASLAK,
                ],
            );

            $yazilan++;
        }

        foreach ($atlanan as $a) {
            $this->warn('  ✗ '.$a);
        }

        $this->info(($rapor ? 'RAPOR — yazılacak: ' : 'Aktarılan: ').$yazilan.' · atlanan: '.count($atlanan));

        if (! $rapor && $yazilan > 0) {
            $this->line('Sıradaki adım: php artisan rehber:yayinla --rapor');
        }

        return self::SUCCESS;
    }
}
