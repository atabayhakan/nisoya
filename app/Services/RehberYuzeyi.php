<?php

namespace App\Services;

use App\Models\Country;
use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Models\User;
use App\Models\YasamKategorisi;
use App\Models\YasamKonuIcerigi;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Rehberin ana sayfa/footer yüzeyi için okuma katmanı (F2).
 *
 * İki soruya cevap verir: (1) hangi ülkelerin rehberi GERÇEKTEN hazır —
 * yani en az bir aktif temsilcilikte en az bir YAYINDA işlem var (K7: taslak
 * içerik yüzeyde yokmuş gibi davranır); (2) ziyaretçiye hangi ülke gösterilir.
 *
 * Ülke önceliği K1'in kendisi: üye için ikamet (users.country_code) > GeoIP;
 * misafir için GeoIP. Acil butonundaki "fiilî konum > profil" önceliğinin
 * bilinçli TERSİ — tatildeki kullanıcıya tatil ülkesinin konsolosluğu
 * gösterilmez. Çözülen ülke hazır değilse bölüm hazır ülkeleri önerir;
 * varsayılan DAYATILMAZ.
 *
 * YAŞAM REHBERİ (2026-08-22, F2) — `yasamHazirUlkeler()`/`yasamOzeti()` aynı
 * sınıfta yaşıyor, AYRI bir servis açılmadı: tasarım belgesinin kararı
 * "mevcut rehber bölümü genişletilir" — iki rehber tek ana sayfa yüzeyinin
 * parçası. Yayın kapısı ve K1 önceliği BİREBİR aynı desen, yalnız model
 * farklı.
 */
class RehberYuzeyi
{
    public function __construct(private readonly VisitorLocationService $konum) {}

