<?php

namespace App\Filament\Pages;

use App\Models\KahyaHarcamasi;
use App\Services\Kahya\Dis\IsletmeKesfi;
use App\Services\Kahya\Dis\WebAramasi;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Kâhya Harcamaları — "bu ay ne harcadık" ekranı (F3, tasarım §3 "bütçe
 * görünürlüğü").
 *
 * DOLAR GÖSTERMİYORUZ, BİLE BİLE: model fiyatları oynak; uydurma bir kur
 * tablosuyla "tahmini $" basmak yanlış güven verir. Token ve çağrı sayısı
 * sağlayıcı faturasıyla her zaman karşılaştırılabilir ham gerçektir; kesin
 * dolar sağlayıcının kendi panosunda.
 */
class KahyaHarcamalari extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Kâhya & Yapay Zekâ';

    protected static ?string $navigationLabel = 'Kâhya Harcamaları';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.kahya-harcamalari';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getTitle(): string
    {
        return 'Kâhya Harcamaları';
    }

    /**
     * Bu ayın LLM kullanımı, model bazında (satırlar aggregate takma
     * adlarını taşır: adet/girdi/cikti).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, KahyaHarcamasi>
     */
    public function llmSatirlari(): \Illuminate\Database\Eloquent\Collection
    {
        return KahyaHarcamasi::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('kaynak', 'sohbet')
            ->selectRaw('model, COUNT(*) as adet, COALESCE(SUM(girdi_token),0) as girdi, COALESCE(SUM(cikti_token),0) as cikti')
            ->groupBy('model')
            ->orderByDesc('girdi')
            ->get();
    }

    /**
     * Dış araç kullanımları + limitleri.
     *
     * @return list<array{etiket: string, kullanim: int, limit: int, sonlar: Collection<int, KahyaHarcamasi>}>
     */
    public function aracSatirlari(): array
    {
        $arama = app(WebAramasi::class);
        $kesif = app(IsletmeKesfi::class);

        $sonlar = fn (string $kaynak): Collection => KahyaHarcamasi::query()
            ->where('kaynak', $kaynak)
            ->latest('id')
            ->limit(5)
            ->get();

        return [
            [
                'etiket' => 'Web araması (web-ara)',
                'kullanim' => $arama->buAykiKullanim(),
                'limit' => $arama->aylikLimit(),
                'sonlar' => $sonlar(WebAramasi::KAYNAK),
            ],
            [
                'etiket' => 'İşletme keşfi (isletme-kesfet)',
                'kullanim' => $kesif->buAykiKullanim(),
                'limit' => $kesif->aylikLimit(),
                'sonlar' => $sonlar(IsletmeKesfi::KAYNAK),
            ],
        ];
    }
}
