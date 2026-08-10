<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\OturumBaslat;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use App\Services\FraudBlocklist;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Google ile gelen yeni kişinin kaydını TAMAMLAYAN adım.
 *
 * Google ad ve e-posta verir; Nisoya'nın kayıt sözleşmesi bunlara ek olarak
 * ülke, para birimi ve koşul onayı ister (bkz. RegisteredUserController).
 * Bu ekran yalnız o farkı kapatır — Google'dan gelen iki alan tekrar
 * sorulmaz.
 *
 * Kullanıcı bu adımı tamamlayana kadar `users` tablosunda HİÇBİR kayıt yoktur.
 * Yarım kullanıcı yaratıp "sonra doldurur" demek, ülkesi boş olduğu için
 * hiçbir ülke filtresinde görünmeyen ve bunu kimsenin fark etmediği ölü
 * hesaplar üretirdi.
 */
class GoogleRegistrationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $taslak = $request->session()->get('google.kayit');

        if (! $taslak) {
            // Doğrudan URL'ye gelinmiş ya da oturum düşmüş.
            return redirect()->route('register');
        }

        return view('auth.google-tamamla', [
            'ad' => $taslak['ad'],
            'eposta' => $taslak['eposta'],
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'currencies' => Currency::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request, OturumBaslat $oturumBaslat, FraudBlocklist $karaListe): RedirectResponse
    {
        $taslak = $request->session()->get('google.kayit');

        if (! $taslak) {
            return redirect()->route('register');
        }

        $validated = $request->validate([
            'country_code' => ['required', 'string', 'exists:countries,code'],
            'city' => ['nullable', 'string', 'max:255'],
            'preferred_currency' => ['required', 'string', 'exists:currencies,code'],
            'terms' => ['accepted'],
        ], attributes: [
            'country_code' => 'ülke',
            'city' => 'şehir',
            'preferred_currency' => 'para birimi',
            'terms' => 'kullanım koşulları',
        ]);

        $email = $taslak['eposta'];

        // Kara liste burada TEKRAR kontrol edilir: callback'ten bu adıma kadar
        // geçen sürede hesap dondurulmuş olabilir ve bu ekran oturumdaki
        // taslakla açılıyor.
        if ($karaListe->isBlocked(FraudBlocklist::TYPE_EMAIL, $email)) {
            $request->session()->forget('google.kayit');

            return redirect()->route('login')->withErrors([
                'email' => 'Bu e-posta ile kayıt tamamlanamadı. Yardım için bizimle iletişime geçebilirsin.',
            ]);
        }

        // Yarış durumu: aynı e-posta bu iki adım arasında kaydolmuş olabilir
        // (başka sekme, parolayla kayıt). Kaydı zorlamak yerine o hesaba gir.
        if ($mevcut = User::query()->where('email', $email)->first()) {
            $request->session()->forget('google.kayit');

            return $oturumBaslat->calistir($request, $mevcut);
        }

        $user = User::create([
            'name' => $taslak['ad'],
            'username' => $this->uniqueUsername($taslak['ad']),
            'email' => $email,
            // Parola YOK: Google ile gelen hesabın parolası hiç kurulmaz.
            // Rastgele bir parola atanır ki sütun boş kalmasın ve kimse bunu
            // tahmin edip parola girişi yapamasın; kullanıcı isterse
            // "şifremi unuttum" akışıyla kendi parolasını kurar.
            'password' => bcrypt(Str::random(64)),
            'country_code' => $validated['country_code'],
            'city' => $validated['city'] ?? null,
            'preferred_currency' => $validated['preferred_currency'],
            'referred_by' => $this->resolveReferrerId($request),
        ]);

        /*
         * Google adresi doğruladı; ikinci bir doğrulama postası istemek
         * kullanıcıyı zaten kanıtlanmış bir şey için bekletmek olurdu.
         *
         * `email_verified_at` BİLEREK `$fillable` dışında bırakılıyor —
         * kütlesel atamaya açmak, bir formun bu alanı taşıyabilmesi demek
         * olurdu. Burada açıkça işaretliyoruz.
         *
         * Sıra önemli: `Registered` olayı BUNDAN SONRA gönderilir, yoksa
         * Laravel'in doğrulama postası dinleyicisi hesabı doğrulanmamış
         * görüp gereksiz bir e-posta yollar.
         */
        $user->markEmailAsVerified();

        event(new Registered($user));

        $request->session()->forget('google.kayit');

        return $oturumBaslat->calistir($request, $user);
    }

    /** Oturumdaki davet kodundan davet eden kullanıcının id'sini çözer. */
    protected function resolveReferrerId(Request $request): ?int
    {
        $code = $request->session()->pull('referral_code');

        if (! $code) {
            return null;
        }

        return User::query()->where('referral_code', $code)->value('id');
    }

    /** İsimden benzersiz kullanıcı adı üretir (parola kaydıyla aynı kural). */
    protected function uniqueUsername(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'uye';
        }

        $username = $base;
        $i = 1;

        while (User::query()->where('username', $username)->exists()) {
            $i++;
            $username = $base.'-'.$i;
        }

        return $username;
    }
}
