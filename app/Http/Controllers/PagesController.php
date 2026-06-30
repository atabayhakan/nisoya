<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PagesController extends Controller
{
    public function nasilCalisir(): View
    {
        return view('pages.nasil-calisir');
    }

    public function hakkimizda(): View
    {
        return view('pages.hakkimizda');
    }

    public function iletisim(): View
    {
        return view('pages.iletisim');
    }

    public function kosullar(): View
    {
        return view('pages.kosullar');
    }

    public function gizlilik(): View
    {
        return view('pages.gizlilik');
    }

    public function sss(): View
    {
        return view('pages.sss');
    }
}
