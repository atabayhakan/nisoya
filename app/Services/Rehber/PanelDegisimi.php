<?php

namespace App\Services\Rehber;

use Illuminate\Support\Facades\Cache;

/**
 * Panele yeni bir ekran eklendiğinde rehberin GERİDE KALDIĞINI yakalar.
 *
 * ---------------------------------------------------------------------------
 * SAHİBİN "HER YENİLİK BELGEYE EKLENSİN" İSTEĞİNİN KARŞILIĞI
 *
 * İstek doğaldı ama otomatik metin üretimiyle karşılanamaz: bir ekranın ne işe
 * yaradığını AI'ya yazdırmak uydurma üretir (rehber içeriğinde tam olarak bu
 * riski yaşadık ve dört kapıyla önledik).
 *
 * Doğru karşılık: metni ÜRETMEK değil, UNUTMAYI İMKÂNSIZ KILMAK. Yeni ekran
 * belirdiğinde "bunun rehberde sayfası yok" uyarısı çıkar; metni sahip (ya da
 * Claude) yazar.
 *
 * ---------------------------------------------------------------------------
 * NASIL
 *
 * Panel haritasının ekran listesi önbellekte tutulur. Her ölçümde bugünkü
 * liste öncekiyle karşılaştırılır; listede olup rehberde karşılığı olmayan
 * ekranlar "kapsanmayan" sayılır.
 *
 * ÖNBELLEK KALICI (`rememberForever`): bu bir performans önbelleği değil,
 * bir KIYAS TABANI. TTL ile silinseydi her sürede "her şey yeni" görünürdü.
 */
class PanelDegisimi
{
    private const ANAHTAR = 'rehber.panel-anlik';

    /**
     * `PanelHaritasi` BİLEREK enjekte edilmiyor: o sınıf modele YÖNERGE metni
     * üretir (adres + etiket), buradaki iş ise sınıf adlarını karşılaştırmak.
     * Metinden sınıf adı ayrıştırmak kırılgan olurdu; Filament'in kendi
     * kayıtları kanonik kaynak.
     */
    public function __construct(private readonly ElKitabiRehberi $rehber) {}

    /**
     * Rehberde sayfası olmayan panel ekranları.
     *
     * @return list<string>
     */
    public function kapsanmayanEkranlar(): array
    {
        $kapsanan = $this->rehber->tumSayfalar()
            ->pluck('ekran')
            ->filter()
            ->all();

        return array_values(array_filter(
            $this->ekranlar(),
            fn (string $sinif): bool => ! in_array($sinif, $kapsanan, true)
        ));
    }

    /**
     * Kıyas tabanından BU YANA eklenen ekranlar.
     *
     * @return list<string>
     */
    public function yeniEkranlar(): array
    {
        $onceki = Cache::get(self::ANAHTAR);

        if (! is_array($onceki)) {
            // İlk çalıştırma: kıyas tabanı yok, "hepsi yeni" DEMEZ. Taban
            // kurulur ve bu tur sessiz geçer — aksi hâlde ilk raporda
            // panelin tamamı "yenilik" diye listelenirdi.
            $this->tabaniGuncelle();

            return [];
        }

        return array_values(array_diff($this->ekranlar(), $onceki));
    }

    /** Yeni VE rehberde sayfası olmayan ekranlar — asıl sinyal budur. */
    public function eksikYeniEkranlar(): array
    {
        return array_values(array_intersect($this->yeniEkranlar(), $this->kapsanmayanEkranlar()));
    }

    /** Sinyal görüldükten sonra tabanı bugüne çeker. */
    public function tabaniGuncelle(): void
    {
        Cache::forever(self::ANAHTAR, $this->ekranlar());
    }

    /**
     * Paneldeki ekran sınıflarının listesi.
     *
     * PanelHaritasi metin üretiyor (modele yönerge için); buradaki iş
     * karşılaştırma olduğu için Filament'in kendi kayıtlarından okunuyor.
     *
     * @return list<string>
     */
    private function ekranlar(): array
    {
        $sinifllar = array_merge(
            array_map(fn ($s): string => (string) $s, filament()->getPanel('admin')->getPages()),
            array_map(fn ($s): string => (string) $s, filament()->getPanel('admin')->getResources()),
        );

        sort($sinifllar);

        return array_values(array_unique($sinifllar));
    }
}
