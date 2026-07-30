<?php

namespace App\Filament\Pages;

use App\Livewire\Concerns\KahyaSohbetiYurutur;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
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
 * DAVRANIŞ TRAIT'TE
 *
 * Gönderme/onay/geri alma mantığı {@see KahyaSohbetiYurutur} içinde durur ve
 * panelin her ekranındaki balonla (KahyaBalonu) paylaşılır. Onay ve geri alma
 * düğmeleri sayfadaki SON eyleme değil, TIKLANAN mesajın eylemine bağlıdır:
 * arka arkaya iki iş istendiğinde yanlış olanı onaylamak diye bir şey
 * olamamalı.
 */
class KahyaSohbet extends Page
{
    use KahyaSohbetiYurutur;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Kâhya & Yapay Zekâ';

    protected static ?string $navigationLabel = 'Kâhya ile Konuş';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.kahya-sohbet';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTitle(): string
    {
        return config('kahya.isim', 'Kâhya').' ile Konuş';
    }

    public function getSubheading(): ?string
    {
        return 'Sorabilir ya da iş isteyebilirsin — yapabildiği işler sınırlı bir listeden gelir ve hepsi geri alınabilir.';
    }
}
