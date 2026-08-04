<?php

namespace App\Reports;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\Page;
use App\Models\TemsilcilikIslemi;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * "Nisoya Genel Bakış" belgesinin TEK veri kaynağı.
 *
 * ---------------------------------------------------------------------------
 * KURAL: BELGEDE ELLE YAZILMIŞ TEK RAKAM BULUNMAZ
 *
 * Şablonda sabit bir sayı olsaydı ikinci ayda yalan olurdu — bu projede daha
 * önce tam olarak bu yaşandı (hero'daki sahte grafik, iskelet rehber
 * içerikleri). Her sayı buradan, canlı sorgudan gelir.
 *
 * ---------------------------------------------------------------------------
 * DEMO SAYILMAZ — TEK TANIM
 *
 * `is_demo = false` süzgeci Kâhya teşhisiyle, ana sayfadaki kanıt şeridiyle ve
 * süreç şeridiyle AYNI kuraldır. İki ayrı "gerçek envanter" tanımı olursa
 * hangisinin doğru olduğu tartışılır; tek doğrulanan şişik rakam belgeyi
 * çöpe atar.
 *
 * ---------------------------------------------------------------------------
 * VERİ KESİM DAMGASI
 *
 * `kesimTarihi()` belgenin her sayfasına basılır. Bir yatırımcı belgeyi üç ay
 * sonra okuduğunda hangi güne ait olduğunu bilmeli.
 */
class NisoyaDosyasi
{
    public function kesimTarihi(): Carbon
    {
        return now();
    }

    /**
     * Gerçek envanter — demo hariç.
     *
     * `satici` ayrı veriliyor çünkü "12 ilan" iyi görünür, "12 ilan / 1 satıcı"
     * gerçeği söyler. Belge ikincisini yazar.
     *
     * @return array{ilan: int, satici: int, sehir: int, ulke: int}
     */
    public function envanter(): array
    {
        $temel = Listing::query()->where('is_demo', false)->where('status', ListingStatus::Aktif->value);

        return [
            'ilan' => (clone $temel)->count(),
            'satici' => (clone $temel)->distinct()->count('user_id'),
            // Şehir serbest metin: 'Berlin'/'berlin'/'Berlin ' üç şehir sayılmasın.
            'sehir' => (clone $temel)->whereNotNull('city')->where('city', '!=', '')
                ->distinct()->count(DB::raw('LOWER(TRIM(city))')),
            'ulke' => (clone $temel)->distinct()->count('country_code'),
        ];
    }

    /**
     * Üye tabanı — demo üyeler (@demo.invalid) hariç.
     *
     * @return array{toplam: int, dogrulanmis: int}
     */
    public function uyeler(): array
    {
        $temel = User::query()->where('email', 'not like', '%@demo.invalid');

        return [
            'toplam' => (clone $temel)->count(),
            'dogrulanmis' => (clone $temel)->whereNotNull('email_verified_at')->count(),
        ];
    }

    /**
     * Ürünün yayında olan içerik varlıkları.
     *
     * Bunlar envanterden farklı bir şeyi kanıtlar: pazaryeri boşken bile
     * ürünün ürettiği DEĞER. Ülke rehberi bugün bunun en somut örneği.
     *
     * @return array{rehber_icerik: int, rehber_ulke: int, sayfa: int}
     */
    public function icerik(): array
    {
        $rehberAcik = Modules::enabled('rehber');

        return [
            'rehber_icerik' => $rehberAcik ? TemsilcilikIslemi::query()->yayinda()->count() : 0,
            'rehber_ulke' => $rehberAcik
                ? TemsilcilikIslemi::query()->yayinda()
                    ->join('temsilcilikler', 'temsilcilikler.id', '=', 'temsilcilik_islemleri.temsilcilik_id')
                    ->distinct()->count('temsilcilikler.country_code')
                : 0,
            'sayfa' => Page::query()->published()->count(),
        ];
    }

    /** Açık modüller — ürünün kapsamını sayıyla değil ADLA anlatır. */
    public function moduller(): array
    {
        return array_keys(array_filter(Modules::all()));
    }

    /**
     * Belgede gösterilecek her sayının tek seferde toplanmış hâli.
     *
     * Anlık görüntü olarak saklanmaya da uygun: geçen ay hangi rakamın
     * verildiği izlenebilsin.
     *
     * @return array<string, mixed>
     */
    public function anlikGoruntu(): array
    {
        return [
            'kesim' => $this->kesimTarihi()->toDateTimeString(),
            'envanter' => $this->envanter(),
            'uyeler' => $this->uyeler(),
            'icerik' => $this->icerik(),
            'moduller' => $this->moduller(),
        ];
    }
}
