<?php

namespace App\Livewire\Concerns;

use App\Models\KahyaEylemKaydi;
use App\Models\KahyaMesaji;
use App\Services\Kahya\Eylem\EylemCalistirici;
use App\Services\Kahya\Sohbet\KahyaSohbeti;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Kâhya sohbetinin Livewire davranışı — TEK kopya, İKİ yüz.
 *
 * Aynı sohbet iki yerden açılır: "Kâhya ile Konuş" sayfası ve panelin her
 * ekranındaki balon. Gönderme/onay/geri alma mantığı ikisinde de birebir aynı
 * olmak ZORUNDA — kopyalansaydı, onay akışına gelecek ilk düzeltme iki yerden
 * birinde unutulurdu ve "balondan onayladım ama olmadı" diye açıklanamayan bir
 * hata doğardı. Bu yüzden davranış burada, görünüm Blade'lerde ayrışır.
 *
 * GÜVENLİK: her yazan metot yetkiyi kendisi doğrular. Sayfanın canAccess'i ve
 * balonun mount'u ilk yüklemeyi korur ama Livewire güncellemeleri mount'tan
 * geçmez; rolü sonradan düşürülmüş bir oturum buradaki kapıya takılır.
 */
trait KahyaSohbetiYurutur
{
    public string $mesaj = '';

    /** Gönderim sırasında arayüzü kilitlemek için. */
    public bool $dusunuyor = false;

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
        abort_unless(auth()->user()?->isAdmin() ?? false, 403);

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

        // Balon gibi dar pencereler yeni mesaja kaysın (sayfa dinlemiyorsa zararsız).
        $this->dispatch('kahya-kaydir');
    }

    public function eylemOnayla(int $kayitId): void
    {
        abort_unless(auth()->user()?->isAdmin() ?? false, 403);

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
        abort_unless(auth()->user()?->isAdmin() ?? false, 403);

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
        abort_unless(auth()->user()?->isAdmin() ?? false, 403);

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
