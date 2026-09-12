<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Models\Country;
use App\Models\Currency;
use App\Services\Ai\SystemToolsAiAssistant;
use App\Support\Modules;
use App\Support\Settings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_sistem_yapilandirma_yonet')]
#[Title('Sistem Yapılandırma — Dikey modülleri, ülkeleri, para birimlerini ve demo kapısını yönet')]
#[Description(
    'Platform dikey modüllerini, ülkeleri ve para birimlerini yönetir. '.
    'islem="moduller_listele" (emlak, vasıta, davetiye, iş ilanları modül durumlarını listeler), '.
    'islem="modul_durumu_degistir" (modülü tek tıkla açar veya kapatır), '.
    'islem="ulkeleri_listele" (sitede tanımlı ülkeleri listeler), '.
    'islem="ulke_kaydet" (yeni ülke ekler veya günceller; eksik bilgileri AI tamamlayabilir), '.
    'islem="para_birimleri_listele" (para birimlerini listeler), '.
    'islem="demo_kapisi_durumu" (örnek veri MCP üretim kapısını kontrol eder veya açıp kapatır).'
)]
class SistemYapilandirmaYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "moduller_listele", "modul_durumu_degistir", "ulkeleri_listele", "ulke_kaydet", "para_birimleri_listele", "demo_kapisi_durumu".')
                ->required(),
            'modul_anahtari' => $schema->string()
                ->description('Modül anahtarı: "emlak", "vasita", "davetiye", "is_ilanlari".'),
            'aktif' => $schema->boolean()
                ->description('Modül veya demo kapısı için aktif/pasif durumu.'),
            'ulke_kodu' => $schema->string()
                ->description('Ülke ISO kodu (örn: DE, FR, AT, SE).'),
            'ulke_adi' => $schema->string()
                ->description('Ülke Türkçe adı (örn: Almanya, İsveç).'),
            'emoji' => $schema->string()
                ->description('Ülke bayrak emojisi (örn: 🇩🇪). Boşsa AI önerir.'),
            'para_birimi' => $schema->string()
                ->description('Varsayılan para birimi (örn: EUR, CHF, SEK). Boşsa AI önerir.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'moduller_listele'));

        return match ($islem) {
            'modul_durumu_degistir' => $this->modulDurumuDegistir($request),
            'ulkeleri_listele' => $this->ulkeleriListele(),
            'ulke_kaydet' => $this->ulkeKaydet($request),
            'para_birimleri_listele' => $this->paraBirimleriListele(),
            'demo_kapisi_durumu' => $this->demoKapisiDurumu($request),
            default => $this->modullerListele(),
        };
    }

    /** @return array<string, mixed> */
    private function modullerListele(): array
    {
        $list = [];
        foreach (Modules::KEYS as $key) {
            $list[$key] = [
                'etiket' => Modules::LABELS[$key],
                'aktif' => Modules::enabled($key),
            ];
        }

        return [
            'durum' => 'basarili',
            'toplam_modul' => count($list),
            'moduller' => $list,
        ];
    }

    /** @return array<string, mixed> */
    private function modulDurumuDegistir(Request $request): array
    {
        $key = (string) $request->get('modul_anahtari');
        if (! in_array($key, Modules::KEYS, true)) {
            return [
                'durum' => 'hata',
                'mesaj' => "Geçersiz modül anahtarı: {$key}. Geçerli olanlar: ".implode(', ', Modules::KEYS),
            ];
        }

        $aktif = (bool) $request->get('aktif', true);
        Settings::set("modul.{$key}", $aktif ? '1' : '0');

        return [
            'durum' => 'basarili',
            'mesaj' => "Modül '{$key}' durumu güncellendi: ".($aktif ? 'Aktif' : 'Pasif'),
            'modul' => $key,
            'aktif' => $aktif,
        ];
    }

    /** @return array<string, mixed> */
    private function ulkeleriListele(): array
    {
        $countries = Country::query()
            ->orderBy('sort_order')
            ->orderBy('name_tr')
            ->get(['code', 'name_tr', 'emoji', 'default_currency', 'is_active', 'sort_order']);

        return [
            'durum' => 'basarili',
            'toplam_ulke' => $countries->count(),
            'aktif_sayisi' => $countries->where('is_active', true)->count(),
            'ulkeler' => $countries->toArray(),
        ];
    }

    /** @return array<string, mixed> */
    private function ulkeKaydet(Request $request): array
    {
        $code = Str::upper(trim((string) $request->get('ulke_kodu', '')));
        $nameTr = trim((string) $request->get('ulke_adi', ''));
        $emoji = trim((string) $request->get('emoji', ''));
        $currency = Str::upper(trim((string) $request->get('para_birimi', '')));
        $isActive = (bool) $request->get('aktif', true);

        if ($code === '' && $nameTr === '') {
            return [
                'durum' => 'hata',
                'mesaj' => 'ulke_kodu veya ulke_adi parametrelerinden en az biri gereklidir.',
            ];
        }

        $assistant = app(SystemToolsAiAssistant::class);
        $aiSuggestions = $assistant->suggestCountryDetails($nameTr !== '' ? $nameTr : $code);

        if ($code === '') {
            $code = $aiSuggestions['code'];
        }
        if ($nameTr === '') {
            $nameTr = $aiSuggestions['name_tr'];
        }
        if ($emoji === '') {
            $emoji = $aiSuggestions['emoji'];
        }
        if ($currency === '') {
            $currency = $aiSuggestions['default_currency'];
        }

        $country = Country::query()->updateOrCreate(
            ['code' => $code],
            [
                'name_tr' => $nameTr,
                'emoji' => $emoji,
                'default_currency' => $currency,
                'is_active' => $isActive,
            ]
        );

        return [
            'durum' => 'basarili',
            'mesaj' => "Ülke kaydedildi: {$emoji} {$nameTr} ({$code})",
            'ulke' => $country->only(['code', 'name_tr', 'emoji', 'default_currency', 'is_active']),
        ];
    }

    /** @return array<string, mixed> */
    private function paraBirimleriListele(): array
    {
        $currencies = Currency::query()
            ->orderBy('sort_order')
            ->get(['code', 'name', 'symbol', 'is_active', 'sort_order']);

        return [
            'durum' => 'basarili',
            'toplam' => $currencies->count(),
            'aktif_sayisi' => $currencies->where('is_active', true)->count(),
            'para_birimleri' => $currencies->toArray(),
        ];
    }

    /** @return array<string, mixed> */
    private function demoKapisiDurumu(Request $request): array
    {
        if ($request->has('aktif')) {
            $yeni = (bool) $request->get('aktif');
            Settings::set('demo.mcp_acik', $yeni ? '1' : '0');
        }

        $acik = Settings::get('demo.mcp_acik') === '1';

        return [
            'durum' => 'basarili',
            'mcp_demo_kapisi_acik' => $acik,
            'aciklama' => $acik
                ? 'Yapay zekâ asistanları örnek (demo) veri üretebilir.'
                : 'Yapay zekâ örnek veri üretimi kilitlidir (panelden veya bu araçla açılabilir).',
        ];
    }
}