    /**
     * Rehberi hazır (yayında içeriği olan) aktif ülkeler, menü sırasıyla.
     *
     * @return Collection<int, Country>
     */
    public function hazirUlkeler(): Collection
    {
        // whereHas kapanışlarında `yayinda()` scope'u yerine sabitle durum
        // filtresi: larastan iç içe whereHas kapanışının model tipini
        // çözemiyor, scope çağrısı "undefined method" sayılıyor. Değerin
        // tek kaynağı yine TemsilcilikIslemi::STATUS_YAYIN sabiti.
        return Country::query()
            ->where('is_active', true)
            ->whereHas('temsilcilikler', fn ($q) => $q->where('is_active', true)
                ->whereHas('islemler', fn ($i) => $i->where('status', TemsilcilikIslemi::STATUS_YAYIN)))
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Rehberde en azından bir temsilcilik kaydı olan aktif ülkeler — yayında
     * İŞLEM şartı YOK (hazirUlkeler()'in tersine). Nisoya AI arama
     * çubuğunun ülke listesi buradan gelir: model "Avustralya" gibi bir
     * ismi koda çevirebilsin diye, o ülkenin işlem içeriği olmasa bile
     * listede olması yeterli — RehberDogalDilArama::temsilciklerleBul() o
     * ülkede zaten temsilciliğin kendisini önerebiliyor.
     *
     * Bu ayrım olmadan (2026-08-20, canlıda ölçüldü): 55 yeni iskelet ülke
     * hazirUlkeler()'de hiç görünmediği için AI'nin ülke listesinde de hiç
     * yoktu — "Avustralya'da büyükelçilik nerede" sorusu model tarafından
     * hiçbir ülke koduna bağlanamıyordu.
     *
     * @return Collection<int, Country>
     */
    public function kapsananUlkeler(): Collection
    {
        return Country::query()
            ->where('is_active', true)
            ->whereHas('temsilcilikler', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * K1 çözümü: üye ikameti > GeoIP; ikisi de yoksa null.
     * Dönen kod HAZIRLIK garantisi taşımaz — çağıran hazır listesiyle eşler.
     */
    public function cozulenUlkeKodu(?User $uye, Request $request): ?string
    {
        $kod = trim((string) $uye?->country_code);

        if ($kod === '') {
            $kod = (string) $this->konum->resolve($request)?->code;
        }

        return $kod !== '' ? strtoupper($kod) : null;
    }

    /**
     * Seçili ülkenin bölümde gösterilecek özeti: temsilcilik sayısı +
     * yayında içeriği olan işlem türleri (çipler).
     *
     * @return array{temsilcilikSayisi: int, islemTurleri: Collection<int, IslemTuru>}
     */
    public function ulkeOzeti(Country $country): array
    {
        return [
            'temsilcilikSayisi' => Temsilcilik::query()
                ->aktif()
                ->where('country_code', $country->code)
                ->whereHas('islemler', fn ($q) => $q->where('status', TemsilcilikIslemi::STATUS_YAYIN))
                ->count(),
            'islemTurleri' => IslemTuru::query()
                ->aktif()
                ->whereHas('islemler', fn ($q) => $q->where('status', TemsilcilikIslemi::STATUS_YAYIN)
                    ->whereHas('temsilcilik', fn ($t) => $t->where('is_active', true)
                        ->where('country_code', $country->code)))
                ->orderBy('sort_order')
                ->orderBy('ad')
                ->limit(6)
                ->get(),
        ];
    }

    /**
     * Yaşam Rehberi'nin hazır (yayında içeriği olan) aktif ülkeleri.
     * `hazirUlkeler()`'in Yaşam Rehberi karşılığı — aynı K7 mantığı.
     *
     * @return Collection<int, Country>
     */
    public function yasamHazirUlkeler(): Collection
    {
        return Country::query()
            ->where('is_active', true)
            ->whereHas('yasamIcerikleri', fn ($q) => $q->where('status', YasamKonuIcerigi::STATUS_YAYIN))
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Seçili ülkenin Yaşam Rehberi özeti: yayında içeriği olan kategoriler,
     * her biri kaç konu taşıdığıyla. `ulkeOzeti()`'nin Yaşam Rehberi karşılığı.
     *
     * @return Collection<int, array{kategori: YasamKategorisi, konuSayisi: int<0, max>}>
     */
    public function yasamOzeti(Country $country): Collection
    {
        return YasamKategorisi::query()
            ->aktif()
            ->whereHas(
                'konular.icerikler',
                fn ($q) => $q->where('status', YasamKonuIcerigi::STATUS_YAYIN)->where('country_code', $country->code)
            )
            ->orderBy('sort_order')
            ->get()
            ->map(fn (YasamKategorisi $kategori) => [
                'kategori' => $kategori,
                'konuSayisi' => $kategori->konular()
                    ->whereHas(
                        'icerikler',
                        fn ($q) => $q->where('status', YasamKonuIcerigi::STATUS_YAYIN)->where('country_code', $country->code)
                    )
                    ->count(),
            ]);
    }

    /**
     * Ülkesiz "Rehbere git" linkleri için hedef (footer/menü, F3).
     *
     * Ziyaretçinin kendi ülkesi hazırsa oraya gider; değilse (tespit
     * edilemedi ya da rehberi boşsa) `varsayilanUlkeKodu()`'na düşer —
     * anasayfa widget'ının (`rehberVerisi()`) zaten kullandığı çözünürlükle
     * aynı öncelik, yalnız tek bir ülke koduna indirgenmiş hâli.
     */
    public function girisNoktasiUlkeKodu(?User $uye, Request $request): ?string
    {
        $cozulenKod = $this->cozulenUlkeKodu($uye, $request);

        if ($cozulenKod !== null && $this->hazirUlkeler()->contains('code', $cozulenKod)) {
            return strtolower($cozulenKod);
        }

        return $this->varsayilanUlkeKodu();
    }

    /**
     * Footer bağlantısının hedefi: ilk hazır ülkenin küçük harfli kodu.
     *
     * Footer her sayfada basıldığı için DOLU sonuç kısa süre önbelleklenir.
     * BOŞ sonuç bilerek önbelleklenmez: sahip ilk içeriği yayına aldığı an
     * bağlantı belirmeli — "10 dakika sonra gel" diyen bir yayın düğmesi,
     * sahibin gözünde çalışmayan bir düğmedir. Boş hâlin bedeli sayfa başına
     * tek ucuz sorgu ve yalnız rehber tamamen boşken ödenir.
     */
    public function varsayilanUlkeKodu(): ?string
    {
        $kod = Cache::get('rehber.varsayilan-ulke');

        if (! is_string($kod) || $kod === '') {
            $kod = strtolower((string) $this->hazirUlkeler()->first()?->code);

            if ($kod !== '') {
                Cache::put('rehber.varsayilan-ulke', $kod, 600);
            }
        }

        return $kod !== '' ? $kod : null;
    }
}
