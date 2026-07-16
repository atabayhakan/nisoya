<?php

namespace App\Http\Controllers\WebAuthn;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;

/**
 * Passkey ile giriş (Faz M2). E-posta verilirse o hesabın credential'ları,
 * verilmezse discoverable credential (cihazın kendi hatırladığı passkey)
 * kullanılır. JS tarafı: resources/js/app.js → passkeyLogin().
 */
class WebAuthnLoginController
{
    /**
     * Doğrulama challenge'ını üretir.
     */
    public function options(AssertionRequest $request): Responsable
    {
        return $request->toVerify($request->validate(['email' => 'sometimes|email|string']));
    }

    /**
     * Passkey doğrulamasıyla oturum açar.
     */
    public function login(AssertedRequest $request): JsonResponse
    {
        $user = $request->login();

        if (! $user) {
            return response()->json(['message' => 'Passkey doğrulanamadı.'], 422);
        }

        // Parola girişiyle aynı davranış (AuthenticatedSessionController@store).
        $user->forceFill(['last_seen_at' => now()])->save();

        return response()->json([
            'redirect' => session()->pull('url.intended', route('dashboard')),
        ]);
    }
}
