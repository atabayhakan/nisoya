<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Currency;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('panel.profile.edit', [
            'user' => $request->user(),
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'currencies' => Currency::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:1000'],
            'country_code' => ['required', 'exists:countries,code'],
            'city' => ['nullable', 'string', 'max:255'],
            'preferred_currency' => ['required', 'exists:currencies,code'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], attributes: [
            'name' => 'ad soyad', 'username' => 'kullanıcı adı', 'bio' => 'hakkında',
            'country_code' => 'ülke', 'city' => 'şehir', 'preferred_currency' => 'para birimi', 'avatar' => 'profil fotoğrafı',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $data['avatar_path'] = app(ImageService::class)->storeOptimized($request->file('avatar'), 'avatars', 600, 85);
        }

        unset($data['avatar']);
        $user->update($data);

        return back()->with('status', 'Profilin güncellendi.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], attributes: ['current_password' => 'mevcut şifre', 'password' => 'yeni şifre']);

        $request->user()->update(['password' => Hash::make($request->string('password'))]);

        return back()->with('status_password', 'Şifren güncellendi.');
    }
}
