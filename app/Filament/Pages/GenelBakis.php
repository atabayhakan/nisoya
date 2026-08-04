<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Reports\NisoyaDosyasi;
use App\Services\Rehber\SurecSeridi;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * "Nisoya Genel Bakış" — yazdırılabilir tanıtım belgesi.
 *
 * ---------------------------------------------------------------------------
 * NEDEN PDF KÜTÜPHANESİ YOK (araştırma kararı, plan 2026-08-04)
 *
 * Belge bir Blade sayfası + `@media print` CSS'i. İndirme = tarayıcının
 * Yazdır → PDF'i. Maliyet sıfır, risk sıfır, Tailwind v4 (flex/grid/oklch)
 * tam çalışır.
 *
 * Alternatifler ölçüldü ve elendi:
 *   · dompdf/mPDF — flexbox ve grid TANIMAZ, oklch() bilmez ve HATA VERMEZ;
 *     kutular sessizce üst üste biner. Sitenin tasarım dilini tablolarla
 *     ikinci kez kurmak gerekirdi, ikisi altı ayda ayrışırdı.
 *   · Browsershot/Puppeteer — deploy.sh zaten `npm ci` çalıştırıyor;
 *     postinstall her deploy'da 280 MB Chromium indirmeye kalkar ve ağ
 *     hatasında CANLI DEPLOY KIRILIR.
 *   · Gotenberg — 4 GB'lık VPS'te sürekli ayakta 1 GB'lık konteyner.
 *
 * Sunucu tarafı üretim (arşiv, e-posta eki) gerçekten gerekirse M3'te
 * `spatie/laravel-pdf` + sistem Chromium'u, kuyrukta.
 *
 * ---------------------------------------------------------------------------
 * BELGEDE ELLE YAZILMIŞ TEK RAKAM YOK
 *
 * Her sayı {@see NisoyaDosyasi}'ndan. Şablonda sabit bir sayı olsaydı ikinci
 * ayda yalan olurdu.
 *
 * NOT: Bu belge İÇERİYE bakar (sahip, yeni ekip üyesi). YATIRIMCI memosu ayrı
 * bir şablondur (M2) — özellik listesi yatırımcıya traction değil, traction
 * yokluğunun itirafı olarak okunur.
 */
class GenelBakis extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'Genel Bakış (yazdır)';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.genel-bakis';

    public function getTitle(): string
    {
        return 'Nisoya Genel Bakış';
    }

    /**
     * Şablonda `@php` bloğu KULLANILMIYOR: bu depoda Filament bileşen
     * yuvalarının içinde derleme hatası veriyor (El Kitabı M0'da ölçüldü).
     * Veriyi sınıf hazırlar, şablon yalnız basar.
     */
    public function dosya(): NisoyaDosyasi
    {
        return app(NisoyaDosyasi::class);
    }

    /** @return array{ilan: int, satici: int, sehir: int, ulke: int} */
    public function envanter(): array
    {
        return $this->dosya()->envanter();
    }

    /** @return array{toplam: int, dogrulanmis: int} */
    public function uyeler(): array
    {
        return $this->dosya()->uyeler();
    }

    /** @return array{rehber_icerik: int, rehber_ulke: int, sayfa: int} */
    public function icerikSayilari(): array
    {
        return $this->dosya()->icerik();
    }

    /** @return list<string> */
    public function acikModuller(): array
    {
        return $this->dosya()->moduller();
    }

    public function kesimMetni(): string
    {
        return $this->dosya()->kesimTarihi()->translatedFormat('d F Y H:i');
    }

    /** Envanter bir liste sayfasını (12) doldurmuyorsa darboğaz açıkça yazılır. */
    public function envanterZayifMi(): bool
    {
        return $this->envanter()['ilan'] < 12;
    }

    /** @return list<array{anahtar: string, etiket: string, aciklama: string, dal: string, adet: int}> */
    public function surecAdimlari(): array
    {
        return app(SurecSeridi::class)->adimlar();
    }
}
