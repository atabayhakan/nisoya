<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use App\Support\QrKodu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClaimListingController extends Controller
{
    /**
     * Sahiplenme karşılama ekranını gösterir.
     */
    public function show(string $token): View
    {
        $listing = Listing::query()
            ->where('claim_token', $token)
            ->where('is_claimed', false)
            ->with(['category', 'country', 'coverImage', 'images'])
            ->firstOrFail();

        $listingUrl = route('listings.show', [$listing->id, $listing->slug]);
        $qrSvg = QrKodu::svg($listingUrl, 220);

        return view('claim.show', [
            'listing' => $listing,
            'token' => $token,
            'listingUrl' => $listingUrl,
            'qrSvg' => $qrSvg,
        ]);
    }

    /**
     * İşletmeyi sahiplenir ve hesaba bağlar.
     */
    public function claim(Request $request, string $token): RedirectResponse
    {
        $listing = Listing::query()
            ->where('claim_token', $token)
            ->where('is_claimed', false)
            ->firstOrFail();

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
        } else {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ], [
                'name.required' => 'Lütfen adınızı ve soyadınızı girin.',
                'email.required' => 'Lütfen geçerli bir e-posta adresi girin.',
                'email.email' => 'Geçerli bir e-posta formatı girin.',
                'password.required' => 'Lütfen bir şifre belirleyin.',
                'password.min' => 'Şifreniz en az 8 karakter olmalıdır.',
                'password.confirmed' => 'Şifre teyidi eşleşmiyor.',
            ]);

            $existingUser = User::query()->where('email', $validated['email'])->first();

            if ($existingUser) {
                if (! Hash::check($validated['password'], $existingUser->password)) {
                    return back()
                        ->withInput($request->except('password', 'password_confirmation'))
                        ->withErrors([
                            'email' => 'Bu e-posta adresiyle zaten bir hesap var. Şifrenizi kontrol edin veya önce giriş yapın.',
                        ]);
                }
                $user = $existingUser;
            } else {
                $user = User::create([
                    'name' => $validated['name'],
                    'username' => $this->uniqueUsername($validated['name']),
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'country_code' => $listing->country_code,
                    'city' => $listing->city,
                    'preferred_currency' => $listing->currency ?? 'TRY',
                    'email_verified_at' => now(),
                ]);
            }

            Auth::login($user);
            $request->session()->regenerate();
        }

        // İlanı kullanıcıya devret, tokeni iptal et ve yayına al
        $listing->update([
            'user_id' => $user->id,
            'is_claimed' => true,
            'claimed_at' => now(),
            'claim_token' => null,
            'status' => ListingStatus::Aktif,
        ]);

        return redirect()
            ->route('listings.show', [$listing->id, $listing->slug])
            ->with('status', "Tebrikler! \"{$listing->title}\" vitrini başarıyla hesabınıza bağlandı ve yayına alındı. Artık ilanınızı dilediğiniz gibi güncelleyebilirsiniz.");
    }

    /**
     * İsimden benzersiz kullanıcı adı üretir.
     */
    protected function uniqueUsername(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'kullanici';
        }

        $candidate = $base;
        $suffix = 1;

        while (User::query()->where('username', $candidate)->exists()) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}
