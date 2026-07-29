<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\CategoryType;
use App\Enums\EylemRiski;
use App\Models\Category;
use App\Services\Kahya\Eylem\Eylem;
use Closure;
use Illuminate\Support\Str;

/**
 * Kategori ağacına yeni bir kategori ekler; istenirse bir üst kategorinin altına.
 *
 * ÖNEMLİ İRONİ: Kâhya'nın kendi teşhisi "ilansız kategori" sayısını bir sorun
 * olarak raporlar — ve bu eylemin her çalışması o sayıyı bir artırır, çünkü
 * yeni kategori BOŞ doğar. Çelişkiyi çözmenin yolu eylemi yasaklamak değil
 * (kategori açmak meşru bir sahiplik işi), sahibi önizlemede açıkça uyarmak:
 * ilan gelene kadar bu kategori raporlarda "ilansız" olarak görünecek.
 */
class KategoriEkle extends Eylem
{
    public function ad(): string
    {
        return 'kategori-ekle';
    }

    public function baslik(): string
    {
        return 'Kategori ekle';
    }

    public function aciklama(): string
    {
        return 'Sitenin kategori ağacına yeni bir kategori ekler; menüde, filtrelerde ve ilan '
            .'verme formunda seçilebilir hâle gelir. Üst kategori verilirse onun altına alt '
            .'kategori olarak açılır. Kategori ETİKET DEĞİLDİR — ilanın yanına iliştirilen '
            .'sözcük için etiket-ekle var. Kategori ZATEN VARSA bu eylemi kullanma. '
            .'İçine ilan koymaz; kategori boş doğar.';
    }

    public function sema(): array
    {
        return [
            'ad' => 'Kategorinin görünen TÜRKÇE adı. Ör. "Bahçe Bakımı", "Ofis Temizliği".',
            'ust_kategori' => 'İsteğe bağlı, üst kategorinin sayısal kimliği. Verilirse yeni '
                .'kategori onun altına alt kategori olarak açılır; verilmezse en üst düzeye eklenir.',
            'tur' => 'İsteğe bağlı tür: hizmet, urun, emlak veya vasita. Verilmezse üst '
                .'kategorininki, o da yoksa hizmet.',
            'ikon' => 'İsteğe bağlı ikon — mevcut kategorilerdeki gibi tek bir emoji. Ör. 🧹, 🌿.',
        ];
    }

