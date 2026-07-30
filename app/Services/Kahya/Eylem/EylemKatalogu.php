<?php

namespace App\Services\Kahya\Eylem;

use App\Services\Kahya\Eylem\Eylemler\AyarDoldur;
use App\Services\Kahya\Eylem\Eylemler\EtiketEkle;
use App\Services\Kahya\Eylem\Eylemler\IlanlariEtiketle;
use App\Services\Kahya\Eylem\Eylemler\KategoriEkle;
use App\Services\Kahya\Eylem\Eylemler\ParaBirimiEkle;
use App\Services\Kahya\Eylem\Eylemler\SehirEkle;
use App\Services\Kahya\Eylem\Eylemler\SeoDoldur;
use App\Services\Kahya\Eylem\Eylemler\SiraDegistir;
use App\Services\Kahya\Eylem\Eylemler\UlkeDurumDegistir;
use App\Services\Kahya\Eylem\Eylemler\UlkeEkle;
use RuntimeException;

/**
 * Kâhya'nın yapabileceği işlerin TAM listesi.
 *
 * Bu dosya güvenlik sınırının kendisidir: burada olmayan bir iş yapılamaz.
 * Yapay zekâ serbest metin üretir ama o metin buradaki bir ada karşılık
 * gelmiyorsa hiçbir şey olmaz.
 *
 * ---------------------------------------------------------------------------
 * "PANELİ ÖĞRENSİN" İSTEĞİNİN GERÇEK KARŞILIĞI BURASI
 *
 * Kâhya yönetim panelini gezerek öğrenmiyor. Ne yapabileceği burada
 * BİLDİRİLİYOR ve her eylem kendini Türkçe anlatıyor. Keşfederek öğrenen bir
 * ajan keşfettiğini yanlış anlayabilir; bildirilmiş bir yetenek listesi
 * yanlış anlaşılamaz — en fazla eksik olur, o da göze batar.
 *
 * Yeni bir yetenek eklemek = buraya bir satır eklemek.
 */
class EylemKatalogu
{
    /** @var list<class-string<Eylem>> */
    private const EYLEMLER = [
        // Liste yönetimi — sahibin örneği ("ülkeler kısmına Japonya ekle") burada.
        UlkeEkle::class,
        UlkeDurumDegistir::class,
        SehirEkle::class,
        KategoriEkle::class,
        EtiketEkle::class,
        ParaBirimiEkle::class,
        SiraDegistir::class,

        // Ayar ve içerik
        AyarDoldur::class,

        // Otomasyon — "bu bölümü Kâhya kendi yapsın" işleri (2026-07-30)
        IlanlariEtiketle::class,
        SeoDoldur::class,
    ];

    /** @var array<string, Eylem>|null */
    private ?array $onbellek = null;

    /** @return array<string, Eylem> ad => eylem */
    public function hepsi(): array
    {
        if ($this->onbellek !== null) {
            return $this->onbellek;
        }

        $liste = [];

        foreach (self::EYLEMLER as $sinif) {
            /** @var Eylem $eylem */
            $eylem = app($sinif);
            $ad = $eylem->ad();

            // Aynı ad iki eyleme verilirse biri sessizce diğerini gölgeler ve
            // yapay zekâ yanlış işi çağırır. Sessiz kalınacak yer değil.
            if (isset($liste[$ad])) {
                throw new RuntimeException("Katalogda yinelenen eylem adı: [{$ad}].");
            }

            $liste[$ad] = $eylem;
        }

        return $this->onbellek = $liste;
    }

    public function bul(string $ad): ?Eylem
    {
        return $this->hepsi()[$ad] ?? null;
    }

    /**
     * Katalogun yapay zekâya verilecek hâli.
     *
     * JSON değil düz metin: modeller yetenek listelerini serbest metinde daha
     * iyi kavrıyor ve bu metin doğrudan yönergenin (prompt) içine giriyor.
     */
    public function yapayZekaIcin(): string
    {
        $satirlar = [];

        foreach ($this->hepsi() as $ad => $eylem) {
            $parcalar = ["### {$ad}", $eylem->aciklama()];

            $sema = $eylem->sema();

            if ($sema !== []) {
                $parcalar[] = 'Parametreler:';
                foreach ($sema as $alan => $aciklama) {
                    $parcalar[] = "  - {$alan}: {$aciklama}";
                }
            }

            if ($eylem->ornekler() !== []) {
                $parcalar[] = 'Örnek istekler: '.implode(' · ', $eylem->ornekler());
            }

            // Risk bilgisi modele DE söyleniyor: yüksek riskli bir işi
            // seçtiğinde kullanıcıya "onayına sunuyorum" diyebilsin.
            $parcalar[] = 'Risk: '.$eylem->risk()->etiket().
                ($eylem->risk()->onayGerekir() ? ' (uygulanmadan önce sahibin onayına sunulur)' : ' (doğrudan uygulanır, geri alınabilir)');

            $satirlar[] = implode("\n", $parcalar);
        }

        return implode("\n\n", $satirlar);
    }
}
