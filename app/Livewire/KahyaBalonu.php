<?php

namespace App\Livewire;

use App\Livewire\Concerns\KahyaSohbetiYurutur;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Kâhya balonu — panelin her ekranında sağ altta duran sohbet penceresi.
 *
 * Sahibin en sık sorusu "X nerede?" ve bu soru tam da X'i ararken, yani
 * herhangi bir ekranda doğar. Cevap için "Kâhya ile Konuş" sayfasına gitmek,
 * sorunun kendisinden pahalı — balon bu yüzden var. Aynı sohbeti gösterir,
 * aynı defteri yazar; sayfa ile arasında hiçbir davranış farkı yoktur
 * (bkz. KahyaSohbetiYurutur).
 *
 * KAPALIYKEN BEDAVA: karşılama ve mesaj listesi ancak balon AÇILINCA
 * hesaplanır. Aksi hâlde panelin her sayfa yüklemesi, kimsenin bakmadığı bir
 * sohbet için kuyruk/teşhis sorguları koştururdu.
 *
 * Balonu panele yerleştiren yer: AdminPanelProvider'daki BODY_END render
 * hook'u. Orada da admin kontrolü var; buradaki mount kontrolü, bileşeni
 * doğrudan mount etmeye çalışan istekler için ikinci kemer.
 */
class KahyaBalonu extends Component
{
    use KahyaSohbetiYurutur;

    public bool $acik = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin() ?? false, 403);
    }

    public function ac(): void
    {
        $this->acik = true;
        $this->dispatch('kahya-kaydir');
    }

    public function kapat(): void
    {
        $this->acik = false;
    }

    public function render(): View
    {
        return view('livewire.kahya-balonu');
    }
}