    public function kurallar(): array
    {
        return [
            'ad' => [
                'required',
                'string',
                'max:100',
                /*
                 * Ad çakışması hata DEĞİL (adres sayıyla çözülür, bkz.
                 * benzersizSlug) ama addan hiç adres türetilememesi hatadır:
                 * yalnızca emoji ya da noktalama içeren bir ad, slug'ı boş
                 * bırakıp veritabanı kısıtına çarpar. Bunu veritabanı değil,
                 * burası anlaşılır bir cümleyle reddetmeli.
                 */
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (Str::slug((string) $value) === '') {
                        $fail('Bu addan geçerli bir kategori adresi türetilemedi; harf içeren bir ad ver.');
                    }
                },
            ],
            // `exists` şart: yapay zekâ olmayan bir üst kategori kimliği
            // uydurabilir ve bunu veritabanı değil, burası reddetmeli.
            'ust_kategori' => ['nullable', 'integer', 'exists:categories,id'],
            'tur' => ['nullable', 'string', 'in:hizmet,urun,emlak,vasita'],
            'ikon' => ['nullable', 'string', 'max:16'],
        ];
    }

    public function risk(): EylemRiski
    {
        // Tek satır ekler, kimseye bildirim gitmez, geri alması tek tıktır.
        return EylemRiski::Dusuk;
    }

    public function onizleme(array $p): string
    {
        $ad = (string) $p['ad'];
        $ust = $this->ustKategori($p);
        $tur = $this->tur($p, $ust);
        $slug = $this->benzersizSlug($ad);

        $konum = $ust !== null
            ? "\"{$ust->name}\" kategorisinin altına"
            : 'en üst düzeye';

        return "\"{$ad}\" adıyla 1 yeni {$tur->getLabel()} kategorisi {$konum} AKTİF olarak "
            ."eklenecek (adres: {$slug}). Dikkat: kategori BOŞ doğar — ilan gelene kadar "
            .'Kâhya\'nın raporlarındaki "ilansız kategori" sayısını bir artırır.';
    }

    public function uygula(array $p): array
    {
        $ad = (string) $p['ad'];
        $ust = $this->ustKategori($p);
        $slug = $this->benzersizSlug($ad);

        $kategori = Category::create([
            'parent_id' => $ust?->id,
            'name' => $ad,
            'slug' => $slug,
            'icon' => $p['ikon'] ?? null,
            'type' => $this->tur($p, $ust),
            'is_active' => true,
            // Kardeşlerin sonuna: mevcut sıralama sahibin kurduğu bir düzen
            // olabilir, araya girmek onu bozar.
            'sort_order' => (int) Category::query()
                ->where('parent_id', $ust?->id)
                ->max('sort_order') + 1,
        ]);

        return [
            'sonuc' => "\"{$ad}\" kategorisi eklendi (adres: {$slug}).",
            'geri_alma' => ['id' => $kategori->id, 'ad' => $ad],
        ];
    }

    public function geriAl(array $iz): string
    {
        $kategori = Category::query()->find($iz['id'] ?? 0);

        if ($kategori === null) {
            return 'Kategori zaten yok.';
        }

        /*
         * İÇİ DOLMUŞSA SİLİNMEZ, PASİFE ÇEKİLİR.
         *
         * Eklemek ile geri almak arasında zaman geçer: bu kategoriye ilan
         * girilmiş ya da altına alt kategori açılmış olabilir. Silmek
         * ilanların kategori bağını koparır; alt kategorileri de (parent_id
         * boşalınca) menünün en üst düzeyine savurur. "Geri aldım" derken
         * sahibin kurduğu yapıyı bozmak, geri almamaktan kötüdür. O yüzden
         * geri alma burada pasife çekmeye dönüşür ve bunu AÇIKÇA söyler.
         */
        $ilanSayisi = $kategori->listings()->count();
        $altSayisi = $kategori->children()->count();

        if ($ilanSayisi > 0 || $altSayisi > 0) {
            $kategori->update(['is_active' => false]);

            $neden = implode(' ve ', array_filter([
                $ilanSayisi > 0 ? "{$ilanSayisi} ilan" : null,
                $altSayisi > 0 ? "{$altSayisi} alt kategori" : null,
            ]));

            return "\"{$kategori->name}\" silinmedi çünkü içinde {$neden} var — pasife çekildi, sitede görünmüyor.";
        }

        $ad = $kategori->name;
        $kategori->delete();

        return "\"{$ad}\" kategorisi kaldırıldı.";
    }

    public function ornekler(): array
    {
        return [
            'kategorilere Bahçe Bakımı ekle',
            'Ev & Tamir altına "Çatı Onarımı" diye bir alt kategori aç',
            'ürünler için El Örgüsü kategorisi eksik, ekler misin',
        ];
    }

    private function ustKategori(array $p): ?Category
    {
        return empty($p['ust_kategori'])
            ? null
            : Category::query()->find($p['ust_kategori']);
    }

    /**
     * Tür verilmemişse üst kategorininki miras alınır: "Emlak" altında
     * hizmet türlü bir alt kategori, filtrelerde ya iki listede birden
     * görünür ya da hiçbirinde. Üst de yoksa sitenin en yaygın türü olan
     * hizmete düşülür (veritabanı varsayılanıyla aynı).
     */
    private function tur(array $p, ?Category $ust): CategoryType
    {
        if (! empty($p['tur'])) {
            return CategoryType::from((string) $p['tur']);
        }

        return $ust->type ?? CategoryType::Hizmet;
    }

    /**
     * Adres çakışması reddedilmez, sayıyla çözülür: aynı ad iki farklı üst
     * kategorinin altında meşru olabilir (Hizmet > Temizlik, Ürün > Temizlik).
     * Sonuçta hangi adresin kullanılacağı önizlemede gösterilir.
     */
    private function benzersizSlug(string $ad): string
    {
        $taban = Str::slug($ad);
        $slug = $taban;

        for ($i = 2; Category::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$taban}-{$i}";
        }

        return $slug;
    }
}
