<?php

namespace App\Services;

use App\Enums\StoryStatus;
use App\Models\Country;
use App\Models\Listing;
use App\Models\Story;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Carbon;
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

    /**
     * Bir kullanıcının kendi şehrindeki elçilik durumu — davet sayfasında
     * gösterilir.
     *
     * NEDEN VAR: sitede iki ayrı tanınma yüzeyi vardı ve birbirlerinden
     * habersizdiler. `/panel/davet` davet sayısını gösteriyor ama bu sayının
     * bir şeye yaradığını söylemiyordu; `/nabiz` "şehrinin elçisi ol" diyor
     * ama davet sayfasına hiç bağlanmıyordu. Kullanıcının davet etmesi için
     * bir sebep, tam davet edeceği ekranda görünmüyordu.
     *
     * Yeni bir ödül katmanı EKLEMEK yerine var olanı görünür kılıyoruz: aylık
     * davet sayısı zaten `cityAmbassadors()` ölçütü, burada aynı ölçüt tek
     * kullanıcı için hesaplanıyor.
     *
     * Şehri olmayan kullanıcı için `sehir` null döner — elçilik şehir başına
     * olduğu için o kullanıcı yarışta değildir ve arayüz bunu bilmelidir.
     *
     * @return array{sehir: ?string, benimBuAy: int, lider: int, elciMiyim: bool, fark: int}
     */
    public function sehirElciligiDurumu(User $user): array
    {
        $sehir = trim((string) $user->city);

        $benimBuAy = User::query()
            ->where('referred_by', $user->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        if ($sehir === '') {
            return ['sehir' => null, 'benimBuAy' => $benimBuAy, 'lider' => 0, 'elciMiyim' => false, 'fark' => 0];
        }

        // Aynı şehirdeki en yüksek aylık davet sayısı. cityAmbassadors() ile
        // aynı ölçüt; orada şehir başına tek temsilci seçilir, burada yalnız
        // eşiği öğreniyoruz. Cache YOK: bu sayfa kullanıcıya özeldir ve
        // kullanıcı kendi davetinin etkisini ANINDA görmeli.
        // Cast PARANTEZ İÇİNDE: `(int) ... ?? 0` yazımında cast `??`'den önce
        // bağlanır, sol taraf hiç null olamaz ve `?? 0` ölü koda dönüşür —
        // şehirde hiç davet yoksa value() null döner ve (int) null = 0 olur,
        // yani sonuç tesadüfen doğrudur ama ifade yanıltıcıdır.
        $lider = (int) (User::query()
            ->join('users as r', 'r.referred_by', '=', 'users.id')
            ->whereRaw('LOWER(TRIM(users.city)) = ?', [mb_strtolower($sehir)])
            ->where('r.created_at', '>=', now()->startOfMonth())
            ->groupBy('users.id')
            ->selectRaw('COUNT(r.id) as adet')
            ->orderByDesc('adet')
            ->value('adet') ?? 0);

        return [
            'sehir' => $sehir,
            'benimBuAy' => $benimBuAy,
            'lider' => $lider,
            'elciMiyim' => $benimBuAy > 0 && $benimBuAy >= $lider,
            'fark' => max(0, $lider - $benimBuAy) + 1,
        ];
    }

    /**
     * "Nabız Haritası" (Faz İ2, "2. Tasarım" pilotu) için ülke başına aktif
     * ilan sayısı — ülkenin gerçek enlem/boylamı basit bir eşdik-açılı
     * (equirectangular) izdüşümle 0-1 aralığında x/y'ye çevrilir. Süs değil,
     * gerçek veri: nokta boyutu/parlaklığı o ülkedeki aktif ilan sayısını
     * yansıtır (bkz. components/pulse-map.blade.php).
     *
     * @return array<int, array{code: string, name: string, emoji: ?string, x: float, y: float, count: int}>
     */
    public function countryActivity(): array
    {
        // Anahtar sürümlü (v2): demo süzgeci eklendiğinde eski anahtarda
        // KİRLİ sayı duruyordu ve TTL dolana kadar canlıda görünmeye devam
        // ederdi. Sürüm artırmak, elle önbellek temizlemeyi gereksiz kılar.
        return Cache::remember('nabiz_country_activity_v2', self::CACHE_TTL_SECONDS, function () {
            // DEMO SAYILMAZ — 2026-08-05'te canlıda bulunan kural ihlali.
            //
            // Bu süzgeç eksikken ana sayfada 100 piksel arayla iki öge
            // birbirinin tersini söylüyordu: kanıt şeridi "şehrinde ilk ilanı
            // sen ver" (demo süzülü, gerçek envanter az) derken hemen altındaki
            // ülke satırı "Almanya 12 aktif ilan" diyordu (demo dahil).
            //
            // Sızıntı üç yerdeydi: mobil kanıt şeridi, masaüstü "şu an nerede
            // ilan var" kartı ve nabız haritası. Haritanın altında aynen
            // "temsili değil, gerçek veri" yazıyor — yani sayfa sahte bir
            // sayının altına gerçek diyordu.
            //
            // Kural zaten vardı ve başka her yerde uygulanıyordu (Kâhya
            // teşhisi, kanıt şeridi, süreç şeridi, belgeler): demo veri hiçbir
            // sayıya girmez. Burada unutulmuştu.
            $counts = Listing::query()->active()
                ->where('is_demo', false)
                ->whereNotNull('country_code')
                ->select('country_code', DB::raw('count(*) as toplam'))
                ->groupBy('country_code')
                ->pluck('toplam', 'country_code');

            return Country::query()
                ->where('is_active', true)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->orderBy('sort_order')
                ->get(['code', 'name_tr', 'emoji', 'latitude', 'longitude'])
                ->map(fn (Country $country) => [
                    'code' => $country->code,
                    'name' => $country->name_tr,
                    'emoji' => $country->emoji,
                    'x' => round(($country->longitude + 180) / 360, 4),
                    'y' => round((90 - $country->latitude) / 180, 4),
                    'count' => (int) ($counts[$country->code] ?? 0),
                ])
                ->values()
                ->all();
        });
    }

    /** "Gurbet Günlüğü" hikaye duvarı admin panelden açık mı? */
    public function storyWallEnabled(): bool
    {
        return Settings::get('nabiz.hikaye_duvari_aktif', '0') === '1';
    }

    /**
     * Onaylanmış hikayeler — tamamen anonim (sorgu bile user_id/isim
     * çekmiyor, sadece body+tarih; herhangi bir kimlik sızıntısını
     * kaynağında engeller).
     *
     * Not: created_at Carbon nesnesi de cache'lenmeden önce string'e
     * çevrilir — aksi halde Eloquent modelleri gibi Carbon da
     * serializable_classes=false ile ikinci okumada bozulur.
     *
     * @return Collection<int, object{body: string, created_at: Carbon}>
     */
    public function approvedStories(int $limit = 12): Collection
    {
        $stories = Cache::remember("nabiz_stories_{$limit}", self::CACHE_TTL_SECONDS, fn () => Story::query()
            ->where('status', StoryStatus::Onaylandi)
            ->latest()
            ->take($limit)
            ->get(['body', 'created_at'])
            ->map(fn (Story $story) => [
                'body' => $story->body,
                'created_at' => $story->created_at->toIso8601String(),
            ])
            ->all());

        return collect($stories)->map(fn (array $item) => (object) [
            'body' => $item['body'],
            'created_at' => Carbon::parse($item['created_at']),
        ]);
    }

    /** Kullanıcının en son gönderdiği hikayenin durumu (hiç göndermediyse null). */
    public function latestStoryStatusFor(User $user): ?StoryStatus
    {
        return $user->stories()->latest()->first()?->status;
    }
}
