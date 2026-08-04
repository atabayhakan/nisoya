<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\RestrictsToAdmins;
use App\Models\DosyaAnlikGoruntusu;
use App\Reports\NisoyaDosyasi;
use App\Support\Settings;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Yatırımcı memosu — 2 sayfa, risk azaltma belgesi.
 *
 * ---------------------------------------------------------------------------
 * NEDEN "GENEL BAKIŞ"TAN AYRI BİR BELGE
 *
 * Sahip ikisini tek belge sanıyordu. Değiller:
 *
 *   Genel Bakış  → içeriye bakar, her özelliği anlatır, uzun olabilir.
 *   Bu memo      → dışarıya bakar, RİSKİ ÖLDÜRÜR, iki sayfa.
 *
 * Yatırımcıya 40 özelliğin listesini vermek traction değil, **traction
 * yokluğunun itirafıdır**. Özellikler ancak "tek kişi, geliştirici değil,
 * N ayda" sermaye verimliliği kanıtı olarak anlamlıdır — bu belge onları
 * o çerçevede kullanır.
 *
 * ---------------------------------------------------------------------------
 * DÜRÜSTLÜK BU BELGENİN ÜRÜN ÖZELLİĞİ
 *
 * Bugün pazaryerinde çok az gerçek ilan var ve yatırımcı ilan sahiplerine tek
 * tek bakar. Bu yüzden belge arzın azlığını GİZLEMEZ, açıkça yazar ve
 * darboğaz diye adlandırır. Okuyanın ilk soracağı soruyu öne almak, o soruyu
 * onun bulmasını beklemekten güçlüdür.
 *
 * Kaçınılanlar (araştırma bulgusu): yukarıdan-aşağı pazar aritmetiği
 * ("6,5 milyon Türk × X €"), vanity metrikler, sahte GMV eğrisi, erken take
 * rate taahhüdü, n<30'da kohort grafiği.
 *
 * ---------------------------------------------------------------------------
 * ANLATI METİNLERİ PANELDEN DÜZENLENİR
 *
 * Vizyon, koridor ve ask gibi alanlar `Settings`'te. Sahip bunları geliştirici
 * olmadan güncelleyebilmeli; koda gömülü bir "ask" rakamı ikinci ayda yalan
 * olur. SAYILAR ise düzenlenemez — onlar canlı veriden gelir.
 */
class YatirimciMemosu extends Page
{
    use RestrictsToAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem & Araçlar';

    protected static ?string $navigationLabel = 'Yatırımcı Memosu';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.yatirimci-memosu';

    public function getTitle(): string
    {
        return 'Yatırımcı Memosu';
    }

    public function dosya(): NisoyaDosyasi
    {
        return app(NisoyaDosyasi::class);
    }

    /** @return array{ilan: int, satici: int, sehir: int, ulke: int} */
    public function envanter(): array
    {
        return $this->dosya()->envanter();
    }

    /** @return array{konusma: int, karsilikli: int, anlasma: int} */
    public function huni(): array
    {
        return $this->dosya()->huni();
    }

    /** @return list<array{hafta: string, adet: int}> */
    public function kuzeyYildizi(): array
    {
        return $this->dosya()->kuzeyYildizi();
    }

    /** @return array{rehber_icerik: int, rehber_ulke: int, sayfa: int} */
    public function icerikSayilari(): array
    {
        return $this->dosya()->icerik();
    }

    public function aySayisi(): int
    {
        return $this->dosya()->aySayisi();
    }

    public function kesimMetni(): string
    {
        return $this->dosya()->kesimTarihi()->translatedFormat('d F Y');
    }

    /**
     * Panelden düzenlenebilir anlatı metni; boşsa null döner ve bölüm
     * BASILMAZ — doldurulmamış bir başlık, olmayan bir bölümden kötüdür.
     */
    public function metin(string $anahtar): ?string
    {
        $deger = trim((string) Settings::get('memo.'.$anahtar, ''));

        return $deger !== '' ? $deger : null;
    }

    /** Belgede henüz doldurulmamış anlatı alanları — sahibe uyarı olarak basılır. */
    public function eksikMetinler(): array
    {
        $gerekli = [
            'problem' => 'Problem ve bugünkü ikame',
            'koridor' => 'Tek koridor ve doyurma ölçüsü',
            'ask' => 'Ask ve kilometre taşları',
        ];

        return array_values(array_filter(
            $gerekli,
            fn (string $anahtar): bool => $this->metin($anahtar) === null,
            ARRAY_FILTER_USE_KEY
        ));
    }

    /**
     * O günkü rakamları deftere yazar.
     *
     * Belge her açılışta DEĞİL, sahip istediğinde kaydedilir: her görüntüleme
     * bir satır düşseydi defter gürültüye boğulur ve "hangi rakamı verdim"
     * sorusu yine cevapsız kalırdı.
     */
    public function anligiKaydet(): void
    {
        DosyaAnlikGoruntusu::create([
            'tur' => DosyaAnlikGoruntusu::TUR_YATIRIMCI,
            'veri' => $this->dosya()->anlikGoruntu() + [
                'huni' => $this->huni(),
                'kuzey_yildizi' => $this->kuzeyYildizi(),
                'ay' => $this->aySayisi(),
            ],
        ]);

        Notification::make()
            ->title('Bu belgenin rakamları deftere yazıldı')
            ->body('Sonraki sürümle karşılaştırılabilir.')
            ->success()
            ->send();
    }

    /** @return list<array{tarih: string, ilan: int, karsilikli: int}> */
    public function gecmisAnliklar(): array
    {
        return DosyaAnlikGoruntusu::query()
            ->where('tur', DosyaAnlikGoruntusu::TUR_YATIRIMCI)
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (DosyaAnlikGoruntusu $a): array => [
                'tarih' => $a->created_at?->translatedFormat('d M Y') ?? '—',
                'ilan' => (int) ($a->veri['envanter']['ilan'] ?? 0),
                'karsilikli' => (int) ($a->veri['huni']['karsilikli'] ?? 0),
            ])
            ->all();
    }
}
