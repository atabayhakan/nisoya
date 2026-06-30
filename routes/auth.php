<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('kayit', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('kayit', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1');

    Route::get('giris', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('giris', [AuthenticatedSessionController::class, 'store']);

    // Sosyal giriş (Google / Facebook)
    Route::get('giris/{provider}', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('giris/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

    Route::get('sifremi-unuttum', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('sifremi-unuttum', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('sifre-sifirla/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('sifre-sifirla', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('eposta-dogrula', EmailVerificationPromptController::class)->name('verification.notice');

    Route::get('eposta-dogrula/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('eposta-dogrula/gonder', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('cikis', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
