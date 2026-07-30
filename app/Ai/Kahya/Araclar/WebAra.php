<?php

namespace App\Ai\Kahya\Araclar;

use App\Services\Kahya\Dis\WebAramasi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

/**
 * Okuma halkasının web gözü (F3).
 *
 * Araç HER ZAMAN kayıtlıdır — anahtar yokken bile. Kayıtlı olmayan araç
 * modele görünmez ve model sahibe "web'e bakamıyorum, şurayı doldur"
 * diyemezdi; kayıtlı-ama-yapılandırılmamış araç ise tarif verir. Aynı
 * mantık limit aşımında: sessizce kaybolmak yerine dürüstçe reddeder.
 */
class WebAra implements Tool
{
    public function __construct(private readonly WebAramasi $arama) {}

    public function name(): string
    {
        return 'web-ara';
    }

    public function description(): Stringable|string
    {
        return 'Web\'de güncel bilgi arar (topluluklar, rakipler, kanallar, haberler). '
            .'Sonuç başlık+url+özet listesidir; özetle yetinme gereken yerde sahibe url ver. '
            .'Site içi veriler için KULLANMA — onlar tablo-sorgula\'da. Her arama aylık '
            .'krediden düşer; gereksiz tekrar arama yapma.';
    }

    public function handle(Request $request): Stringable|string
    {
        if (! $this->arama->hazirMi()) {
            return 'YAPILANDIRILMAMIŞ: Web araması için anahtar girilmemiş. Sahibe söyle: '
                .'Kâhya Ayarları → Dış Gözler bölümünden sağlayıcı (Tavily ya da Brave) seçip '
                .'API anahtarını girmeli. Tavily: tavily.com · Brave: brave.com/search/api';
        }

        $kullanim = $this->arama->buAykiKullanim();
        $limit = $this->arama->aylikLimit();

        if ($kullanim >= $limit) {
            return "LİMİT DOLDU: Bu ayın arama hakkı bitti ({$kullanim}/{$limit}). Sahibe söyle: "
                .'limiti Kâhya Ayarları\'ndan artırabilir ya da gelecek ayı bekleyebilirsin.';
        }

        try {
            $sonuclar = $this->arama->ara(
                (string) $request['sorgu'],
                (int) ($request['sonuc_sayisi'] ?? 5),
            );
        } catch (Throwable $e) {
            return 'HATA: '.$e->getMessage();
        }

        if ($sonuclar === []) {
            return '(Sonuç yok — sorguyu farklı kelimelerle dene.)';
        }

        return collect($sonuclar)
            ->map(fn (array $s): string => "- {$s['baslik']}\n  {$s['url']}\n  {$s['ozet']}")
            ->implode("\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sorgu' => $schema->string()
                ->description('Arama sorgusu — hedefe uygun dilde yaz (Türkçe topluluk için Türkçe, yabancı kaynak için İngilizce).')
                ->required(),
            'sonuc_sayisi' => $schema->integer()
                ->description('Kaç sonuç (varsayılan 5, tavan 10).'),
        ];
    }
}
