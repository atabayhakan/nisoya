<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Models\Country;
use App\Models\Listing;
use App\Services\Kahya\Eylem\Eylem;

/**
 * Ülke listesine yeni bir ülke ekler.
 *
 * SAHİBİN VERDİĞİ ÖRNEK BU: "Kâhya ülkeler kısmına Japonya ekle."
 *
 * Yeni ülke AKTİF eklenir — istenen şey zaten sitede seçilebilir olması.
 * Pasif eklemek "teknik olarak güvenli ama işe yaramaz" cevabı olurdu; onun
 * yerine ne olduğu önizlemede açıkça söyleniyor ve tek tıkla geri alınıyor.
 */
class UlkeEkle extends Eylem
{
    public function ad(): string
    {
        return 'ulke-ekle';
    }

    public function baslik(): string
    {
        return 'Ülke ekle';
    }

    public function aciklama(): string
    {
        return 'Sitenin ülke listesine yeni bir ülke ekler; ilan verirken ve arama yaparken '
            .'seçilebilir hâle gelir. Ülke ZATEN VARSA bu eylemi kullanma — durumunu '
            .'değiştirmek için ulke-durum-degistir var. Şehir eklemez (onun için sehir-ekle).';
    }

    public function sema(): array
    {
        return [
            'kod' => 'İki harfli ISO ülke kodu, büyük harf. Japonya için JP, Kanada için CA.',
            'ad' => 'Ülkenin TÜRKÇE adı. Ör. "Japonya", "Kanada", "Avustralya".',
            'emoji' => 'İsteğe bağlı bayrak emojisi. Ör. 🇯🇵',
            'para_birimi' => 'İsteğe bağlı, üç harfli varsayılan para birimi kodu. Ör. JPY.',
        ];
    }

    public function kurallar(): array
    {
        return [
            // `unique` şart: yapay zekâ var olan bir ülkeyi yeniden eklemeye
            // çalışabilir ve bunu veritabanı değil, burası reddetmeli.
            'kod' => ['required', 'string', 'size:2', 'alpha', 'unique:countries,code'],
            'ad' => ['required', 'string', 'max:100'],
            'emoji' => ['nullable', 'string', 'max:16'],
            'para_birimi' => ['nullable', 'string', 'size:3', 'alpha', 'exists:currencies,code'],
        ];
    }

    public function risk(): EylemRiski
    {
        // Tek satır ekler, kimseye bildirim gitmez, silmek tek tıktır.
        return EylemRiski::Dusuk;
    }

    public function onizleme(array $p): string
    {
        $kod = strtoupper((string) $p['kod']);
        $ek = ! empty($p['para_birimi']) ? ', varsayılan para birimi '.strtoupper((string) $p['para_birimi']) : '';

        return "{$p['ad']} ({$kod}) ülke listesine AKTİF olarak eklenecek{$ek}. ".
            'Ekledikten sonra ilan verirken ve aramada seçilebilir olur.';
    }

    public function uygula(array $p): array
    {
        $kod = strtoupper((string) $p['kod']);

        $ulke = Country::create([
            'code' => $kod,
            'name_tr' => $p['ad'],
            'emoji' => $p['emoji'] ?? null,
            'default_currency' => ! empty($p['para_birimi']) ? strtoupper((string) $p['para_birimi']) : null,
            'is_active' => true,
            // Listenin sonuna: mevcut sıralama sahibin kurduğu bir düzen
            // olabilir, araya girmek onu bozar.
            'sort_order' => (int) Country::query()->max('sort_order') + 1,
        ]);

        return [
            'sonuc' => "{$p['ad']} ({$kod}) eklendi.",
            // İz KOD taşır, id değil: `countries` tablosunun birincil anahtarı
            // `code`'dur ve `id` diye bir kolon YOKTUR. `$ulke->id` sessizce
            // null döner ve geri alma "ülke zaten yok" sanırdı — testte yakalandı.
            'geri_alma' => ['kod' => $kod, 'ad' => $p['ad']],
        ];
    }

    public function geriAl(array $iz): string
    {
        $ulke = Country::query()->where('code', (string) ($iz['kod'] ?? ''))->first();

        if ($ulke === null) {
            return 'Ülke zaten yok.';
        }

        /*
         * KULLANILMIŞSA SİLİNMEZ, PASİFE ÇEKİLİR.
         *
         * Bu ülkeye ilan girilmişse satırı silmek o ilanların ülke bağını
         * koparır. "Geri aldım" derken veri bozmak, geri almamaktan kötüdür.
         * O yüzden geri alma burada pasife çekmeye dönüşür ve bunu AÇIKÇA
         * söyler.
         */
        $ilanVar = Listing::query()->where('country_code', $ulke->code)->exists();

        if ($ilanVar) {
            $ulke->update(['is_active' => false]);

            return "{$ulke->name_tr} silinmedi çünkü bu ülkede ilan var — pasife çekildi, sitede görünmüyor.";
        }

        $ad = $ulke->name_tr;
        $ulke->delete();

        return "{$ad} ülke listesinden kaldırıldı.";
    }

    public function ornekler(): array
    {
        return [
            'ülkeler kısmına Japonya ekle',
            'Kanada\'yı ülke listesine ekle',
            'Avustralya eksik, ekler misin',
        ];
    }
}
