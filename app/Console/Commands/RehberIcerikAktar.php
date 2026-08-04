<?php

namespace App\Console\Commands;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
        $tasan = 0;

        // TEK İŞLEM (transaction) — canlıda öğrenildi.
        //
        // İlk sürüm satır satır yazıyordu. Üçüncü kayıtta MySQL "Data too long"
        // hatası verdi ve komut ORTADA kaldı: ilk iki kayıt TASLAĞA çekilmişti
        // ama yayınlanmamıştı, yani o ana kadar YAYINDA olan Köln vekaletname
        // sayfası 404 döndü. Yarım uygulanmış aktarım, hiç uygulanmamış
        // aktarımdan kötüdür.
        DB::transaction(function () use ($veri, $onayTarihi, $rapor, &$yazilan, &$atlanan, &$tasan) {
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

                [$sure, $notlar] = $this->sureyiKisalt(
                    (string) ($satir['sure_metni'] ?? ''),
                    (string) ($satir['notlar'] ?? ''),
                    $temsilcilik->slug.'/'.$tur->slug,
                    $tasan,
                );

                TemsilcilikIslemi::updateOrCreate(
                    ['temsilcilik_id' => $temsilcilik->id, 'islem_turu_id' => $tur->id],
                    [
                        'evraklar' => $satir['evraklar'],
                        'sure_metni' => $sure ?: null,
                        'notlar' => $notlar ?: null,
                        'resmi_kaynak_url' => $satir['kaynak_url'],
                        'dogrulanma_tarihi' => $onayTarihi,

                        // TASLAK kalır. Yayına alma ayrı komutun ve kendi kapısının işi.
                        'status' => TemsilcilikIslemi::STATUS_TASLAK,
                    ],
                );

                $yazilan++;
            }
        });

        foreach ($atlanan as $a) {
            $this->warn('  ✗ '.$a);
        }

        if ($tasan > 0) {
            $this->warn('  ↪ '.$tasan.' kayıtta uzun süre metni notlara taşındı (süre alanı rozet, kısa olmalı).');
        }

        $this->info(($rapor ? 'RAPOR — yazılacak: ' : 'Aktarılan: ').$yazilan.' · atlanan: '.count($atlanan));

        if (! $rapor && $yazilan > 0) {
            $this->line('Sıradaki adım: php artisan rehber:yayinla --rapor');
        }

        return self::SUCCESS;
    }

    /**
     * `sure_metni` KISA olmak zorunda — arayüzde bir rozet: "⏱ Süre: {değer}".
     * Kolon da buna göre `string(200)`; docblock'unda örnekleri yazılı:
     * "aynı gün" / "2-4 hafta".
     *
     * Araştırma ajanları oraya paragraf yazdı ("Sayfada net bir işlem süresi
     * verilmiyor. Pasaportlar Türkiye'de basılıp gönderiliyor; ... 20 gün sonra
     * sorulabiliyor."). Bu iki ayrı sorun:
     *   1. MySQL 200 karakteri aşan değeri REDDEDER → aktarım çöker.
     *   2. Çökmese bile o metin bir rozetin içine sığmaz, düzeni bozar.
     *
     * Yerelde fark edilmedi çünkü SQLite VARCHAR uzunluğunu DAYATMAZ — değer
     * sessizce yazıldı, testler geçti, hata yalnız canlıda çıktı.
     *
     * Çözüm: uzun metni ATMA (bilgi değerli), NOTLARA taşı. Rozet kısa kalır,
     * bilgi kaybolmaz.
     *
     * @return array{0: string, 1: string} [sure_metni, notlar]
     */
    private function sureyiKisalt(string $sure, string $notlar, string $ad, int &$tasan): array
    {
        $sure = trim($sure);

        if ($sure === '' || mb_strlen($sure) <= self::SURE_AZAMI) {
            return [$sure, trim($notlar)];
        }

        $tasan++;
        $this->line('    ↪ '.$ad.': süre metni '.mb_strlen($sure).' karakter, notlara taşındı');

        return ['', trim($sure."\n\n".$notlar)];
    }

    /** Rozet metninin sığdığı azami uzunluk (kolon `string(200)`). */
    private const SURE_AZAMI = 200;
}
