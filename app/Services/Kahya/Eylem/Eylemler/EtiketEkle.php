<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Models\Tag;
use App\Services\Kahya\Eylem\Eylem;
use Closure;
use Illuminate\Support\Str;

/**
 * İlanların etiketlenmesinde kullanılan etiket listesine yeni bir etiket ekler.
 *
 * ETİKET KATEGORİ DEĞİLDİR. Kategori sitenin iskeleti — menüde, filtrede,
 * adreste görünür. Etiket ise ilanın yanına iliştirilen serbest bir sözcük.
 * Yapay zekâ ikisini karıştırmaya çok yatkın olduğu için `aciklama()` bunu
 * açıkça söylüyor: yanlış eylem seçilirse sahip yanlış yeri düzeltmeye çalışır.
 *
 * Etiketin `slug`'ı addan türetilir; sahip ayrıca girmez, çünkü girdiği şey
 * eninde sonunda yanlış olur (Türkçe harf, boşluk, büyük harf).
 */
class EtiketEkle extends Eylem
{
    public function ad(): string
    {
        return 'etiket-ekle';
    }

    public function baslik(): string
    {
        return 'Etiket ekle';
    }

    public function aciklama(): string
    {
        return 'İlanlara iliştirilebilen etiket listesine yeni bir etiket ekler. '
            .'Etiket KATEGORİ DEĞİLDİR: menüde yer almaz, ilanın altında bir sözcük olarak durur — '
            .'kategori için kategori-ekle var. Etiketi bir ilana BAĞLAMAZ, yalnızca listeye ekler. '
            .'Aynı ada sahip etiket zaten varsa bu eylemi kullanma.';
    }

    public function sema(): array
    {
        return [
            'ad' => 'Etiketin görünen TÜRKÇE adı. Ör. "Acil", "Öğrenci dostu", "Evcil hayvan kabul".',
        ];
    }

    public function kurallar(): array
    {
        return [
            'ad' => [
                'required',
                'string',
                'max:60',
                /*
                 * Benzersizlik ADDA DEĞİL, ADDAN TÜREYEN SLUG'DA aranır.
                 *
                 * "Evcil Hayvan" ile "evcil hayvan" farklı iki addır ama aynı
                 * slug'a iner ve veritabanındaki unique kısıtı devreye girip
                 * isteği 500 hatasıyla patlatır. Yapay zekâ var olan bir
                 * etiketi ufak bir yazım farkıyla yeniden eklemeye çalışacak;
                 * bunu veritabanı değil, burası anlaşılır bir cümleyle
                 * reddetmeli.
                 */
                function (string $attribute, mixed $value, Closure $fail): void {
                    $slug = Str::slug((string) $value);

                    if ($slug === '') {
                        $fail('Bu addan geçerli bir etiket adresi türetilemedi; harf içeren bir ad ver.');

                        return;
                    }

                    if (Tag::query()->where('slug', $slug)->exists()) {
                        $fail("Bu etiket zaten var (adres: {$slug}).");
                    }
                },
            ],
        ];
    }

    public function risk(): EylemRiski
    {
        // Tek satır ekler, hiçbir ilana dokunmaz, kimseye bildirim gitmez.
        return EylemRiski::Dusuk;
    }

    public function onizleme(array $p): string
    {
        $ad = (string) $p['ad'];
        $slug = Str::slug($ad);

        return "\"{$ad}\" adıyla 1 yeni etiket eklenecek (adres: {$slug}). ".
            'Etiket listeye girer; hiçbir ilana kendiliğinden bağlanmaz.';
    }

    public function uygula(array $p): array
    {
        $ad = (string) $p['ad'];

        $etiket = Tag::create([
            'name' => $ad,
            'slug' => Str::slug($ad),
        ]);

        return [
            'sonuc' => "\"{$ad}\" etiketi eklendi.",
            'geri_alma' => ['id' => $etiket->id, 'ad' => $ad],
        ];
    }

    public function geriAl(array $iz): string
    {
        $etiket = Tag::query()->find($iz['id'] ?? 0);

        if ($etiket === null) {
            return 'Etiket zaten yok.';
        }

        /*
         * BİR İLANA BAĞLANMIŞSA SİLİNMEZ.
         *
         * Etiketi silmek `listing_tag` satırlarını da götürür; yani sahibin
         * ya da bir ilan sahibinin elle kurduğu bağ sessizce kaybolur.
         * Etiketin "pasif" hâli yok (tabloda böyle bir alan yok), o yüzden
         * ülke eylemindeki gibi pasife çekme numarası da yapılamaz.
         * Geriye tek dürüst seçenek kalıyor: dokunma ve neden dokunmadığını
         * söyle.
         */
        $ilanSayisi = $etiket->listings()->count();

        if ($ilanSayisi > 0) {
            return "\"{$etiket->name}\" silinmedi çünkü {$ilanSayisi} ilana bağlanmış — ".
                'silmek o bağları koparırdı. Yine de gerekiyorsa Etiketler ekranından elle silebilirsin.';
        }

        $ad = $etiket->name;
        $etiket->delete();

        return "\"{$ad}\" etiketi listeden kaldırıldı.";
    }

    public function ornekler(): array
    {
        return [
            'etiketlere "Acil" ekle',
            '"Öğrenci dostu" diye bir etiket açar mısın',
            'evcil hayvan kabul etiketi yok, ekle',
        ];
    }
}
