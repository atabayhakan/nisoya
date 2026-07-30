<?php

namespace App\Ai\Kahya\Araclar;

use App\Ai\Kahya\EylemToplayici;
use App\Models\KahyaEylemKaydi;
use App\Models\User;
use App\Services\Kahya\Eylem\Eylem;
use App\Services\Kahya\Eylem\EylemCalistirici;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Bir Kâhya eylemini laravel/ai aracına çeviren adaptör.
 *
 * Eylem sınıfları (app/Services/Kahya/Eylem) Kâhya'nın F1 döneminden beri
 * güvenlik disiplinini taşır: sunucu tarafı doğrulama, denetim defteri
 * (kahya_eylemleri), zorunlu geri-alma izi, risk kapısı. Beyin naklinde
 * (F0, laravel/ai ajan döngüsü) bu disiplin ATILMAZ — eylemler araca
 * sarılır ve çalıştırma yine {@see EylemCalistirici} tek kapısından geçer.
 *
 * Modelin gördüğü: araç adı = eylem adı, açıklama = eylemin ders kitabı
 * (aciklama + örnekler + risk), şema = sema()'nın tip bilgisiyle
 * zenginleştirilmiş hâli. Modelin görmediği: veritabanı, SQL, kayıt.
 */
class EylemAraci implements Tool
{
    public function __construct(
        private readonly Eylem $eylem,
        private readonly EylemCalistirici $calistirici,
        private readonly EylemToplayici $toplayici,
        private readonly ?User $isteyen = null,
    ) {}

    /**
     * laravel/ai araç adını sınıf adından türetir; biz eylem adını (kebab-case,
     * katalogla birebir) kullanmak istiyoruz — Promptable, Tool'da `name()`
     * metodu varsa onu tercih eder.
     */
    public function name(): string
    {
        return $this->eylem->ad();
    }

    public function description(): Stringable|string
    {
        $parcalar = [$this->eylem->aciklama()];

        if ($this->eylem->ornekler() !== []) {
            $parcalar[] = 'Örnek istekler: '.implode(' · ', $this->eylem->ornekler());
        }

        $parcalar[] = $this->eylem->risk()->onayGerekir()
            ? 'Bu iş sahibin onayına sunulur; aracı çağırmak işi hemen yapmaz.'
            : 'Doğrudan uygulanır ve geri alınabilir.';

        return implode(' ', $parcalar);
    }

    public function handle(Request $request): Stringable|string
    {
        $kayit = $this->calistirici->calistir(
            $this->eylem->ad(),
            array_filter($request->all(), fn ($deger): bool => $deger !== null && $deger !== ''),
            $this->isteyen,
        );

        $this->toplayici->ekle($kayit);

        /*
         * Modele dönen metin GERÇEĞİN kendisidir, modelin niyeti değil —
         * F1'den kalan ilke: "modelin 'ekledim' demesi bir şeyin eklendiği
         * anlamına gelmez." Model bu sonucu okuyup sahibe aktarır.
         */
        return match ($kayit->durum) {
            KahyaEylemKaydi::DURUM_UYGULANDI => 'BAŞARILI: '.$kayit->sonuc,
            KahyaEylemKaydi::DURUM_BEKLEMEDE => 'ONAY BEKLİYOR: İş sahibin onayına sunuldu — '.$kayit->onizleme
                .' Sahibe onay kartını gördüğünü ve Onayla/Vazgeç diyebileceğini söyle; işi yaptığını İDDİA ETME.',
            KahyaEylemKaydi::DURUM_HATA => 'HATA: '.$kayit->hata,
            default => (string) $kayit->onizleme,
        };
    }

    public function schema(JsonSchema $schema): array
    {
        $alanlar = [];

        foreach ($this->eylem->sema() as $alan => $aciklama) {
            $kurallar = $this->kurallarMetni($alan);

            $tip = match (true) {
                in_array('integer', $kurallar, true) => $schema->integer(),
                in_array('boolean', $kurallar, true) => $schema->boolean(),
                in_array('numeric', $kurallar, true) => $schema->number(),
                default => $schema->string(),
            };

            $tip = $tip->description($aciklama);

            if (in_array('required', $kurallar, true)) {
                $tip = $tip->required();
            }

            $alanlar[$alan] = $tip;
        }

        return $alanlar;
    }

    /**
     * Bir alanın doğrulama kurallarının YALNIZ metin olanları — tip türetmek
     * için yeterli; closure kuralları (ör. KategoriEkle'nin slug bekçisi)
     * çalıştırma anında zaten uygulanır.
     *
     * @return list<string>
     */
    private function kurallarMetni(string $alan): array
    {
        $kurallar = $this->eylem->kurallar()[$alan] ?? [];

        if (is_string($kurallar)) {
            $kurallar = explode('|', $kurallar);
        }

        return array_values(array_filter(
            is_array($kurallar) ? $kurallar : [],
            'is_string',
        ));
    }
}
