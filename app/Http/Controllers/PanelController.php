<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\DealStatus;
use App\Models\Deal;
use App\Models\JobApplication;
use App\Models\Listing;
use App\Models\User;
use App\Support\Modules;
use App\Support\PanelSinyalleri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Kullanıcı paneli (/panel).
 *
 * Bu rota eskiden `Route::view` idi: sayfaya HİÇBİR VERİ geçmiyordu, 14 kart
 * Blade içinde elle yazılmış sabit bir diziydi ve 3 okunmamış mesajı olan
 * kullanıcı ile dün kaydolmuş kullanıcı birebir aynı ekranı görüyordu.
 *
 * SORGU SÖZLEŞMESİ: panel EN FAZLA +6 sorgu ekler. Her zaman 3 (sayaçlar,
 * ilan özeti, anlaşmalar), koşullu en fazla 3 (karşı taraf adları, gelen
 * başvuru, boş durumdaki ülke ilanları). Azaltma defteri için bkz.
 * App\Support\PanelSinyalleri docblock'u.
 */
class PanelController extends Controller
{
    /** Öne çıkarması kaç gün içinde biterse "bitiyor" sayılır. */
    private const ONE_CIKARMA_UYARI_GUN = 3;

    /** Bekleyenler listesinde en fazla kaç anlaşma satırı basılır. */
    private const BEKLEYEN_SATIR_TAVANI = 5;

    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $isModulu = Modules::enabled('is_ilanlari');
        $davetiyeModulu = Modules::enabled('davetiye');

        $sayaclar = $this->sayaclar($user, $isModulu, $davetiyeModulu);
        $ilan = $this->ilanOzeti($user);
        [$bekleyenSatirlar, $dahaFazlasiVar] = $this->bekleyenAnlasmalar($user);

        $sirketVar = (bool) ($sayaclar->company_exists ?? false);

        $sinyaller = new PanelSinyalleri(
            bekleyenSatirlar: $bekleyenSatirlar,
            dahaFazlasiVar: $dahaFazlasiVar,
            // 0 EK SORGU: bu iki sayı zaten header zilinde ve mobil sekme
            // çubuğunda koşuyor; User içindeki istek-başı memo sayesinde
            // panel onları bedavaya okur.
            okunmamisMesaj: $user->okunmamisMesajSayisi(),
            okunmamisBildirim: $user->okunmamisBildirimSayisi(),
            gelenYeniBasvuru: $sirketVar && $isModulu ? $this->gelenYeniBasvuru($user) : 0,
            oneCikarmaBitiyor: (int) $ilan->one_cikarma_bitiyor,
            aktifIlan: (int) $ilan->aktif,
            bekleyenIlan: (int) $ilan->beklemede,
            pasifIlan: (int) $ilan->pasif,
            toplamIlan: (int) $ilan->toplam,
            toplamGoruntulenme: (int) $ilan->goruntulenme,
            favori: (int) ($sayaclar->favorites_count ?? 0),
            kayitliArama: (int) ($sayaclar->saved_searches_count ?? 0),
            basvuru: (int) ($sayaclar->job_applications_count ?? 0),
            gorusme: (int) ($sayaclar->gorusme_count ?? 0),
            yaklasanDavetiye: (int) ($sayaclar->yaklasan_etkinlik_count ?? 0),
            davet: (int) ($sayaclar->referrals_count ?? 0),
            sirketVar: $sirketVar,
            isModulu: $isModulu,
            davetiyeModulu: $davetiyeModulu,
            profilEksikleri: $this->profilEksikleri($user),
            yetenekHavuzuKapali: $isModulu && ! $user->is_searchable,
        );

        // Yalnız gerçekten boş panoda: kullanıcının kendi ülkesinden 3 gerçek
        // ilan. Hiçbir ilişki eager yüklenmez (1 sorgu). Ülkede ilan yoksa
        // global/rastgele ilanla DOLDURULMAZ — boş kalır ve öyle söylenir.
        if ($sinyaller->bosDurum() && $user->country_code) {
            // gercek(): bu liste rozet BASMIYOR (yalnız başlık/fiyat/şehir
            // seçiliyor), yani örnek ilan burada işaretsiz gerçek arz gibi
            // okunur. Üstelik gösterildiği an, yeni üyenin panosunu ilk kez
            // açtığı an — sitenin ilk vaadi "ülkende hareket var" olur ve
            // tıklayınca örnek çıkar. Yukarıdaki yorum zaten "3 GERÇEK ilan"
            // diyordu; sorgu bunu söylemiyordu.
            $sinyaller->ulkeIlanlari = Listing::query()->active()->gercek()
                ->where('country_code', $user->country_code)
                ->latest()
                ->limit(3)
                ->get(['id', 'slug', 'title', 'price', 'currency', 'city', 'country_code']);
        }

