<?php

namespace App\Support;

use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Google ile girişin açık olup olmadığına karar veren tek yer.
 *
 * "Açık" üç şeyin BİRLİKTE doğru olması demektir: sahip panelden açmış,
 * client id dolu, client secret dolu. Yalnız bayrağa bakılsaydı, anahtarlar
 * girilmeden açılan bir düğme kullanıcıyı Google'ın hata sayfasına
 * gönderirdi — ve bunu ilk fark eden üye olurdu.
 */
class GoogleGiris
{
    public static function kullanilabilir(): bool
    {
        if (Settings::get('giris.google_aktif') !== '1') {
            return false;
        }

        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    /**
     * Google bu e-postanın sahipliğini doğruladı mı?
     *
     * Socialite bunu tipli arayüzde sunmuyor; ham veride `email_verified`
     * (OpenID Connect) ya da eski `verified_email` (Google People) olarak
     * gelir. Alan HİÇ yoksa `false` döneriz — doğrulanmamış bir adresi kabul
     * etmek, saldırganın kendi Google hesabına kurbanın adresini yazıp mevcut
     * Nisoya hesabına girmesi demek olurdu.
     */
    public static function epostaDogrulanmis(SocialiteUser $user): bool
    {
        // `getRaw()` Socialite'ın ARAYÜZÜNDE yok, soyut sınıfında var. Tip
        // güvencesi olmadan çağırmak yerine daraltıyoruz; sürücü beklenmedik
        // bir tip döndürürse "doğrulanmadı" sayılır (güvenli taraf).
        if (! $user instanceof AbstractUser) {
            return false;
        }

        $ham = $user->getRaw();

        foreach (['email_verified', 'verified_email'] as $alan) {
            if (array_key_exists($alan, $ham)) {
                return filter_var($ham[$alan], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return false;
    }

    /** Google Cloud Console'a kaydedilecek yönlendirme adresi. */
    public static function yonlendirmeAdresi(): string
    {
        return url(config('services.google.redirect'));
    }
}
