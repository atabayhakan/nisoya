<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Models\Category;
use App\Models\Country;
use App\Models\Currency;
use App\Services\Kahya\Eylem\Eylem;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Bir ülkenin, kategorinin ya da para biriminin listedeki sırasını değiştirir.
 *
 * ÜÇ TÜR TEK EYLEMDE: "X'i üste al" cümlesi ülke için de kategori için de
 * aynı cümledir; üç ayrı eylem yapay zekâya üç ayrı yanlış seçim şansı verir.
 * Türü parametre yapmak seçimi tek noktada toplar ve orada doğrulanır.
 *
 * SIRA NUMARASI YAZILIR, LİSTE KAYDIRILMAZ: yalnızca bu kaydın sort_order
 * değeri değişir; aynı numarada başka kayıt varsa ikisi de o numarada kalır
 * ve liste yine tutarlı sıralanır. "3. sıraya koy, gerisini it" davranışı
 * bilerek yok — kaydırma yapan bir eylemin geri alması bütün listenin eski
 * hâlini taşımak zorunda kalırdı; tek numarayı geri yazmaksa birebirdir.
 */
class SiraDegistir extends Eylem
{
    /** Desteklenen türler ve mesajlarda geçen Türkçe adları. */
    public const TURLER = [
        'ulke' => 'ülke',
        'kategori' => 'kategori',
        'para_birimi' => 'para birimi',
    ];

    public function ad(): string
    {
        return 'sira-degistir';
    }

    public function baslik(): string
    {
        return 'Liste sırasını değiştir';
    }

    public function aciklama(): string
    {
        return 'Kayıtlı bir ülkenin, kategorinin ya da para biriminin listedeki sırasını '
            .'değiştirir; küçük numara listede önce gelir. Yalnızca verilen kaydın numarasını '
            .'yazar, öteki kayıtları kaydırmaz. Kayıt eklemez, silmez, aktifliğine dokunmaz. '
            .'Şehirlerin sırası bu eylemle değiştirilemez.';
    }

    public function sema(): array
    {
        return [
            'tur' => "Neyin sırası değişecek: 'ulke', 'kategori' ya da 'para_birimi'.",
            'kimlik' => 'Ülke için iki harfli kod (Japonya için JP), para birimi için üç harfli kod (EUR), kategori için sayısal id.',
            'sira' => 'Yeni sıra numarası; 0 ya da daha büyük tamsayı. Küçük numara listede önce gösterilir.',
        ];
    }

    public function kurallar(): array
    {
        return [
            'tur' => ['required', 'string', Rule::in(array_keys(self::TURLER))],
            // Tek bir `exists:` yazılamaz: kimliğin hangi tabloda aranacağını
            // `tur` söylüyor. Bu kural türe bakıp doğru tabloda arar — yapay
            // zekâ olmayan bir kod ya da id uydurursa hata BURADA çıkar, iş
            // yapılırken değil.
            'kimlik' => [
                'required',
                new class($this->kayitBul(...)) implements DataAwareRule, ValidationRule
                {
                    /** @var array<string, mixed> */
                    private array $veri = [];

                    public function __construct(private readonly Closure $kayitBul) {}

                    public function setData(array $data): static
                    {
                        $this->veri = $data;

                        return $this;
                    }

                    public function validate(string $attribute, mixed $value, Closure $fail): void
                    {
                        $tur = (string) ($this->veri['tur'] ?? '');

                        // Tür geçersizse kendi kuralı zaten reddediyor;
                        // üstüne ikinci bir hata üretmek kafa karıştırır.
                        if (! array_key_exists($tur, SiraDegistir::TURLER)) {
                            return;
                        }

                        if (($this->kayitBul)($tur, (string) $value) === null) {
                            $fail('Bu kimlikle kayıtlı bir '.SiraDegistir::TURLER[$tur].' yok.');
                        }
                    }
                },
            ],
            'sira' => ['required', 'integer', 'min:0'],
        ];
    }

    public function risk(): EylemRiski
    {
        // Tek alan, tek satır; eski numara izde durur ve birebir geri yazılır.
        return EylemRiski::Dusuk;
    }