        return view('panel.dashboard', ['sinyaller' => $sinyaller]);
    }

    /**
     * S1 — tüm sayaçlar + şirket varlığı TEK sorguda.
     *
     * Rol üyeliği bu sorgunun YAN ÜRÜNÜDÜR; ayrı bir "rol tespit" sorgusu
     * yoktur. Kapalı modülün sayacı hiç sorulmaz.
     *
     * withExists('company') bilinçli: isEmployer()/account_type gösterim
     * kararında KULLANILMAZ. account_type şirket oluşturulunca bir kez
     * Kurumsal'a çevrilip geri alınmıyor, şirket ise yönetim panelinden
     * silinebiliyor — yani ikisi de gerçeği söylemeyebilir. withExists
     * hem bedava (aynı sorgu) hem varsayımsız.
     */
    private function sayaclar(User $user, bool $isModulu, bool $davetiyeModulu): User
    {
        $sayimlar = ['favorites', 'savedSearches', 'referrals'];

        if ($isModulu) {
            $sayimlar[] = 'jobApplications';
            $sayimlar['jobApplications as gorusme_count'] = fn ($q) => $q->where('status', ApplicationStatus::Gorusme->value);
        }

        if ($davetiyeModulu) {
            $sayimlar['events as yaklasan_etkinlik_count'] = fn ($q) => $q->where('starts_at', '>=', now());
        }

        /** @var User $sonuc */
        $sonuc = User::query()
            ->whereKey($user->id)
            ->withExists('company')
            ->withCount($sayimlar)
            ->first();

        return $sonuc;
    }

    /**
     * S2 — ilan özeti TEK TARAMA, agregat-only.
     *
     * GROUP BY KULLANILMAZ: MySQL'in only_full_group_by kipi yerelde SQLite'ta
     * sessizce geçip üretimde 500 verir (aynı tuzağa Event::rsvpSummary'de
     * düşülmüştü). Ayrıca S1'e katılmadı çünkü 5 ayrı withCount alt seçimi
     * listings tablosunu 5 KEZ tarardı; "1 sorgu" metriği bunu gizler.
     */
    private function ilanOzeti(User $user): object
    {
        $sinir = now()->addDays(self::ONE_CIKARMA_UYARI_GUN);

        /** @var object $satir */
        $satir = Listing::query()
            ->where('user_id', $user->id)
            ->toBase()
            ->selectRaw('COUNT(*) as toplam')
            ->selectRaw("SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif")
            ->selectRaw("SUM(CASE WHEN status = 'beklemede' THEN 1 ELSE 0 END) as beklemede")
            ->selectRaw("SUM(CASE WHEN status = 'pasif' THEN 1 ELSE 0 END) as pasif")
            ->selectRaw('COALESCE(SUM(views_count), 0) as goruntulenme')
            ->selectRaw('SUM(CASE WHEN featured_until IS NOT NULL AND featured_until > ? AND featured_until <= ? THEN 1 ELSE 0 END) as one_cikarma_bitiyor', [now(), $sinir])
            ->first();

        return $satir;
    }

    /**
     * S3 (+S4) — eylem bekleyen anlaşmalar.
     *
     * SATIR olarak basılır, SAYIYA ÇEVRİLMEZ: sorgu limit'li olduğu için
     * "6" demek yanlış olurdu (gerçekte 40 olabilir). Tavanı aşarsa sonda
     * sayısız "…ve daha fazlası" satırı çıkar.
     *
     * proposed_by <> ben filtresi ŞART: teklifi yapan taraf için o teklif
     * eylem değil bekleyiştir; filtresiz basmak sahte aciliyet üretir
     * (DealController@accept zaten yalnız karşı tarafın kabulüne izin veriyor).
     *
     * @return array{0: array<int, array<string, mixed>>, 1: bool}
     */
    private function bekleyenAnlasmalar(User $user): array
    {
        $anlasmalar = Deal::query()
            ->where(fn ($q) => $q->where('seller_id', $user->id)->orWhere('buyer_id', $user->id))
            ->where(function ($q) use ($user) {
                $q->where('status', DealStatus::Sorunlu->value)
                    ->orWhere(fn ($s) => $s->where('status', DealStatus::Teklif->value)->where('proposed_by', '!=', $user->id))
                    ->orWhere(fn ($s) => $s->where('status', DealStatus::Tamamlandi->value)
                        ->whereNotExists(fn ($r) => $r->select(DB::raw(1))
                            ->from('reviews')
                            ->whereColumn('reviews.deal_id', 'deals.id')
                            ->where('reviews.reviewer_id', $user->id)
                        )
                    );
            })
            ->latest('updated_at')
            ->limit(self::BEKLEYEN_SATIR_TAVANI + 1)
            ->get(['id', 'conversation_id', 'seller_id', 'buyer_id', 'status', 'amount', 'currency']);

        if ($anlasmalar->isEmpty()) {
            return [[], false];
        }

        $dahaFazlasiVar = $anlasmalar->count() > self::BEKLEYEN_SATIR_TAVANI;
        $anlasmalar = $anlasmalar->take(self::BEKLEYEN_SATIR_TAVANI);

        // S4 — karşı taraf adları. COALESCE/leftJoin KULLANILMAZ: seller_id ve
        // buyer_id nullable değil, COALESCE her zaman ilkini döndürür ve
        // kullanıcıyı KENDİ profiline yollardı.
        $karsiIdler = $anlasmalar
            ->map(fn (Deal $d) => $d->seller_id === $user->id ? $d->buyer_id : $d->seller_id)
            ->unique()
            ->all();

        $kisiler = User::query()->whereIn('id', $karsiIdler)->get(['id', 'name', 'username'])->keyBy('id');

        $satirlar = $anlasmalar->map(function (Deal $d) use ($user, $kisiler) {
            $karsiId = $d->seller_id === $user->id ? $d->buyer_id : $d->seller_id;
            $karsi = $kisiler->get($karsiId);

            // instanceof, nullsafe DEĞİL: karşı taraf silinmiş olabilir ve o
            // durumda satır yine basılmalı (anlaşma duruyor). Koleksiyonun
            // tipi non-null göründüğü için nullsafe burada yanıltıcı olurdu.
            return [
                'tur' => match ($d->status) {
                    DealStatus::Sorunlu => 'sorunlu',
                    DealStatus::Teklif => 'teklif',
                    default => 'degerlendirme',
                },
                'konusmaId' => $d->conversation_id,
                'karsiTarafAd' => $karsi instanceof User ? $karsi->name : 'Silinmiş kullanıcı',
                'karsiTarafKullaniciAdi' => $karsi instanceof User ? $karsi->username : null,
                'tutar' => $d->amount !== null ? number_format((float) $d->amount, 0) : null,
                'currency' => $d->currency,
            ];
        })->values()->all();

        return [$satirlar, $dahaFazlasiVar];
    }

    /** S5 — işverene gelen, henüz incelenmemiş başvurular. */
    private function gelenYeniBasvuru(User $user): int
    {
        return JobApplication::query()
            ->where('status', ApplicationStatus::Gonderildi->value)
            ->whereHas('jobListing.company', fn ($q) => $q->where('user_id', $user->id))
            ->count();
    }

    /**
     * Profil eksikleri — 0 sorgu, users satırından okunur.
     *
     * YÜZDE GÖSTERİLMEZ, kesir gösterilir ("2 adımdan 1'i tamam"): yüzde,
     * arkasında olmayan bir kesinlik iması taşır. country_code kayıtta zorunlu
     * olduğu için adım sayılmaz.
     *
     * @return array<int, string>
     */
    private function profilEksikleri(User $user): array
    {
        $eksikler = [];

        if (blank($user->avatar_path)) {
            $eksikler[] = 'Profil fotoğrafı ekle';
        }

        if (blank($user->bio)) {
            $eksikler[] = 'Kendini bir cümleyle anlat';
        }

        return $eksikler;
    }
}
