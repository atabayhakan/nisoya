<?php

namespace App\Console\Commands;

use App\Models\TemsilcilikIslemi;
use Illuminate\Console\Command;

/**
 * Rehber içeriğini yayına alır — AMA YALNIZ YAYINA HAZIRSA.
 *
 * ---------------------------------------------------------------------------
 * NEDEN BİR KAPI VAR
 *
 * Bu içerikler vekaletname, pasaport, doğum bildirimi gibi işlemleri anlatıyor.
 * İnsan sayfaya bakıp randevu alıyor, yol gidiyor, evrak hazırlıyor. Eksik
 * evrak listesi ya da hiçbir yere gitmeyen bir "resmî kaynak" bağlantısı somut
 * zarar demek.
 *
 * K7 kararı ("içerik resmî kaynaktan kendi ifadeyle, taslak-önce") şu ana kadar
 * yalnız bir TASARIM BELGESİNDE yazılıydı. Belge kimseyi durdurmaz; panelden
 * tek tıkla eksik içerik yayına alınabilirdi. Bu komut kuralı KODA gömer.
 *
 * ---------------------------------------------------------------------------
 * KAPI ÖLÇÜTLERİ
 *
 * 1. `evraklar` DOLU olmalı — rehberin tek gerçek değeri bu. Boşsa sayfa
 *    ziyaretçiye hiçbir şey söylemez.
 * 2. `resmi_kaynak_url` İŞLEME ÖZEL olmalı. Genel `konsolosluk.gov.tr` adresi
 *    reddedilir: 210 taslağın hepsi oraya işaret ediyordu ve "Resmî kaynağı aç"
 *    düğmesi ziyaretçiyi işiyle ilgisiz bir ana sayfaya bırakıyordu.
 * 3. `dogrulanma_tarihi` DOLU olmalı — birinin bu içeriği kaynağa bakarak
 *    doğruladığının kaydı. Sayfa bu tarihi ziyaretçiye gösteriyor; boşken
 *    "henüz doğrulanmadı" yazıyor ve o hâlde yayında olmamalı.
 *
 * ÜCRET BİLİNÇLİ OLARAK ARANMAZ. Sahip kararı (2026-08-04): ücret
 * yayınlanmayacak, çünkü yıllık tarifeyle değişiyor ve bayatlaması en muhtemel
 * alan. Ziyaretçi güncel tarifeyi resmî kaynaktan görür. Aynı sebeple `sure`
 * da zorunlu değil — çoğu resmî sayfa süre taahhüdü vermiyor; olanı basarız,
 * olmayanı uydurmayız.
 */
class RehberYayinla extends Command
{
    protected $signature = 'rehber:yayinla
        {--ulke= : Yalnız bu ülke kodu (ör. DE)}
        {--temsilcilik=* : Yalnız bu temsilcilik slug\'ları}
        {--islem=* : Yalnız bu işlem türü slug\'ları}
        {--rapor : Hiçbir şey yayınlama, yalnız neyin hazır olduğunu yaz}';

    protected $description = 'Hazır rehber içeriklerini yayına alır; eksik olanları reddeder';

    /** İşleme özel olmayan, reddedilecek genel kaynak adresleri. */
    private const GENEL_KAYNAKLAR = [
        'https://www.konsolosluk.gov.tr',
        'https://konsolosluk.gov.tr',
        'http://www.konsolosluk.gov.tr',
    ];

    public function handle(): int
    {
        $sorgu = TemsilcilikIslemi::query()
            ->where('status', TemsilcilikIslemi::STATUS_TASLAK)
            ->with(['temsilcilik', 'islemTuru']);

        if ($ulke = $this->option('ulke')) {
            $sorgu->whereHas('temsilcilik', fn ($q) => $q->where('country_code', strtoupper((string) $ulke)));
        }

        if ($slugs = array_filter((array) $this->option('temsilcilik'))) {
            $sorgu->whereHas('temsilcilik', fn ($q) => $q->whereIn('slug', $slugs));
        }

        if ($islemler = array_filter((array) $this->option('islem'))) {
            $sorgu->whereHas('islemTuru', fn ($q) => $q->whereIn('slug', $islemler));
        }

        $adaylar = $sorgu->get();

        if ($adaylar->isEmpty()) {
            $this->info('Ölçütlere uyan taslak yok.');

            return self::SUCCESS;
        }

        $hazir = [];
        $eksik = [];

        foreach ($adaylar as $kayit) {
            $sebepler = $this->eksikler($kayit);

            if ($sebepler === []) {
                $hazir[] = $kayit;
            } else {
                $eksik[] = [$kayit, $sebepler];
            }
        }

        $this->line('Aday taslak: '.$adaylar->count().' · hazır: '.count($hazir).' · eksik: '.count($eksik));

        foreach ($eksik as [$kayit, $sebepler]) {
            $this->warn('  ✗ '.$this->ad($kayit).' — '.implode(', ', $sebepler));
        }

        if ($hazir === []) {
            $this->error('Yayına alınacak hazır içerik yok.');

            return self::SUCCESS;
        }

        foreach ($hazir as $kayit) {
            $this->line('  ✓ '.$this->ad($kayit));
        }

        if ($this->option('rapor')) {
            $this->info('RAPOR: hiçbir şey yayınlanmadı.');

            return self::SUCCESS;
        }

        foreach ($hazir as $kayit) {
            $kayit->update(['status' => TemsilcilikIslemi::STATUS_YAYIN]);
        }

        $this->info(count($hazir).' içerik yayına alındı.');

        return self::SUCCESS;
    }

    /**
     * Kaydın yayına HAZIR OLMAMA sebepleri. Boş dizi = hazır.
     *
     * @return list<string>
     */
    private function eksikler(TemsilcilikIslemi $kayit): array
    {
        $sebepler = [];

        if (empty($kayit->evraklar)) {
            $sebepler[] = 'evrak listesi boş';
        }

        $kaynak = trim((string) $kayit->resmi_kaynak_url);

        if ($kaynak === '') {
            $sebepler[] = 'kaynak adresi yok';
        } elseif (in_array(rtrim($kaynak, '/'), self::GENEL_KAYNAKLAR, true)) {
            $sebepler[] = 'kaynak adresi işleme özel değil (genel adres)';
        }

        if ($kayit->dogrulanma_tarihi === null) {
            $sebepler[] = 'doğrulanma tarihi yok';
        }

        return $sebepler;
    }

    private function ad(TemsilcilikIslemi $kayit): string
    {
        return ($kayit->temsilcilik->ad ?? '?').' / '.($kayit->islemTuru->ad ?? '?');
    }
}
