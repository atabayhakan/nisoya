<?php

namespace App\Http\Controllers\WebAuthn;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;

/**
 * Passkey kayıt/yönetim uçları (Faz M2). Kayıt UI'ı 2FA sayfasında
 * (panel/profile/two-factor.blade.php); JS: resources/js/app.js →
 * passkeyManage().
 */
class WebAuthnRegisterController
{
    /**
     * Cihazın doğrulayacağı kayıt challenge'ını üretir.
     */
    public function options(AttestationRequest $request): mixed
    {
        // fastRegistration: attestation doğrulaması olmadan hızlı kayıt —
        // pazaryeri için yeterli (banka değiliz), tüm cihazlarla uyumlu.
        return $request->fastRegistration()->toCreate();
    }

    /**
     * Passkey'i kullanıcıya kaydeder. Alias query string ile gelir
     * (webpass'in body birleştirme davranışına bağımlı olmamak için).
     */
    public function register(AttestedRequest $request): Response
    {
        $alias = mb_substr(trim((string) $request->query('alias', '')), 0, 50) ?: null;

        $request->save(['alias' => $alias]);

        return response()->noContent();
    }

    /**
     * Kullanıcının kendi passkey'ini siler.
     */
    public function destroy(Request $request, string $credentialId): RedirectResponse
    {
        $deleted = $request->user()->webAuthnCredentials()
            ->whereKey($credentialId)
            ->delete();

        return back()->with('status', $deleted ? 'Passkey silindi.' : 'Passkey bulunamadı.');
    }
}
