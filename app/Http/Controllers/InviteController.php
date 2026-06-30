<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InviteController extends Controller
{
    public function index(Request $request): View
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
        ]);
    }
}
