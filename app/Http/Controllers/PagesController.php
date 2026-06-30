<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PagesController extends Controller
{
    public function nasilCalisir(): View
    {
        return view('pages.nasil-calisir');
    }

    public function iletisim(): View
    {
        return view('pages.iletisim');
    }
}
