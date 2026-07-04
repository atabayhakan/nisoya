<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * "Nisoya Nabzı": topluluk büyüme hedefi (bu ay X yeni üye/ilan) ve
 * şehir elçileri (bu ay en çok davet eden kişi, şehir başına) verilerini
 * hesaplar. Admin panelinden (İçerik → Nisoya Nabzı) yönetilir.
 */
class NabizService
{
    private const CACHE_TTL_SECONDS = 300;

    /**
     * Topluluk hedefinin ilerleme durumu. Hedef sayı 0/boşsa null döner
     * (özellik admin panelinden kapatılmış demektir).
     *
     * @return array{baslik: string, mevcut: int, hedef: int, yuzde: int, odul: string}|null
     */
    public function goalProgress(): ?array
    {
        $target = (int) Settings::get('nabiz.hedef_sayi', '0');

        if ($target <= 0) {
            return null;
        }

        $metric = Settings::get('nabiz.hedef_metrik', 'yeni_uye');

        $current = Cache::remember("nabiz_progress_{$metric}", self::CACHE_TTL_SECONDS, fn () => match ($metric) {
            'yeni_ilan' => Listing::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            default => User::query()->where('created_at', '>=', now()->startOfMonth())->count(),
        });

        return [
            'baslik' => Settings::get('nabiz.hedef_baslik', 'Bu ay hedefimiz'),
            'mevcut' => $current,
            'hedef' => $target,
            'yuzde' => (int) min(100, round($current / $target * 100)),
            'odul' => Settings::get('nabiz.odul_mesaji', '') ?? '',
        ];
    }

    /**
     * Bu ay en çok üye getiren kişiler, şehir başına en iyi performans
     * gösteren kişiyle temsil edilir ("şehir elçisi").
     *
     * @return Collection<int, object{name: string, city: string, country_code: ?string, referral_count: int}>
     */
    public function cityAmbassadors(int $limit = 10): Collection
    {
        // Not: Eloquent modeli değil, düz dizi cache'lenir (bkz. AppServiceProvider'daki
        // emergencyCategories/emergencyCountries composer'ları ile aynı desen) —
        // 'database' cache sürücüsü nesne unserialize'ını varsayılan olarak
        // engelliyor (config/cache.php: serializable_classes=false), bu yüzden
        // cache'den ikinci okumada Eloquent model'leri __PHP_Incomplete_Class'a
        // dönüşür.
        $ambassadors = Cache::remember("nabiz_ambassadors_{$limit}", self::CACHE_TTL_SECONDS, function () use ($limit) {
            $inviters = User::query()
                ->select('users.id', 'users.name', 'users.city', 'users.country_code', DB::raw('COUNT(r.id) as referral_count'))
                ->join('users as r', 'r.referred_by', '=', 'users.id')
                ->where('r.created_at', '>=', now()->startOfMonth())
                ->whereNotNull('users.city')
                ->groupBy('users.id', 'users.name', 'users.city', 'users.country_code')
                ->orderByDesc('referral_count')
                ->get();

            return $inviters
                ->groupBy(fn ($u) => Str::lower(trim($u->city)))
                ->map(fn ($group) => $group->first())
                ->sortByDesc('referral_count')
                ->take($limit)
                ->values()
                ->map(fn (User $u) => $u->only(['id', 'name', 'city', 'country_code', 'referral_count']))
                ->all();
        });

        return collect($ambassadors)->map(fn (array $item) => (object) $item);
    }
}
