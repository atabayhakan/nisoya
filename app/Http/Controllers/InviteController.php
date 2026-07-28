<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NabizService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InviteController extends Controller
{
    public function index(Request $request, NabizService $nabiz): View
    {
        $user = $request->user();

        // Davet sistemi öncesi kaydolan kullanıcıların kodu olmayabilir; üret.
        if (empty($user->referral_code)) {
            $user->referral_code = User::generateReferralCode();
            $user->save();
        }

        return view('panel.davet', [
            'user' => $user,
            'invitedCount' => $user->referrals()->count(),
            'invited' => $user->referrals()->latest()->take(20)->get(['name', 'username', 'created_at']),
            // Davet sayfasi ile /nabiz Sehir Elcileri birbirinden habersizdi:
            // kullanici davet sayisini goruyor ama bunun bir seye yaradigini
            // hicbir yerde okumuyordu. Var olan taninma katmanini gorunur
            // kilmak, yeni bir rozet eklemekten once gelir.
            'elcilik' => $nabiz->sehirElciligiDurumu($user),
        ]);
    }
}
