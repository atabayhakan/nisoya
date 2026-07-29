<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Listing;
use App\Services\Kahya\Eylem\Eylem;

/**
 * Para birimi listesine yeni bir para birimi ekler.
 *
 * Yeni para birimi AKTİF eklenir — istenen şey zaten ilan verirken ve fiyat
 * gösterirken seçilebilir olması. Pasif eklemek işi yarım bırakırdı; ne olduğu
 * önizlemede açıkça söyleniyor ve tek tıkla geri alınıyor.
 *
 * BU EYLEM KUR AYARLAMAZ. Sitede para birimleri arası çeviri yok; her ilan
 * kendi para biriminde durur. Yeni bir para birimi eklemek yalnızca "bu
 * kodla fiyat girilebilsin" demektir, "şu kadar euro eder" demek değil.
 */
class ParaBirimiEkle extends Eylem
{
    public function ad(): string
    {
        return 'para-birimi-ekle';
    }

    public function baslik(): string
    {
        return 'Para birimi ekle';
    }

    public function aciklama(): string
    {
        return 'Sitenin para birimi listesine yeni bir para birimi ekler; ilan verirken ve '
            .'fiyat gösterirken seçilebilir hâle gelir. Para birimi ZATEN VARSA bu eylemi '
            .'kullanma. Kur/çeviri oranı ayarlamaz, var olan ilanların fiyatlarını '
            .'dönüştürmez ve hiçbir ülkenin varsayılan para birimini değiştirmez.';
    }

    public function sema(): array
    {
        return [
            'kod' => 'Üç harfli ISO para birimi kodu, büyük harf. Zloti için PLN, İsviçre frangı için CHF.',
            'ad' => 'Para biriminin TÜRKÇE adı. Ör. "Polonya zlotisi", "İsviçre frangı".',
            'sembol' => 'İsteğe bağlı sembol. Ör. zł, ₣, kr. Boş bırakılırsa kodun kendisi kullanılır.',
        ];
    }

    public function kurallar(): array
    {
        return [
            // `unique` şart: yapay zekâ zaten var olan bir para birimini yeniden
            // eklemeye çalışabilir ve bunu veritabanı değil, burası reddetmeli.
            'kod' => ['required', 'string', 'size:3', 'alpha', 'unique:currencies,code'],
            'ad' => ['required', 'string', 'max:100'],
            // Sütun 8 karakter; uzun bir "sembol" gelirse kaydetmeden önce dursun.
            'sembol' => ['nullable', 'string', 'max:8'],
        ];
    }

    public function risk(): EylemRiski
    {
        // Tek satır ekler, kimseye bildirim gitmez, var olan fiyatlara dokunmaz.
        return EylemRiski::Dusuk;
    }

    public function onizleme(array $p): string
    {
        $kod = strtoupper((string) $p['kod']);
        $sembol = $this->sembol($p, $kod);

        $ek = $sembol === $kod
            ? ", sembol verilmediği için kodun kendisi ({$kod}) gösterilecek"
            : ", sembolü {$sembol}";

        return "{$p['ad']} ({$kod}) para birimi listesine AKTİF olarak eklenecek{$ek}. ".
            'Ekledikten sonra ilan verirken seçilebilir olur; mevcut ilanların fiyatları değişmez.';
    }

    public function uygula(array $p): array
    {
        $kod = strtoupper((string) $p['kod']);

        Currency::create([
            'code' => $kod,
            'name' => $p['ad'],
            'symbol' => $this->sembol($p, $kod),
            'is_active' => true,
            // Listenin sonuna: mevcut sıralama sahibin kurduğu bir düzen
            // olabilir, araya girmek onu bozar.
            'sort_order' => (int) Currency::query()->max('sort_order') + 1,
        ]);

        // Tabloda `id` yok; birincil anahtar kodun kendisi, iz de onu taşır.
        return [
            'sonuc' => "{$p['ad']} ({$kod}) eklendi.",
            'geri_alma' => ['kod' => $kod, 'ad' => $p['ad']],
        ];
    }

    public function geriAl(array $iz): string
    {
        $paraBirimi = Currency::query()->find($iz['kod'] ?? '');

        if ($paraBirimi === null) {
            return 'Para birimi zaten yok.';
        }

        /*
         * KULLANILMIŞSA SİLİNMEZ, PASİFE ÇEKİLİR.
         *
         * `listings.currency` ve `countries.default_currency` birer kod
         * kolonu — veritabanı seviyesinde yabancı anahtar YOK, yani satırı
         * silmek hata vermez, sessizce öksüz kod bırakır: fiyatı "PLN" yazan
         * ama artık böyle bir para birimi olmayan ilanlar. "Geri aldım"
         * derken veri bozmak, geri almamaktan kötüdür. O yüzden geri alma
         * burada pasife çekmeye dönüşür ve bunu AÇIKÇA söyler.
         */
        $ilanVar = Listing::query()->where('currency', $paraBirimi->code)->exists();
        $ulkeVar = Country::query()->where('default_currency', $paraBirimi->code)->exists();

        if ($ilanVar || $ulkeVar) {
            $paraBirimi->update(['is_active' => false]);

            $neden = $ilanVar
                ? 'bu para biriminde ilan var'
                : 'bir ülkenin varsayılan para birimi olarak kullanılıyor';

            return "{$paraBirimi->name} silinmedi çünkü {$neden} — pasife çekildi, yeni ilanlarda seçilemiyor.";
        }

        $ad = $paraBirimi->name;
        $paraBirimi->delete();

        return "{$ad} para birimi listesinden kaldırıldı.";
    }

    public function ornekler(): array
    {
        return [
            'para birimlerine Polonya zlotisi ekle',
            'İsviçre frangını ekler misin, kodu CHF',
            'para birimi listesinde İsveç kronu yok, ekle',
        ];
    }

    /**
     * Sembol sütunu boş geçilemiyor; sembol verilmediyse kodun kendisi yazılır.
     * "PLN 250" hem doğru hem okunur, boş sembol ise fiyatı sayı yığınına
     * çevirirdi.
     *
     * @param  array<string, mixed>  $p
     */
    private function sembol(array $p, string $kod): string
    {
        $sembol = trim((string) ($p['sembol'] ?? ''));

        return $sembol !== '' ? $sembol : $kod;
    }
}
