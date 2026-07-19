<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use App\Services\FraudBlocklist;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        // Davet bağlantısıyla (?ref=KOD) gelindiyse kodu oturumda sakla;
        // kayıt POST'unda davet edeni eşleştirmek için kullanılacak.
        if ($ref = $request->query('ref')) {
            $request->session()->put('referral_code', $ref);
        }

        return view('auth.kayit', [
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'currencies' => Currency::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'country_code' => ['required', 'string', 'exists:countries,code'],
            'city' => ['nullable', 'string', 'max:255'],
            'preferred_currency' => ['required', 'string', 'exists:currencies,code'],
            'terms' => ['accepted'],
            'website' => ['prohibited'], // honeypot: botlar doldurur, gerçek kullanıcı boş bırakır
        ], attributes: [
            'name' => 'ad soyad',
            'email' => 'e-posta',
            'password' => 'şifre',
            'country_code' => 'ülke',
            'city' => 'şehir',
            'preferred_currency' => 'para birimi',
            'terms' => 'kullanım koşulları',
        ]);

        // Dolandırıcılık nedeniyle dondurulmuş bir hesabın e-postasıyla yeniden
        // kayıt engellenir (K-D). Nötr mesaj — kara listede olduğunu ele vermez.
        if (app(FraudBlocklist::class)->isBlocked(FraudBlocklist::TYPE_EMAIL, $validated['email'])) {
            throw ValidationException::withMessages([
                'email' => 'Bu e-posta ile kayıt tamamlanamadı. Yardım için bizimle iletişime geçebilirsin.',
            ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'username' => $this->uniqueUsername($validated['name']),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'country_code' => $validated['country_code'],
            'city' => $validated['city'] ?? null,
            'preferred_currency' => $validated['preferred_currency'],
            'referred_by' => $this->resolveReferrerId($request),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
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

    /** İsimden benzersiz kullanıcı adı üretir. */
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
