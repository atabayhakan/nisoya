<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\Listing;
use App\Models\Page;
use App\Models\User;
use App\Support\Modules;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
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
            ->withCount(['listings' => fn ($q) => $q->active()])
            ->get();

        foreach ($kategoriler as $category) {
            if ($category->listings_count === 0) {
                continue;
            }

            $urls[] = ['loc' => route('listings.category', $category->slug), 'priority' => '0.7'];
        }

        foreach (Listing::query()->active()->latest()->limit(1000)->get() as $listing) {
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

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