    public function onizleme(array $p): string
    {
        $tur = (string) $p['tur'];
        $kayit = $this->kayitBul($tur, (string) $p['kimlik']);

        if ($kayit === null) {
            return 'Böyle bir kayıt listede yok, sırası değiştirilemez.';
        }

        $ad = $this->kayitAdi($kayit);
        $turAd = self::TURLER[$tur];
        $eski = (int) $kayit->sort_order;
        $yeni = (int) $p['sira'];

        if ($eski === $yeni) {
            return "{$ad} ({$turAd}) zaten {$yeni}. sırada. Bu eylem hiçbir şeyi değiştirmez.";
        }

        return "{$ad} ({$turAd}) listedeki {$eski}. sıradan {$yeni}. sıraya alınacak. "
            .'Yalnızca bu kaydın numarası değişir, öbür kayıtlar kaydırılmaz.';
    }

    public function uygula(array $p): array
    {
        $tur = (string) $p['tur'];
        $kayit = $this->kayitBul($tur, (string) $p['kimlik']);

        // kurallar() varlığı doğruladı; buraya düşmek ancak arada kaydın
        // silinmesiyle olur. Sessizce geçmek değil, açıkça durmak doğru.
        if ($kayit === null) {
            throw new RuntimeException('Kayıt doğrulamadan sonra kayboldu, sıra değiştirilmedi.');
        }

        $ad = $this->kayitAdi($kayit);
        $eskiSira = (int) $kayit->sort_order;
        $yeniSira = (int) $p['sira'];

        $kayit->update(['sort_order' => $yeniSira]);

        return [
            'sonuc' => "{$ad} (".self::TURLER[$tur].") {$eskiSira}. sıradan {$yeniSira}. sıraya alındı.",
            'geri_alma' => [
                // İz KİMLİĞİ taşır, `$kayit->id` DEĞİL: ülke ve para birimi
                // tablolarının birincil anahtarı `code`'dur, `id` kolonları
                // hiç yoktur — `$kayit->id` onlarda sessizce null döner ve
                // geri alma hedefini asla bulamazdı.
                'tur' => $tur,
                'kimlik' => (string) $p['kimlik'],
                'ad' => $ad,
                'eski_sira' => $eskiSira,
            ],
        ];
    }

    public function geriAl(array $iz): string
    {
        // Bulma işi uygula() ile AYNI yoldan: iki ayrı arama yazmak, birinin
        // sessizce eskimesi demektir.
        $kayit = $this->kayitBul((string) ($iz['tur'] ?? ''), (string) ($iz['kimlik'] ?? ''));

        if ($kayit === null) {
            return 'Kayıt artık listede yok, sırası geri yazılamadı.';
        }

        $eski = (int) ($iz['eski_sira'] ?? 0);
        $kayit->update(['sort_order' => $eski]);

        return $this->kayitAdi($kayit)." yeniden {$eski}. sıraya alındı, sıralama eski hâlinde.";
    }

    public function ornekler(): array
    {
        return [
            'ülke listesinde Almanya en üstte dursun',
            'Ev Yemekleri kategorisini ikinci sıraya al',
            'Euro para birimleri listesinde başa gelsin',
        ];
    }

    /**
     * Kimliği türüne göre doğru tabloda arar: ülke koduyla, kategori ve
     * para birimi id ile bulunur. Doğrulama kuralı da aynı yolu kullanır ki
     * "kural geçti ama kayıt bulunamadı" ikiliği hiç doğmasın.
     */
    private function kayitBul(string $tur, string $kimlik): Country|Category|Currency|null
    {
        // Ülke ve para biriminde birincil anahtar KODdur (`id` kolonu yok);
        // sayısal id yalnız kategoride var.
        return match ($tur) {
            'ulke' => Country::query()->where('code', strtoupper($kimlik))->first(),
            'kategori' => Category::query()->find((int) $kimlik),
            'para_birimi' => Currency::query()->where('code', strtoupper($kimlik))->first(),
            default => null,
        };
    }

    private function kayitAdi(Country|Category|Currency $kayit): string
    {
        return $kayit instanceof Country ? (string) $kayit->name_tr : (string) $kayit->name;
    }
}
