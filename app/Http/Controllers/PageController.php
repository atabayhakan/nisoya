<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\SssSorusu;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        /*
         * SSS 2026-08-25'te yapılandırıldı: içerik artık `SssSorusu`'da,
         * `$page->blocks` DEĞİL (bkz. StaticPagesSeeder yorumu). Bu satır
         * olmadan panelde `blocks`'a yazılan hiçbir şey görünmezdi — sessiz
         * sürüklenme tuzağının (bkz. PageForm uyarısı) diğer yarısı burası.
         */
        if ($slug === 'sss') {
            return view('pages.sss', [
                'page' => $page,
                'sorular' => SssSorusu::query()->aktif()->orderBy('sort_order')->get(),
            ]);
        }

        return view('pages.dynamic', ['page' => $page]);
    }
}
