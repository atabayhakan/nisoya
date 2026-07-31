<?php

namespace App\Filament\Widgets;

use App\Models\OutreachTarget;
use App\Support\Growth\KesifIlerlemesi;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * Keşif Havuzu'nun ÜST alanında canlı ilerleme — "Yeni keşif çalıştır"
 * tetiklendiğinde kuyruklanan iş sayısı kadar gösterge belirir, her iş
 * bitince biri tamamlanır, bulunan işletmeler adlarıyla bir kez belirip
 * kaybolur. Sadece {@see \App\Filament\Resources\OutreachTargets\Pages\ListOutreachTargets}
 * başlığında kayıtlı — durumu {@see KesifIlerlemesi} üzerinden (Cache) okur,
 * o sayfanın Livewire state'iyle DOĞRUDAN bağlı değildir.
 */
class KesifIlerlemeWidget extends Widget
{
    protected static ?int $sort = -1;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.kesif-ilerlemesi';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * @return array{lot: string, toplam: int, baslangic: string, tamamlanan: int}|null
     */
    public function getDurum(): ?array
    {
        $userId = auth()->id();

        return $userId ? KesifIlerlemesi::aktifDurum((int) $userId) : null;
    }

    /**
     * Parti bitince SONUÇ TABLOSU (ayrı Livewire bileşeni, bu widget'ın
     * pollamasından habersiz) kendini yenilesin diye bir kerelik olay
     * yayınlar — aksi hâlde sahip yeni bulunan işletmeleri görmek için
     * sayfayı elle yenilemek zorunda kalıyordu (canlıda fark edildi,
     * 2026-07-31). `bildirildiMi` sayesinde tamamlandıktan sonraki her
     * pollamada TEKRAR tetiklenmez — tablo tek sefer, temiz bir şekilde
     * yenilenir.
     */
    public function kontrolVeBildir(): void
    {
        $durum = $this->getDurum();

        if ($durum === null || $durum['tamamlanan'] < $durum['toplam']) {
            return;
        }

        if (KesifIlerlemesi::bildirildiMi($durum['lot'])) {
            return;
        }

        KesifIlerlemesi::bildirildi($durum['lot']);
        $this->dispatch('kesif-tamamlandi');
    }

    /** @return Collection<int, array{id: int, name: string}> */
    public function getSonBulunanlar(): Collection
    {
        $durum = $this->getDurum();

        if ($durum === null) {
            return collect();
        }

        return OutreachTarget::query()
            ->where('created_at', '>=', $durum['baslangic'])
            ->latest('id')
            ->limit(12)
            ->get(['id', 'name'])
            ->map(fn (OutreachTarget $t): array => ['id' => $t->id, 'name' => $t->name]);
    }
}
