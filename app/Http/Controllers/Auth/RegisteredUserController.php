<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
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
        ], attributes: [
            'name' => 'ad soyad',
            'email' => 'e-posta',
            'password' => 'şifre',
            'country_code' => 'ülke',
            'city' => 'şehir',
            'preferred_currency' => 'para birimi',
            'terms' => 'kullanım koşulları',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $this->uniqueUsername($validated['name']),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'country_code' => $validated['country_code'],
            'city' => $validated['city'] ?? null,
            'preferred_currency' => $validated['preferred_currency'],
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard');
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
