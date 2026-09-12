<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\OutreachTarget;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_ozet_metrikler')]
#[Title('Platform Özet Metrikleri — Yönetici kokpit raporu')]
#[Description(
    'Nisoya pazarının anlık sağlık ve büyüme metriklerini getirir: '.
    'Toplam ve aktif ilan sayıları, onay bekleyenler, diaspora esnafı havuzu, '.
    'kullanıcı sayıları, ülke bazlı dağılım ve aktif yapay zekâ sağlayıcı durumu.'
)]
class OzetMetrikler extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return []; // Parametresiz, doğrudan genel özet döner
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $aktifIlan = Listing::query()->where('status', ListingStatus::Aktif)->count();
        $bekleyenIlan = Listing::query()->where('status', ListingStatus::Beklemede)->count();
        $toplamIlan = Listing::query()->count();

        $toplamKullanici = User::query()->count();
        $yeniKullanici24s = User::query()->where('created_at', '>=', now()->subDay())->count();

        $kesfedilenEsnaf = OutreachTarget::query()->count();
        $sahipsizVitrin = Listing::query()->whereNotNull('claim_token')->whereNull('user_id')->count();

        // En popüler ülkeler
        $ulkeDagilimi = Listing::query()
            ->select('country_code', DB::raw('count(*) as toplam'))
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderByDesc('toplam')
            ->limit(5)
            ->pluck('toplam', 'country_code')
            ->all();

        // Aktif AI sağlayıcı bilgisi
        $aiProvider = Settings::get('ai.saglayici') ?? config('ai.default', 'anthropic');
        $aiModel = Settings::get("ai.providers.{$aiProvider}.model") ?? config("ai.providers.{$aiProvider}.model", 'bilinmiyor');

        return [
            'rapor_zamani' => now()->toIso8601String(),
            'platform' => [
                'ad' => 'Nisoya',
                'url' => config('app.url', 'https://nisoya.com'),
                'ortam' => app()->environment(),
            ],
            'ilan_istatistikleri' => [
                'aktif_ilan_sayisi' => $aktifIlan,
                'onay_bekleyen_ilanlar' => $bekleyenIlan,
                'toplam_ilan' => $toplamIlan,
                'vitrin_sahiplenme_bekleyen' => $sahipsizVitrin,
            ],
            'kullanici_istatistikleri' => [
                'toplam_uye' => $toplamKullanici,
                'son_24_saatte_kayit' => $yeniKullanici24s,
            ],
            'buyume_ve_diaspora' => [
                'havuzdaki_esnaf_sayisi' => $kesfedilenEsnaf,
                'en_aktif_ulkeler' => $ulkeDagilimi,
            ],
            'yapay_zeka_durumu' => [
                'varsayilan_saglayici' => $aiProvider,
                'aktif_model' => $aiModel,
            ],
        ];
    }
}
