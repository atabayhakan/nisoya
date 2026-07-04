<?php

namespace App\Http\Controllers;

use App\Services\NabizService;
use Illuminate\View\View;

class NabizController extends Controller
{
    public function index(NabizService $nabiz): View
    {
        return view('nabiz', [
            'goal' => $nabiz->goalProgress(),
            'ambassadors' => $nabiz->cityAmbassadors(20),
        ]);
    }
}
