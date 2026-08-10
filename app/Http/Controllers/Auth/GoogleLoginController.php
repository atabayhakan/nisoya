<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\OturumBaslat;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FraudBlocklist;
use App\Support\GoogleGiris;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Google ile giriş / kayıt.
 *
 * ---------------------------------------------------------------------------
 * AKIŞ NEDEN İKİ ADIMLI
 *
 * Kayıt formu `country_code` ve `preferred_currency` alanlarını ZORUNLU
 * tutuyor (ilan listeleri ülkeye göre filtreleniyor, fiyatlar para birimine
 * göre yazılıyor) ve kullanım koşullarının açıkça onaylanmasını istiyor.
 * Google bunların hiçbirini vermez — yalnız ad, e-posta ve avatar verir.
 *
 * Bu yüzden "tek tık kayıt" mümkün değil: Google'dan dönen kişi ZATEN ÜYEYSE
 * doğrudan girer; DEĞİLSE kullanıcı kaydı burada YARATILMAZ, profil oturuma
 * konur ve eksik iki alanı soran tamamlama ekranına gidilir. Yarım kullanıcı
 * yaratmak, ülkesi olmadığı için hiçbir listede görünmeyen sessiz bozuk
 * hesaplar üretirdi.
 *
 * ---------------------------------------------------------------------------
 * GÜVENLİK
 *
 * - Oturum HER ZAMAN OturumBaslat üzerinden açılır: 2FA açık bir hesap
 *   Google'la giriş yaparak ikinci faktörü ATLAYAMAZ.
 * - Google'ın doğrulamadığı e-posta kabul edilmez. Doğrulanmamış e-posta
 *   kabul edilseydi, saldırgan kendi Google hesabına kurbanın adresini
 *   yazarak mevcut hesabı ele geçirebilirdi.
 * - Dolandırıcılık kara listesi parola kaydındaki ile aynı şekilde uygulanır;
 *   aksi hâlde Google, engellenen e-postalar için arka kapı olurdu.
 */
class GoogleLoginController extends Controller
{
    public function redirect(): SymfonyRedirect|RedirectResponse
    {
        if (! GoogleGiris::kullanilabilir()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google ile giriş şu anda kapalı.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, OturumBaslat $oturumBaslat, FraudBlocklist $karaListe): RedirectResponse
    {
        if (! GoogleGiris::kullanilabilir()) {
            return redirect()->route('login')->withErrors(['email' => 'Google ile giriş şu anda kapalı.']);
        }

        // Kullanıcı Google ekranında "iptal" derse buraya hata parametresiyle
        // döner; bunu bir arıza gibi göstermeyip sessizce girişe yolluyoruz.
        if ($request->has('error')) {
            return redirect()->route('login');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            // Yanlış/yenilenmiş client secret, süresi geçmiş kod, saat kayması…
            // Kullanıcıya teknik detay verilmez ama sebep loga yazılır, yoksa
            // "Google çalışmıyor" şikâyetinin nedeni hiç öğrenilemez.
            Log::warning('Google girişi başarısız', ['istisna' => $e::class, 'mesaj' => $e->getMessage()]);

            return redirect()->route('login')->withErrors([
                'email' => 'Google ile giriş tamamlanamadı. Lütfen tekrar dene ya da e-posta ile gir.',
            ]);
        }

        $email = mb_strtolower((string) $googleUser->getEmail());

        if ($email === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'Google hesabından e-posta alınamadı.',
            ]);
        }

        // Google, adresin sahibi olduğunu doğruladı mı? Socialite bu alanı
        // ham veride taşır; alan yoksa GÜVENLİ tarafta kalıp reddediyoruz.
        if (! GoogleGiris::epostaDogrulanmis($googleUser)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google hesabının e-postası doğrulanmamış. Lütfen e-posta ile giriş yap.',
            ]);
        }

        if ($karaListe->isBlocked(FraudBlocklist::TYPE_EMAIL, $email)) {
            // Parola kaydındaki ile AYNI nötr mesaj — kara listede olduğunu
            // ele vermez.
            return redirect()->route('login')->withErrors([
                'email' => 'Bu e-posta ile giriş tamamlanamadı. Yardım için bizimle iletişime geçebilirsin.',
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            // Mevcut hesap: e-postayı Google doğruladığı için bağlama güvenli.
            // 2FA açıksa OturumBaslat challenge'a yönlendirir.
            return $oturumBaslat->calistir($request, $user);
        }

        // Yeni kişi: KULLANICI YARATILMAZ. Eksik alanlar tamamlama ekranında.
        $request->session()->put('google.kayit', [
            'ad' => trim((string) $googleUser->getName()) ?: 'Nisoya üyesi',
            'eposta' => $email,
        ]);

        return redirect()->route('register.google.complete');
    }
}
