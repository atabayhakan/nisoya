<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => route('listings.index'), 'priority' => '0.9'],
            ['loc' => route('pages.how'), 'priority' => '0.4'],
            ['loc' => route('pages.about'), 'priority' => '0.3'],
            ['loc' => route('pages.faq'), 'priority' => '0.3'],
        ];

        foreach (Category::query()->where('is_active', true)->get() as $category) {
            $urls[] = ['loc' => route('listings.category', $category->slug), 'priority' => '0.7'];
        }

        foreach (Listing::query()->active()->latest()->limit(1000)->get() as $listing) {
            $urls[] = [
                'loc' => route('listings.show', [$listing, $listing->slug]),
                'lastmod' => $listing->updated_at?->toAtomString(),
                'priority' => '0.8',
            ];
        }

        foreach (User::query()->where('status', 'aktif')->whereNotNull('username')
            ->whereHas('listings', fn ($q) => $q->active())->get() as $user) {
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
