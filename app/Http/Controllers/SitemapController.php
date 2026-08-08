<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\Listing;
use App\Models\Page;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Üretim maliyeti onlarca sorgu ve URL seti rehber sayfalarıyla büyüdü —
     * 15 dk cache, tarayıcıların (Google birkaç günde bir gelir) tazelik
     * ihtiyacını fazlasıyla karşılar. Testler tek istekle doğrular; içerik
     * değişimi en geç 15 dk içinde sitemap'e yansır.
     */
    private const CACHE_SANIYE = 900;

    public function index(): Response
    {
        /*
         * Anahtar modül durumunu İÇERİR: panelden bir modül kapatılınca URL'leri
         * sitemap'ten ANINDA düşer (TTL beklenmez, elle flush gerekmez). İçerik
         * değişimleri (yeni ilan/sayfa) ise TTL ile en geç 15 dk'da yansır.
         */
        $anahtar = 'sitemap.xml:'.md5(json_encode(Modules::all()) ?: '');

        $xml = Cache::remember($anahtar, self::CACHE_SANIYE, fn (): string => $this->uret());

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private function uret(): string
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => route('listings.index'), 'priority' => '0.9'],
            ['loc' => route('happy-moments'), 'priority' => '0.5'],
            ['loc' => route('pages.how'), 'priority' => '0.4'],
            ['loc' => route('pages.contact'), 'priority' => '0.3'],
            // '/gizlilik' artık CMS'teki bir Page kaydı — aşağıdaki döngüden gelir, burada tekrar eklenmez.
            ['loc' => url('/cerez-tercihleri'), 'priority' => '0.2'],
        ];

        // Dikey modüller — yalnızca açıksa sitemap'e ekle (kapalı modül 404 verir).
        if (Modules::enabled('emlak')) {
            $urls[] = ['loc' => route('properties.index'), 'priority' => '0.9'];
        }
        if (Modules::enabled('vasita')) {
            $urls[] = ['loc' => route('vehicles.index'), 'priority' => '0.9'];
        }
        if (Modules::enabled('is_ilanlari')) {
            $urls[] = ['loc' => route('jobs.index'), 'priority' => '0.9'];
            $urls[] = ['loc' => route('candidates.index'), 'priority' => '0.6'];
        }

        foreach (Page::query()->published()->get() as $page) {
            $urls[] = [
                'loc' => url('/'.$page->slug),
                'lastmod' => $page->updated_at?->toAtomString(),
                'priority' => '0.3',
            ];
        }

        // BOŞ KATEGORİ SAYFASI SİTEMAP'E GİRMEZ.
        //
        // 97 kategori sayfasının 93'ünde sıfır ilan vardı ve hepsi sitemap ile
        // Google'a bildiriliyordu. Aynı meta description'ı taşıyan, içeriği
        // olmayan yüzlerce URL klasik "thin content" desenidir: arama motoru
        // bunları değersiz sayar ve bu değerlendirme SİTENİN TAMAMINA yansır —
        // yani dolu olan birkaç sayfanın da sıralaması düşer. Trafik çekmeye
        // başlamak bu sayfaların daha hızlı taranması demek olduğu için,
        // temizlik yapılmadan yapılacak her SEO çalışması zararlıdır.
        //
        // withCount + having: ilanı olan kategori sayfası gerçek içeriktir ve
        // bildirilir; boş olan kategori sayfası SİLİNMEZ (kullanıcı gezinirken
        // ulaşabilmeli), yalnız arama motoruna ÖNERİLMEZ. İlan girildiği anda
        // sayfa kendiliğinden sitemap'e geri döner.
        $kategoriler = Category::query()
            ->where('is_active', true)
            //
            // gercek(): örnek ilan bir kategori sayfasını "dolu" yapmaz. Aksi
            // hâlde yukarıdaki temizliğin tamamı boşa giderdi — 97 kategorinin
            // 93'ü demo partisiyle sitemap'e geri dönerdi.
            ->withCount(['listings' => fn ($q) => $q->active()->gercek()])
            ->get();

        foreach ($kategoriler as $category) {
            if ($category->listings_count === 0) {
                continue;
            }

            $urls[] = ['loc' => route('listings.category', $category->slug), 'priority' => '0.7'];
        }

        // gercek(): ÖRNEK ilanlar arama motoruna hiç bildirilmez. Sayfaları
        // erişilebilir kalır (sahibin gezintisi için) ama kendi robots
        // etiketiyle noindex'tir — bkz. listings/show.blade.php.
        foreach (Listing::query()->active()->gercek()->latest()->limit(1000)->get() as $listing) {
            $urls[] = [
                'loc' => route('listings.show', [$listing, $listing->slug]),
                'lastmod' => $listing->updated_at?->toAtomString(),
                'priority' => '0.8',
            ];
        }

        if (Modules::enabled('is_ilanlari')) {
            foreach (JobListing::query()->active()->latest()->limit(1000)->get() as $job) {
                $urls[] = [
                    'loc' => route('jobs.show', [$job, $job->slug]),
                    'lastmod' => $job->updated_at?->toAtomString(),
                    'priority' => '0.7',
                ];
            }

            foreach (Company::query()->get() as $company) {
                $urls[] = ['loc' => route('companies.show', $company), 'priority' => '0.5'];
            }
        }

        // Aktif ilanı olan ya da Yetenek Havuzu'nda görünür olan üyeler (bkz. Nisoya Jobzilla Esinlenme Planı).
        foreach (User::query()->where('status', 'aktif')->whereNotNull('username')
            ->where(fn ($q) => $q->whereHas('listings', fn ($q2) => $q2->active())->orWhere('is_searchable', true))
            ->get() as $user) {
            $urls[] = ['loc' => route('profiles.show', $user->username), 'priority' => '0.5'];
        }

        /*
         * Ülke Rehberi — boş-kategori felsefesiyle aynı kural (yukarıdaki uzun
         * yorum): içeriği OLMAYAN sayfa arama motoruna önerilmez. Ülke sayfası
         * ancak yayında işlemi olan bir temsilcilik varsa; temsilcilik sayfası
         * ancak yayında işlemi varsa; işlem sayfası zaten yalnız yayındakiler.
         */
        if (Modules::enabled('rehber')) {
            $temsilcilikler = Temsilcilik::query()
                ->aktif()
                ->whereHas('islemler', fn ($q) => $q->where('status', TemsilcilikIslemi::STATUS_YAYIN))
                ->with(['islemler' => fn ($q) => $q->yayinda()->with('islemTuru')])
                ->get();

            foreach ($temsilcilikler->pluck('country_code')->unique() as $kod) {
                $urls[] = ['loc' => route('rehber.ulke', strtolower($kod)), 'priority' => '0.7'];
            }

            foreach ($temsilcilikler as $temsilcilik) {
                $ulke = strtolower($temsilcilik->country_code);
                $urls[] = ['loc' => route('rehber.temsilcilik', [$ulke, $temsilcilik->slug]), 'priority' => '0.6'];

                foreach ($temsilcilik->islemler as $islem) {
                    if ($islem->islemTuru === null || ! $islem->islemTuru->is_active) {
                        continue;
                    }

                    $urls[] = [
                        'loc' => route('rehber.islem', [$ulke, $temsilcilik->slug, $islem->islemTuru->slug]),
                        'lastmod' => $islem->updated_at?->toAtomString(),
                        'priority' => '0.7',
                    ];
                }
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.htmlspecialchars($url['loc'], ENT_XML1).'</loc>'."\n";
            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>'.$url['lastmod'].'</lastmod>'."\n";
            }
            $xml .= '    <priority>'.$url['priority'].'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }
        $xml .= '</urlset>'."\n";

        return $xml;
    }
}
