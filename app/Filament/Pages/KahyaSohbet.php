<?php

namespace App\Filament\Pages;

use App\Models\KahyaEylemKaydi;
use App\Models\KahyaMesaji;
use App\Services\Kahya\Eylem\EylemCalistirici;
use App\Services\Kahya\Sohbet\KahyaSohbeti;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Throwable;
use UnitEnum;

/**
 * Kâhya ile sohbet — "şunu yap dediğimde gidip yapsın" ekranı.
 *
 * ---------------------------------------------------------------------------
 * YALNIZ ADMIN
 *
 * Bu sayfadan EYLEM tetiklenir: ülke eklenir, ayar değişir. Moderatörün
 * panelde yapamadığı işleri sohbet üzerinden yapabilmesi bir yetki
 * yükseltmesi olurdu. Karşılama widget'ı moderatöre açık; bu sayfa değil.
 *
 * ---------------------------------------------------------------------------
 * ONAY VE GERİ ALMA SOHBETİN İÇİNDE
 *
 * Yüksek riskli eylem "onayına sunuyorum" der ve mesajın altında Onayla /
 * Vazgeç düğmeleri çıkar. Uygulanan eylemin altında "Geri al" durur. Ayrı bir
 * yönetim ekranına gitmek gerekmez — sahip kararını konuşmanın aktığı yerde
 * verir.
 *
 * Düğmeler sayfadaki SON eyleme değil, TIKLANAN mesajın eylemine bağlıdır:
 * arka arkaya iki iş istendiğinde yanlış olanı onaylamak diye bir şey
 * olamamalı.
 */
class KahyaSohbet extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'Kâhya ile Konuş';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.kahya-sohbet';

    public string $mesaj = '';

    /** Gönderim sırasında arayüzü kilitlemek için. */
    public bool $dusunuyor = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTitle(): string
    {
        return 'Kâhya ile Konuş';
    }

    public function getSubheading(): ?string
    {
        return 'Sorabilir ya da iş isteyebilirsin — yapabildiği işler sınırlı bir listeden gelir ve hepsi geri alınabilir.';
    }

    /** @return Collection<int, KahyaMesaji> */
    public function getMesajlar(): Collection
    {
        // Son 30 mesaj, eskiden yeniye. Sohbetin tamamı DB'de duruyor;
        // ekranda son sayfa yeter, gerisi kaydırma değil arşiv işidir.
        return KahyaMesaji::query()
            ->with('eylem')
            ->latest('id')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();
    }

    public function getKarsilama(): string
    {
        $sahip = auth()->user();

        if ($sahip === null) {
            return '';
        }

        try {
            return app(KahyaSohbeti::class)->karsila($sahip);
        } catch (Throwable $e) {
            report($e);

            return 'Merhaba.';
        }
    }

    public function gonder(): void
    {
        $metin = trim($this->mesaj);

        if ($metin === '') {
            return;
        }

        $this->mesaj = '';
        $this->dusunuyor = true;

        try {
            app(KahyaSohbeti::class)->sor($metin, auth()->user());
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('Kâhya yanıt veremedi')
                ->body(class_basename($e).' — ayrıntı sunucu kayıtlarında.')
                ->danger()
                ->send();
        } finally {
            $this->dusunuyor = false;
        }
    }

    public function eylemOnayla(int $kayitId): void
    {
        $kayit = KahyaEylemKaydi::query()->find($kayitId);

        if ($kayit === null || $kayit->durum !== KahyaEylemKaydi::DURUM_BEKLEMEDE) {
            Notification::make()->title('Bu eylem artık onay beklemiyor.')->warning()->send();

            return;
        }

        try {
            app(KahyaSohbeti::class)->onayla($kayit, auth()->user());
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('Eylem uygulanamadı')
                ->body(class_basename($e).' — ayrıntı sunucu kayıtlarında.')
                ->danger()
                ->send();
        }
    }

    public function eylemReddet(int $kayitId): void
    {
        $kayit = KahyaEylemKaydi::query()->find($kayitId);

        if ($kayit === null || $kayit->durum !== KahyaEylemKaydi::DURUM_BEKLEMEDE) {
            return;
        }

        app(EylemCalistirici::class)->reddet($kayit);

        // Reddin de sohbette izi kalmalı — sessizce kaybolan bir karar,
        // "bunu istemiştim, ne oldu?" sorusunu doğurur.
        KahyaMesaji::create([
            'rol' => KahyaMesaji::ROL_KAHYA,
            'metin' => 'Anladım, vazgeçtim: '.$kayit->onizleme,
            'kahya_eylemi_id' => $kayit->id,
            'user_id' => auth()->id(),
        ]);
    }

    public function eylemGeriAl(int $kayitId): void
    {
        $kayit = KahyaEylemKaydi::query()->find($kayitId);

        if ($kayit === null || ! $kayit->geriAlinabilirMi()) {
            Notification::make()->title('Bu eylem geri alınamıyor.')->warning()->send();

            return;
        }

        try {
            app(KahyaSohbeti::class)->geriAl($kayit, auth()->user());
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('Geri alınamadı')
                ->body(class_basename($e).' — ayrıntı sunucu kayıtlarında.')
                ->danger()
                ->send();
        }
    }
}
