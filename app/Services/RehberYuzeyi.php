<?php

namespace App\Services;

use App\Models\Country;
use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Models\User;
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
