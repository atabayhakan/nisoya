<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Filament\Resources\NavigationLinks\NavigationLinkResource;
use App\Filament\Resources\Pages\PageResource;
use App\Services\Rehber\ElKitabiRehberi;
use App\Services\Rehber\RehberSayfasi;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * El Kitabı — panelin "kendini anlatan" rehberi.
 *
 * ---------------------------------------------------------------------------
 * ESKİ HÂLİ VE NEDEN DEĞİŞTİ
 *
 * Sayfa 88 satırlık, ELLE YAZILMIŞ bir bağlantı kartı dizisiydi. Doğru
 * yöndeydi ama üç şeyi yapamıyordu: aranamıyordu, "nasıl yaparım" sorusunu
 * yanıtlamıyordu ve panel büyüdükçe sessizce eksiliyordu.
 *
 * Artık `docs/rehber/*.md` okuyucusu. Metin markdown'da yaşıyor çünkü aynı
 * metin DÖRT yüzeye birden hizmet ediyor (bkz. plan 2026-08-04):
 * bu ekran · ekran içi "Yardım" · Kâhya'nın cevapları · belgeler.
 * Kod içine gömülü ikinci bir anlatım olsaydı üçüncü ayda ikisi çelişirdi.
 *
 * Hızlı erişim kartları KALDI: onlar metin değil CANLI URL'ler
 * (`::getUrl()`), yani yanlış yazıya dönüşemezler. Rehber "nasıl", kartlar
 * "nereye" sorusunu yanıtlar.
 */
class ElKitabi extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'El Kitabı';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.el-kitabi';

    /** Arama kutusu — Livewire ile canlı süzer. */
    public string $arama = '';

    /** Açık olan rehber sayfasının slug'ı. */
    public string $acik = '';

    public function mount(): void
    {
        $ilk = $this->rehber()->tumSayfalar()->first();

        $this->acik = $ilk instanceof RehberSayfasi ? $ilk->slug : '';
    }

    public function getTitle(): string
    {
        return 'El Kitabı';
    }

    public function ac(string $slug): void
    {
        $this->acik = $slug;
    }

    /** @return Collection<int, RehberSayfasi> */
    public function sayfalar(): Collection
    {
        return $this->rehber()->ara($this->arama);
    }

    public function acikSayfa(): ?RehberSayfasi
    {
        $sayfalar = $this->sayfalar();

        // Arama sonucu açık sayfayı dışarıda bırakabilir; o durumda ilk sonuca
        // düş, yoksa kullanıcı boş bir içerik alanına bakar ve aramanın
        // bozulduğunu sanır.
        return $sayfalar->firstWhere('slug', $this->acik) ?? $sayfalar->first();
    }

    /**
     * Açık sayfanın bağlı olduğu Filament ekranının URL'i (yoksa null).
     *
     * Blade'de DEĞİL burada hesaplanıyor: bu depoda `@php` blokları Filament
     * bileşen yuvalarının içinde derleme hatası veriyor (ölçüldü — hem kısa
     * `@php(...)` formu hem blok form). Mantık zaten şablonun işi değil.
     */
    public function acikSayfaUrl(): ?string
    {
        $sinif = $this->acikSayfa()?->ekran;

        if ($sinif === null || ! class_exists($sinif) || ! method_exists($sinif, 'getUrl')) {
            return null;
        }

        return $sinif::getUrl();
    }

    /**
     * Hızlı erişim — en sık gidilen ekranlar.
     *
     * Bunlar metin DEĞİL canlı URL: bir ekranın adresi değişirse burası
     * kendiliğinden doğru kalır. Rehber sayfalarına taşınmadı çünkü markdown'a
     * yazılan bir adres bayatlayabilir.
     *
     * @return array<int, array{baslik: string, url: string, ikon: string}>
     */
    public function hizliErisim(): array
    {
        return [
            ['baslik' => 'İçerik ve metinler', 'url' => IcerikAyarlari::getUrl(), 'ikon' => 'heroicon-o-document-text'],
            ['baslik' => 'Tasarım', 'url' => TasarimAyarlari::getUrl(), 'ikon' => 'heroicon-o-swatch'],
            ['baslik' => 'Menü', 'url' => NavigationLinkResource::getUrl(), 'ikon' => 'heroicon-o-bars-3'],
            ['baslik' => 'Sayfalar', 'url' => PageResource::getUrl(), 'ikon' => 'heroicon-o-document-plus'],
            ['baslik' => 'Yedekleme', 'url' => Yedekleme::getUrl(), 'ikon' => 'heroicon-o-circle-stack'],
            ['baslik' => 'Kurtarma Kiti', 'url' => KurtarmaKiti::getUrl(), 'ikon' => 'heroicon-o-lifebuoy'],
            ['baslik' => 'E-posta (SMTP)', 'url' => MailAyarlari::getUrl(), 'ikon' => 'heroicon-o-envelope'],
            ['baslik' => 'Yapay zekâ', 'url' => YapayZekaAyarlari::getUrl(), 'ikon' => 'heroicon-o-sparkles'],
        ];
    }

    private function rehber(): ElKitabiRehberi
    {
        return app(ElKitabiRehberi::class);
    }
}
