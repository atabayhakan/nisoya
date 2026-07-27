<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Support\HomeSections;
use App\Support\Settings;
use App\Support\Tema;
use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Anasayfa Bölümleri (Faz 2 · G5) — anasayfada hangi içerik bölümünün
 * görüneceğini ve HANGİ SIRAYLA görüneceğini panelden yönet. Kapatılan bölüm
 * anasayfada hiç render edilmez; içerik/veri silinmez (tekrar açınca geri
 * gelir — bkz. App\Support\HomeSections).
 *
 * Sıra TEMA BAŞINA ayrıdır: klasik ve Vitrin anasayfalarının bugünkü sırası
 * zaten farklı, ortak tek ayar hangisine yazılırsa diğerinin görünümünü
 * bozardı. Sayfa hangi temanın sırasını düzenlediğini açıkça gösterir.
 */
class AnasayfaBolumleri extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWindow;

    protected static string|UnitEnum|null $navigationGroup = 'İçerik & Tasarım (CMS)';

    protected static ?string $navigationLabel = 'Anasayfa Bölümleri';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.anasayfa-bolumleri';

    public ?array $data = [];

    /** Düzenlenmekte olan temanın bölüm sırası. */
    public array $sira = [];

    /** Hangi temanın sırası düzenleniyor. */
    public string $siraTema = 'klasik';

    /** Sitede şu an açık olan tema — panelde işaretlenir. */
    public string $aktifTema = 'klasik';

    public function getTitle(): string
    {
        return 'Anasayfa Bölümleri';
    }

    public function mount(): void
    {
        $state = [];
        foreach (array_keys(HomeSections::SECTIONS) as $key) {
            $state[$key] = HomeSections::visible($key);
        }

        $this->form->fill($state);

        // Sahibin ilk gördüğü sıra, sitede AÇIK OLAN temanınki olsun —
        // düzenlediği şeyin karşılığını sitede hemen görebilsin.
        $this->aktifTema = Tema::aktif();
        $this->siraTema = in_array($this->aktifTema, HomeSections::temalar(), true)
            ? $this->aktifTema
            : 'klasik';
        $this->sira = HomeSections::sirali($this->siraTema);
    }

    public function temaSec(string $tema): void
    {
        if (! in_array($tema, HomeSections::temalar(), true)) {
            return;
        }

        $this->siraTema = $tema;
        $this->sira = HomeSections::sirali($tema);
    }

    public function yukari(int $i): void
    {
        $this->tasi($i, $i - 1);
    }

    public function asagi(int $i): void
    {
        $this->tasi($i, $i + 1);
    }

    /** Bir bölümü listede başka bir konuma taşır (sürükleme de bunu çağırır). */
    public function tasi(int $kaynak, int $hedef): void
    {
        $son = count($this->sira) - 1;
        if ($kaynak < 0 || $kaynak > $son || $hedef < 0 || $hedef > $son || $kaynak === $hedef) {
            return;
        }

        $tasinan = $this->sira[$kaynak];
        array_splice($this->sira, $kaynak, 1);
        array_splice($this->sira, $hedef, 0, [$tasinan]);
    }

    public function siraVarsayilana(): void
    {
        $this->sira = HomeSections::VARSAYILAN_SIRA[$this->siraTema] ?? [];
    }

    public function siraKaydet(): void
    {
        HomeSections::siraKaydet($this->siraTema, $this->sira);

        // Kaydedilen sırayı geri okuyup göster: bozuk/eksik girdi düzeltilmişse
        // sahip panelde gerçekte ne kaydedildiğini görür.
        $this->sira = HomeSections::sirali($this->siraTema);

        Notification::make()
            ->title(($this->siraTema === 'vitrin' ? 'Vitrin' : 'Klasik').' bölüm sırası kaydedildi')
            ->body($this->siraTema === $this->aktifTema
                ? 'Değişiklik canlı sitede anında geçerli.'
                : 'Bu tema şu an açık değil; sıraya o temaya geçince görürsün.')
            ->success()
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        $toggles = [];
        foreach (HomeSections::SECTIONS as $key => $label) {
            $toggles[] = Toggle::make($key)->label($label);
        }

        return $schema
            ->components([
                Section::make('Görünen bölümler')
                    ->description('Kapattığın bölüm anasayfada görünmez olur; verilerin silinmez. Hero (üst alan), Nisoya Nabzı ve reklam alanları buradan bağımsızdır (kendi ayarları var).')
                    ->columns(2)
                    ->schema($toggles),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $values = [];
        foreach (array_keys(HomeSections::SECTIONS) as $key) {
            $values["home.section.{$key}"] = ! empty($state[$key]) ? '1' : '0';
        }

        Settings::setMany($values);

        Notification::make()
            ->title('Anasayfa bölümleri güncellendi')
            ->body('Değişiklik canlı sitede anında geçerli.')
            ->success()
            ->send();
    }
}
