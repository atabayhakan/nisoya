<?php

use App\Http\Controllers\Auth\AccountRecoveryController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\Auth\GoogleRegistrationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('kayit', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('kayit', [RegisteredUserController::class, 'store'])
        ->middleware(['honeypot', 'throttle:register']);

    Route::get('giris', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('giris', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');

    /*
     * Google ile giriş / kayıt.
     *
     * `callback` yolu config('services.google.redirect') ile HARFİ HARFİNE
     * aynı olmalı — Google Cloud Console'a kaydedilen adres de bu. Uyuşmazsa
     * Google redirect_uri_mismatch döndürür.
     *
     * Tamamlama adımı da guest grubunda: Google'dan dönen yeni kişi henüz
     * üye DEĞİL, kaydı o ekranda tamamlanıyor.
     */
    Route::get('giris/google', [GoogleLoginController::class, 'redirect'])
        ->middleware('throttle:login')
        ->name('login.google');
    Route::get('giris/google/callback', [GoogleLoginController::class, 'callback'])
        ->middleware('throttle:login')
        ->name('login.google.callback');

    Route::get('kayit/google/tamamla', [GoogleRegistrationController::class, 'create'])
        ->name('register.google.complete');
    Route::post('kayit/google/tamamla', [GoogleRegistrationController::class, 'store'])
        ->middleware('throttle:register')
        ->name('register.google.store');

    // İki adımlı doğrulama challenge'ı (parola doğrulandıktan SONRA, tam giriş
    // ÖNCESİ). Kullanıcı henüz "guest"tir; bekleyen kimlik session'da tutulur.
    // POST'a throttle: 6 haneli TOTP/yedek kod brute-force koruması.
    Route::get('iki-faktor-dogrula', [TwoFactorChallengeController::class, 'create'])->name('two-factor.login');
    Route::post('iki-faktor-dogrula', [TwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('two-factor.login.store');

    // Passkey ile giriş: uçlar artık laravel/passkeys paketinden gelir
    // (GET /passkeys/login/options + POST /passkeys/login, guest+throttle'lı;
    // yönetim uçları /user/passkeys* auth arkasında — bkz. config/passkeys.php).

    Route::get('sifremi-unuttum', [PasswordResetLinkController::class, 'create'])->name('password.request');
    // throttle: e-posta bombardımanı/enumeration denemelerine karşı IP başına 6/dk.
    Route::post('sifremi-unuttum', [PasswordResetLinkController::class, 'store'])
        ->middleware(['honeypot', 'throttle:6,1'])
        ->name('password.email');

    Route::get('sifre-sifirla/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('sifre-sifirla', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.store');

    // Hesap kurtarma — e-posta (SMTP) erişilemezken tek-kullanımlık kurtarma
    // koduyla parola sıfırlama. Kodlar Kurtarma Kiti sayfasından üretilir (G2).
    Route::get('hesap-kurtar', [AccountRecoveryController::class, 'create'])->name('account-recovery.request');
    Route::post('hesap-kurtar', [AccountRecoveryController::class, 'store'])
        ->middleware(['honeypot', 'throttle:6,1'])
        ->name('account-recovery.store');
});

Route::middleware('auth')->group(function () {
    Route::get('eposta-dogrula', EmailVerificationPromptController::class)->name('verification.notice');

    Route::get('eposta-dogrula/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:verification'])
        ->name('verification.verify');

    Route::post('eposta-dogrula/gonder', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:verification')
        ->name('verification.send');

    Route::post('cikis', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
