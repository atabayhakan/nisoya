<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Models\City;
use App\Models\Country;
use App\Services\Kahya\Eylem\Eylem;
use Closure;
use Illuminate\Validation\Validator;

/**
 * Var olan bir ülkeye şehir ekler.
 *
 * ŞEHİR ÜLKEYE BAĞLIDIR, İLANA DEĞİL. Şehir listesi yalnızca ilan verirken ve
 * ararken açılan bir seçenek kümesidir; buraya bir satır eklemek hiçbir ilanı
 * değiştirmez, hiçbir kullanıcıya bir şey göstermez.
 *
 * Yeni şehir AKTİF eklenir — istenen zaten sitede seçilebilir olması. `UlkeEkle`
 * ile aynı gerekçe: pasif eklemek "yaptım ama işe yaramıyor" demek olurdu.
 */
class SehirEkle extends Eylem
{
    public function ad(): string
    {
        return 'sehir-ekle';
    }

    public function baslik(): string
    {
        return 'Şehir ekle';
    }

    public function aciklama(): string
    {
        return 'Sitede ZATEN VAR OLAN bir ülkenin şehir listesine yeni bir şehir ekler; '
            .'ilan verirken ve arama yaparken seçilebilir hâle gelir. Ülke listede yoksa önce '
            .'ulke-ekle kullanılmalı — bu eylem ülke açmaz. Aynı ülkede aynı adda şehir zaten '
            .'varsa bu eylemi kullanma. Hiçbir ilanı bu şehre taşımaz, yalnızca listeye ekler.';
    }

    public function sema(): array
    {
        return [
            'ulke_kodu' => 'Şehrin ekleneceği ülkenin iki harfli ISO kodu, büyük harf. Almanya için DE, Hollanda için NL.',
            'ad' => 'Şehrin TÜRKÇE adı. Ör. "Münih", "Lahey", "Köln" — "Munich" değil.',
        ];
    }

    public function kurallar(): array
    {
        return [
            // `exists` şart: yapay zekâ olmayan bir ülkeye şehir ekletmeye
            // çalışabilir. O satır eklenirse hiçbir yerde görünmez, hiçbir
            // hata da vermez — sessizce kaybolan veri en kötüsüdür.
            'ulke_kodu' => ['required', 'string', 'size:2', 'alpha', 'exists:countries,code'],
            'ad' => [
                'required',
                'string',
                'max:100',
                /*
                 * BENZERSİZLİK TEK SÜTUNDA DEĞİL, ÜLKE+AD BİLEŞKESİNDE.
                 *
                 * `unique:cities,name` yanlış olurdu: aynı ad farklı ülkelerde
                 * meşrudur (Almanya'da da ABD'de de Frankfurt vardır). Kısıtın
                 * diğer yarısı olan ülke kodu `kurallar()` çağrılırken henüz
                 * bilinmediği için kural, değerleri ancak doğrulama anında
                 * görebilen kapanış biçiminde yazıldı.
                 *
                 * Karşılaştırma iki tarafta da LOWER ile yapılıyor: yapay zekâ
                 * var olan bir şehri "münih" diye yeniden eklemeye çalışacak ve
                 * bunu yakalayacak tek yer burası — `cities` tablosunda bileşke
                 * bir unique kısıt YOK.
                 */
                function (string $attribute, mixed $value, Closure $fail, Validator $validator): void {
                    $kod = strtoupper((string) $validator->getValue('ulke_kodu'));
                    $ad = trim((string) $value);

                    if ($kod === '' || $ad === '') {
                        return;
                    }

                    $varOlan = City::query()
                        ->where('country_code', $kod)
                        ->whereRaw('LOWER(name) = LOWER(?)', [$ad])
                        ->value('name');

                    if ($varOlan !== null) {
                        $fail("{$kod} ülkesinde \"{$varOlan}\" şehri zaten var.");
                    }
                },
            ],
        ];
    }

    public function risk(): EylemRiski
    {
        // Tek satır ekler, kimseye bildirim gitmez, silmek tek tıktır.
        return EylemRiski::Dusuk;
    }

    public function onizleme(array $p): string
    {
        $kod = strtoupper((string) $p['ulke_kodu']);

        // Ülkenin TÜRKÇE adı da yazılıyor: yapay zekânın seçtiği kod yanlışsa
        // ("Danimarka (DK)" derken sahip Almanya demişti) bunu yakalayabileceği
        // tek an bu cümledir.
        $ulkeAdi = Country::query()->where('code', $kod)->value('name_tr') ?? 'bilinmeyen ülke';
        $mevcut = City::query()->where('country_code', $kod)->count();

        // Sayılara ek yapıştırılmıyor ("3'den" yazımı çoğu sayıda yanlış düşer);
        // cümle, eki sayıya değil sözcüğe yükleyecek biçimde kuruldu.
        return "{$ulkeAdi} ({$kod}) ülkesine \"{$p['ad']}\" şehri AKTİF olarak eklenecek — ".
            "bu ülkede şu an {$mevcut} şehir var, eklemeyle ".($mevcut + 1).
            ' olur. Ekledikten sonra ilan verirken ve aramada seçilebilir olur.';
    }

    public function uygula(array $p): array
    {
        $kod = strtoupper((string) $p['ulke_kodu']);
        $ad = trim((string) $p['ad']);

        $sehir = City::create([
            'country_code' => $kod,
            'name' => $ad,
            'is_active' => true,
            // Sıralama ÜLKE İÇİNDE anlamlı: her ülkenin kendi listesi var,
            // tablo genelindeki en büyük değeri almak o listeyi bozardı.
            'sort_order' => (int) City::query()->where('country_code', $kod)->max('sort_order') + 1,
        ]);

        return [
            'sonuc' => "{$ad} şehri {$kod} ülkesine eklendi.",
            'geri_alma' => ['id' => $sehir->id, 'ad' => $ad, 'ulke_kodu' => $kod],
        ];
    }

    public function geriAl(array $iz): string
    {
        $sehir = City::query()->find($iz['id'] ?? 0);

        if ($sehir === null) {
            return 'Şehir zaten yok.';
        }

        /*
         * BURADA "İLAN VAR MI" DİYE BAKMAYA GEREK YOK.
         *
         * Ülke eyleminde satırı silmek ilanların ülke bağını koparıyordu; şehirde
         * öyle bir bağ YOK: `listings.city` düz bir METİN sütunu, `cities.id`'ye
         * işaret etmiyor (yabancı anahtar da yok). Şehir satırını silmek, o metni
         * taşıyan ilanlara dokunmaz — ilan "Münih" yazmaya devam eder, yalnızca
         * yeni ilanlarda açılır listede çıkmaz.
         *
         * Yani buradaki geri alma gerçekten eski hâle döndürür: eklenen satır
         * gider, geriye iz kalmaz.
         */
        $ad = $sehir->name;
        $kod = $sehir->country_code;
        $sehir->delete();

        return "{$ad} ({$kod}) şehir listesinden kaldırıldı; bu adı taşıyan ilanlar etkilenmedi.";
    }

    public function ornekler(): array
    {
        return [
            'Almanya\'ya Münih şehrini ekle',
            'şehirlerde Rotterdam yok, Hollanda\'ya ekler misin',
            'ABD şehirlerine Chicago ekle',
        ];
    }
}
