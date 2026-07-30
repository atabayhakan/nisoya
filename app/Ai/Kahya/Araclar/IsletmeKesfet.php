<?php

namespace App\Ai\Kahya\Araclar;

use App\Services\Kahya\Dis\IsletmeKesfi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

/**
 * Okuma halkasının keşif gözü (F3): Google Places ile işletme arama.
 *
 * "Gerçek kullanıcı bul" misyonunun (docs/06) sorgu ayağı. Araç yalnız
 * KEŞFEDER — kimseye ulaşmaz, e-posta toplamaz; bulduklarından bir hamle
 * doğacaksa o hamle-oner kartından geçer (tek onay kapısı).
 */
class IsletmeKesfet implements Tool
{
    public function __construct(private readonly IsletmeKesfi $kesif) {}

    public function name(): string
    {
        return 'isletme-kesfet';
    }

    public function description(): Stringable|string
    {
        return 'Google Places ile işletme keşfeder — "Turkish barber Rotterdam", "Türk market '
            .'Berlin" gibi {yer}+{meslek} sorguları. Sonuç: ad, adres, puan, web sitesi. '
            .'Türk işletmesi ararken sorguya "Turkish/Türk" ekle, hedef ülkenin dilindeki '
            .'varyantı da dene. Her çağrı ücretli ve aylık limitli — aynı sorguyu tekrarlama. '
            .'KEŞİF salt bilgidir: bulduğun işletmeye ulaşmak istersen hamle-oner kartı yaz.';
    }

    public function handle(Request $request): Stringable|string
    {
        if (! $this->kesif->hazirMi()) {
            return 'YAPILANDIRILMAMIŞ: İşletme keşfi için Google Places API anahtarı girilmemiş. '
                .'Sahibe söyle: Google Cloud Console\'da Places API (New) etkinleştirip anahtarı '
                .'Kâhya Ayarları → Dış Gözler bölümüne girmeli.';
        }

        $kullanim = $this->kesif->buAykiKullanim();
        $limit = $this->kesif->aylikLimit();

        if ($kullanim >= $limit) {
            return "LİMİT DOLDU: Bu ayın keşif hakkı bitti ({$kullanim}/{$limit}). Sahibe söyle: "
                .'limiti Kâhya Ayarları\'ndan artırabilir.';
        }

        try {
            $sonuclar = $this->kesif->kesfet(
                (string) $request['sorgu'],
                (int) ($request['sonuc_sayisi'] ?? 10),
            );
        } catch (Throwable $e) {
            return 'HATA: '.$e->getMessage();
        }

        if ($sonuclar === []) {
            return '(Sonuç yok — sorguyu genişlet ya da farklı dilde dene.)';
        }

        return collect($sonuclar)
            ->map(fn (array $s): string => "- {$s['ad']} · {$s['adres']}"
                .($s['puan'] !== null ? " · ⭐{$s['puan']}" : '')
                .($s['site'] !== null ? "\n  site: {$s['site']}" : ''))
            ->implode("\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sorgu' => $schema->string()
                ->description('Places metin sorgusu: {yer} + {meslek/tür}. Ör. "Turkish restaurant Chicago".')
                ->required(),
            'sonuc_sayisi' => $schema->integer()
                ->description('Kaç sonuç (varsayılan 10, tavan 20).'),
        ];
    }
}
