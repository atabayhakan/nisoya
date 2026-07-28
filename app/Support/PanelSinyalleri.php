<?php

namespace App\Support;

use App\Models\Listing;
use Illuminate\Support\Collection;

/**
 * Panelin (/panel) tüm sinyallerini taşıyan salt-okunur kap.
 *
 * SÖZLEŞME: hiçbir Blade partial'ı KENDİ SORGUSUNU ATMAZ. Her şey
 * PanelController'da toplanır, buraya konur, view'a tek değişken olarak
 * geçer. Bekçi testi partial'larda `Model::` ve `->count(` arayarak bunu
 * mühürler — aksi hâlde "bir sayaç daha ekleyelim" baskısı sayfayı sessizce
 * N+1'e sürükler.
 *
 * AZALTMA DEFTERİ — hangi karar kaç sorgu kazandırdı (bu defter, ileride
 * "bir tane daha eklesek ne olur" sorusunun bedelini görünür tutmak için):
 *   -2  okunmamış mesaj/bildirim istek-başı memo (User'daki örnek property)
 *   -6  tüm sayaçlar tek withCount sorgusunda (kart başına ayrı sorgu yerine)
 *   -1  şirket varlığı withExists ile aynı sorguda (ayrı exists sorgusu yerine)
 *   -2  anlaşma sinyalleri tek sorguda (sorunlu/teklif/değerlendirilmemiş ayrı ayrı yerine)
 *   -1  ilan özeti agregat-only selectRaw (GROUP BY yok → MySQL only_full_group_by bağışıklığı)
 *   -3  trustProfile() reddedildi (kendi ana ekranında güven kademesi göstermek anlamsız)
 *   -4  "kayıtlı aramanda yeni sonuç" reddedildi (arama başına ayrı LIKE '%..%' taraması)
 *   -N  etkinlik başına LCV dökümü reddedildi (Event::rsvpSummary N+1)
 *
 * Sözleşme: panel EN FAZLA +6 sorgu ekler.
 */
final class PanelSinyalleri
{
    /**
     * @param  array<int, array{tur: string, konusmaId: int|null, karsiTarafAd: string, karsiTarafKullaniciAdi: string|null, tutar: string|null, currency: string|null}>  $bekleyenSatirlar
     * @param  array<int, string>  $profilEksikleri
     * @param  Collection<int, Listing>  $ulkeIlanlari
     */
    public function __construct(
        // --- Bekleyenler (eylem gerektiren) ---
        public array $bekleyenSatirlar = [],
        public bool $dahaFazlasiVar = false,
        public int $okunmamisMesaj = 0,
        public int $okunmamisBildirim = 0,
        public int $gelenYeniBasvuru = 0,
        public int $oneCikarmaBitiyor = 0,

        // --- Durumun (bilgi) ---
        public int $aktifIlan = 0,
        public int $bekleyenIlan = 0,
        public int $pasifIlan = 0,
        public int $toplamIlan = 0,
        public int $toplamGoruntulenme = 0,
        public int $favori = 0,
        public int $kayitliArama = 0,
        public int $basvuru = 0,
        public int $gorusme = 0,
        public int $yaklasanDavetiye = 0,
        public int $davet = 0,

        // --- Bağlam ---
        public bool $sirketVar = false,
        public bool $isModulu = false,
        public bool $davetiyeModulu = false,
        public array $profilEksikleri = [],
        public bool $yetenekHavuzuKapali = false,
        public ?Collection $ulkeIlanlari = null,
    ) {}

    /** Eylem bekleyen bir şey var mı? */
    public function bekleyenVarMi(): bool
    {
        return $this->bekleyenSatirlar !== []
            || $this->okunmamisMesaj > 0
            || $this->okunmamisBildirim > 0
            || $this->gelenYeniBasvuru > 0
            || $this->oneCikarmaBitiyor > 0;
    }

    /** Gösterilecek bir rakam var mı? Hepsi 0 ise katman hiç basılmaz. */
    public function rakamVarMi(): bool
    {
        return $this->toplamIlan > 0
            || $this->toplamGoruntulenme > 0
            || $this->favori > 0
            || $this->kayitliArama > 0
            || $this->basvuru > 0
            || $this->yaklasanDavetiye > 0
            || $this->davet > 0;
    }

    /**
     * Hiç sinyali olmayan kullanıcı: ne kuyruk ne rakam basılır, tek bir "0"
     * bile görünmez — bunun yerine başlangıç ekranı gelir.
     */
    public function bosDurum(): bool
    {
        return ! $this->bekleyenVarMi() && ! $this->rakamVarMi();
    }
}
