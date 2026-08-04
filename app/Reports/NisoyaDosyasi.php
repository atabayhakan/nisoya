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

    /**
     * KUZEY YILDIZI: haftalık karşılıklı ilk temas.
     *
     * Neden bu metrik: bir pazaryerinde tek anlamlı sinyal, iki yabancının
     * gerçekten konuşmaya başlaması. Kayıtlı üye, sayfa görüntüleme, ilan
     * sayısı — hepsi vanity; hiçbiri "bu ürün işe yarıyor" demiyor.
     *
     * "Karşılıklı" ŞART: tek taraflı mesaj temas değildir. Aynı tanım
     * değerlendirme kapısında da kullanılıyor (Conversation::mutualExistsBetween)
     * — iki ayrı "temas" tanımı olmamalı.
     *
     * @return list<array{hafta: string, adet: int}>
     */
    public function kuzeyYildizi(int $hafta = 8): array
    {
        $baslangic = now()->startOfWeek()->subWeeks($hafta - 1);

        // Karşılıklı konuşmalar: her iki tarafın da en az bir mesajı olan.
        $karsilikli = DB::table('messages')
            ->select('conversation_id', DB::raw('COUNT(DISTINCT sender_id) as taraf'), DB::raw('MIN(created_at) as ilk'))
            ->groupBy('conversation_id')
            ->havingRaw('COUNT(DISTINCT sender_id) >= 2');

        $satirlar = DB::query()
            ->fromSub($karsilikli, 't')
            ->where('ilk', '>=', $baslangic)
            ->get();

        // Haftalara PHP'de bölünüyor: SQLite ile MySQL'in hafta fonksiyonları
        // farklı (WEEKOFYEAR vs strftime) ve testler SQLite'ta koşuyor.
        $sayimlar = [];

        foreach ($satirlar as $satir) {
            $anahtar = Carbon::parse($satir->ilk)->startOfWeek()->toDateString();
            $sayimlar[$anahtar] = ($sayimlar[$anahtar] ?? 0) + 1;
        }

        $sonuc = [];

        for ($i = 0; $i < $hafta; $i++) {
            $h = $baslangic->copy()->addWeeks($i);
            $sonuc[] = ['hafta' => $h->toDateString(), 'adet' => $sayimlar[$h->toDateString()] ?? 0];
        }

        return $sonuc;
    }

    /**
     * Huni: konuşma → karşılıklı konuşma → tamamlanan anlaşma.
     *
     * Yatırımcıya "kaç ilan var"dan daha çok şey söyler: ürünün hangi
     * adımında kopuyor.
     *
     * @return array{konusma: int, karsilikli: int, anlasma: int}
     */
    public function huni(): array
    {
        $karsilikli = DB::table('messages')
            ->select('conversation_id')
            ->groupBy('conversation_id')
            ->havingRaw('COUNT(DISTINCT sender_id) >= 2');

        return [
            'konusma' => DB::table('conversations')->count(),
            'karsilikli' => DB::query()->fromSub($karsilikli, 't')->count(),
            'anlasma' => DB::table('deals')->where('status', 'tamamlandi')->count(),
        ];
    }

    /**
     * Sermaye verimliliği: ürünün canlıda olduğu ay sayısı.
     *
     * Yatırımcıya anlatılacak şey özellik sayısı DEĞİL: "tek kişi, geliştirici
     * değil, N ayda bunu kurdu". Özellikler ancak bu çerçevede anlamlı.
     *
     * En eski üye kaydından türüyor — elle girilen bir tarih bayatlar.
     */
    public function aySayisi(): int
    {
        $ilk = User::query()->min('created_at');

        return $ilk === null ? 0 : (int) max(1, Carbon::parse($ilk)->diffInMonths(now()));
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
