<?php

namespace App\Filament\Pages;

use App\Services\Demo\DemoDefteri;
use App\Services\Demo\DemoFabrikasi;
use App\Services\Demo\DemoTemizleyici;
use App\Support\Settings;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;
use UnitEnum;

/**
 * Örnek (demo) veri yönetimi — üretimin ve geri almanın insan kapısı.
 *
 * ---------------------------------------------------------------------------
 * BU SAYFANIN ASIL İŞİ ÜRETMEK DEĞİL, GERİ ALMAYI GÖRÜNÜR TUTMAK
 *
 * Sahte veri üretmek kolaydır. Tehlikeli olan, üretildiğini unutmaktır: aylar
 * sonra "sitede 40 ilan var" diye sevinmek ve otuz sekizinin örnek olduğunu
 * fark etmemek. Bu yüzden sayfa her partiyi, ne zaman üretildiğini ve tek
 * tıkla geri alma düğmesini yan yana gösterir.
 *
 * `demo.mcp_acik` anahtarı da burada: yapay zekânın örnek veri üretebilmesi
 * bir insan tıklamasıyla açılır. Bir kez açılır — her çağrıda onay istemek,
 * "ajana söyleyince yapsın" isteğini boşa çıkarırdı.
 */
class OrnekVeri extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'Örnek Veri';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.ornek-veri';

    public int $uyeSayisi = 4;

    public int $ilanSayisi = 2;

    public bool $gorunur = false;

    public bool $aiGorsel = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTitle(): string
    {
        return 'Örnek Veri';
    }

    public function getSubheading(): ?string
    {
        return 'Siteyi dolu hâlde denemek için örnek üye, ilan ve işlem üret — sonra tek tıkla geri al.';
    }

    public function mount(): void
    {
        $this->uyeSayisi = 4;
        $this->ilanSayisi = 2;
        $this->gorunur = false;
        $this->aiGorsel = false;
    }

    public function mcpAcikMi(): bool
    {
        return Settings::get('demo.mcp_acik') === '1';
    }

    public function uretimMi(): bool
    {
        return app()->isProduction();
    }

    /** @return list<array{parti: string, olusturuldu: ?string, adet: int, dokum: array<string, int>}> */
    public function getPartiler(): array
    {
        return app(DemoDefteri::class)->partiler();
    }

    public function getArtikSayisi(): int
    {
        return app(DemoTemizleyici::class)->artikSayisi();
    }

    public function mcpKapisiniDegistir(): void
    {
        $yeni = ! $this->mcpAcikMi();

        Settings::setMany(['demo.mcp_acik' => $yeni ? '1' : '0']);

        Notification::make()
            ->title($yeni ? 'Yapay zekâ örnek veri üretebilir' : 'Örnek veri üretme kapısı kapatıldı')
            ->body($yeni
                ? 'MCP araçları (demo-uret, demo-sil) artık görünür. Üretilen her şey geri alınabilir.'
                : 'MCP araç listesinden düştüler. Bu sayfadaki düğmeler çalışmaya devam eder.')
            ->success()
            ->send();
    }

    public function uret(): void
    {
        $uye = min(50, max(1, $this->uyeSayisi));
        $ilan = min(10, max(0, $this->ilanSayisi));

        try {
            $sonuc = app(DemoFabrikasi::class)->uret($uye, $ilan, $this->gorunur, $this->aiGorsel);
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('Örnek veri üretilemedi')
                ->body(class_basename($e).' — ayrıntı sunucu log\'unda.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title('Örnek veri üretildi: '.$sonuc['parti'])
            ->body(
                "{$sonuc['uye']} üye, {$sonuc['ilan']} ilan, {$sonuc['gorsel']} görsel, ".
                "{$sonuc['sohbet']} sohbet, {$sonuc['anlasma']} anlaşma. ".
                ($this->gorunur
                    // NEREYE BAKACAĞI YAZILI OLMAK ZORUNDA — sahip "ürettim ama
                    // sayfada yok" diye aramasın.
                    //
                    // Bu metin bir kez yanlış hâle geldi: örnek ilanlar liste
                    // sayfasından çıkarılınca (2026-08-08 demo sızıntısı
                    // düzeltmesi) burada hâlâ "/ilanlar ve kategori
                    // sayfalarında görünürler" yazıyordu. Ekran, kodun
                    // YAPMADIĞI şeyi anlatıyordu. Kural artık tek yerde
                    // özetleniyor; sızıntı düzeltmesi değişirse burası da
                    // değişmeli (bkz. Listing::scopeGercek).
                    ? 'İlanlar YAYINDA ve "ÖRNEK" işareti taşıyor. Gerçek ilanların ARASINA '
                        .'karışmazlar ve hiçbir sayaca girmezler: /ilanlar (ve /emlak, /vasita) '
                        .'sayfalarında ancak o süzgeçle hiç GERÇEK ilan bulunamadığında, '
                        .'"örnek ilanlar" başlıklı ayrı bir rafta çıkarlar; ana sayfa vitrininde '
                        .'de yalnız hiç gerçek ilan yokken. Haritada, hızlı aramada ve '
                        .'sitemap\'te hiç yer almazlar, detay sayfaları "noindex" taşır.'
                    : 'İlanlar taslak — sitede görünmüyorlar.')
            )
            ->success()
            ->persistent()
            ->send();
    }

    public function partiSil(string $parti): void
    {
        $sonuc = app(DemoTemizleyici::class)->sil($parti);

        Notification::make()
            ->title('Parti geri alındı: '.$parti)
            ->body(
                collect($sonuc['silinen'])->map(fn (int $a, string $ad): string => "{$ad} {$a}")->implode(', ')
                .' · '.$sonuc['dosya'].' dosya silindi.'
            )
            ->success()
            ->send();
    }

    public function hepsiniSil(): void
    {
        $sonuc = app(DemoTemizleyici::class)->hepsiniSil();

        Notification::make()
            ->title($sonuc['parti_sayisi'].' parti geri alındı')
            ->body($sonuc['dosya'].' dosya silindi.')
            ->success()
            ->send();
    }
}
