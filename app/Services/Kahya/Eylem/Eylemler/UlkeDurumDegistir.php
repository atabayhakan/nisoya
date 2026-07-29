<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Models\Country;
use App\Models\Listing;
use App\Services\Kahya\Eylem\Eylem;

/**
 * Var olan bir ülkeyi aktif ya da pasif yapar.
 *
 * PASİF ÜLKE SİTEDE YOKTUR: arama filtrelerinde çıkmaz, ilan verme formunda
 * seçilemez. Yani bu eylem tek satır günceller ama sitenin YÜZÜNÜ değiştirir —
 * ziyaretçi bir anda daha az ülke görür.
 *
 * Asıl tehlike ilanlarda: pasife çekilen ülkede yayında ilan varsa o ilanlar
 * ülke filtresinden düşer, sahipleri hiçbir şey yapmadan görünmez olur.
 * Satır geri yazılınca ilanlar geri gelir ama arada geçen süre geri gelmez;
 * {@see EylemRiski} ölçütünde bu tam olarak yüksek risk demektir. Bu yüzden
 * önizleme ülkenin adını değil, o ülkedeki AKTİF İLAN SAYISINI da söyler:
 * sahip "Japonya'yı kapat" derken 40 ilanı kapattığını görmeden onaylamasın.
 */
class UlkeDurumDegistir extends Eylem
{
    public function ad(): string
    {
        return 'ulke-durum-degistir';
    }

    public function baslik(): string
    {
        return 'Ülkeyi aktif/pasif yap';
    }

    public function aciklama(): string
    {
        return 'Zaten kayıtlı olan bir ülkeyi sitede görünür (aktif) ya da görünmez (pasif) yapar. '
            .'Pasif ülke arama filtrelerinde ve ilan verme formunda çıkmaz, o ülkedeki ilanlar '
            .'filtrelerden düşer. Ülkeyi SİLMEZ, ilanlara dokunmaz. Listede olmayan bir ülke '
            .'için bunu kullanma — önce ulke-ekle gerekir.';
    }

    public function sema(): array
    {
        return [
            'kod' => 'Sitede KAYITLI olan ülkenin iki harfli kodu, büyük harf. Japonya için JP.',
            'aktif' => 'true ise ülke sitede görünür olur, false ise gizlenir.',
        ];
    }

    public function kurallar(): array
    {
        return [
            // `exists` şart: yapay zekâ sitede hiç olmayan bir ülke kodu
            // uydurabilir ("Japonya'yı kapat" der, JP kayıtlı değildir).
            // Böyle bir durumda sessizce hiçbir şey yapmak değil, açıkça
            // hata vermek doğru cevap.
            'kod' => ['required', 'string', 'size:2', 'alpha', 'exists:countries,code'],
            'aktif' => ['required', 'boolean'],
        ];
    }

    public function risk(): EylemRiski
    {
        // Tek satır ama sitenin yüzü: pasife çekilen ülkenin ilanları
        // filtrelerden düşer, ziyaretçi daha az ülke görür.
        return EylemRiski::Yuksek;
    }

    public function onizleme(array $p): string
    {
        $kod = strtoupper((string) $p['kod']);
        $aktif = (bool) $p['aktif'];
        $ulke = Country::query()->where('code', $kod)->first();

        if ($ulke === null) {
            return "{$kod} kodlu ülke listede yok.";
        }

        if ((bool) $ulke->is_active === $aktif) {
            $durum = $aktif ? 'zaten aktif' : 'zaten pasif';

            return "{$ulke->name_tr} ({$kod}) {$durum}. Bu eylem hiçbir şeyi değiştirmez.";
        }

        $ilanSayisi = Listing::query()->where('country_code', $kod)->active()->count();

        if ($aktif) {
            return "{$ulke->name_tr} ({$kod}) AKTİF yapılacak: arama filtrelerinde ve ilan verme "
                ."formunda yeniden görünür. Bu ülkedeki {$ilanSayisi} aktif ilan tekrar "
                .'filtrelerden bulunabilir olur.';
        }

        return "{$ulke->name_tr} ({$kod}) PASİF yapılacak: arama filtrelerinden ve ilan verme "
            ."formundan kalkar. Bu ülkedeki {$ilanSayisi} aktif ilan silinmez ama ülke "
            .'filtresinden düşer, yani ziyaretçi bunları bulamaz.';
    }

    public function uygula(array $p): array
    {
        $kod = strtoupper((string) $p['kod']);
        $aktif = (bool) $p['aktif'];

        $ulke = Country::query()->where('code', $kod)->firstOrFail();

        // Eski durum İZE yazılır, "tersini yap" varsayılmaz: eylem zaten
        // aktif bir ülkeyi aktif yapmış olabilir ve geri alma o durumda
        // ülkeyi pasife çekmemeli.
        $oncekiDurum = (bool) $ulke->is_active;

        $ulke->update(['is_active' => $aktif]);

        return [
            'sonuc' => $aktif
                ? "{$ulke->name_tr} ({$kod}) aktif yapıldı, sitede yeniden görünüyor."
                : "{$ulke->name_tr} ({$kod}) pasif yapıldı, sitede görünmüyor.",
            // İz KOD taşır: `countries` tablosunda `id` kolonu YOK, birincil
            // anahtar `code`. `$ulke->id` sessizce null döner ve geri alma
            // hedefini asla bulamazdı.
            'geri_alma' => [
                'kod' => $kod,
                'ad' => $ulke->name_tr,
                'onceki_aktif' => $oncekiDurum,
            ],
        ];
    }

    public function geriAl(array $iz): string
    {
        $ulke = Country::query()->where('code', (string) ($iz['kod'] ?? ''))->first();

        if ($ulke === null) {
            return 'Ülke artık listede yok, durumu geri yazılamadı.';
        }

        $onceki = (bool) ($iz['onceki_aktif'] ?? true);
        $ulke->update(['is_active' => $onceki]);

        return $onceki
            ? "{$ulke->name_tr} eski hâline döndürüldü: yeniden aktif, sitede görünüyor."
            : "{$ulke->name_tr} eski hâline döndürüldü: zaten pasifti, yine pasif.";
    }

    public function ornekler(): array
    {
        return [
            'Japonya\'yı şimdilik kapat',
            'Kanada ülkesini pasife çek',
            'Avustralya\'yı tekrar aktif yap',
            'Hollanda sitede görünmesin',
        ];
    }
}
