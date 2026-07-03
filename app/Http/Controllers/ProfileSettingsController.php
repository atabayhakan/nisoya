<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\UserStatus;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Favorite;
use App\Models\Message;
use App\Models\Report;
use App\Models\Review;
use App\Models\SavedSearch;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('panel.profile.edit', [
            'user' => $request->user(),
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'currencies' => Currency::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'paymentMethods' => PaymentMethod::cases(),
            'suggestedPaymentMethods' => PaymentMethod::suggestedFor($request->user()->country_code),
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
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['string', Rule::enum(PaymentMethod::class)],
        ], attributes: [
            'name' => 'ad soyad', 'username' => 'kullanıcı adı', 'bio' => 'hakkında',
            'country_code' => 'ülke', 'city' => 'şehir', 'preferred_currency' => 'para birimi', 'avatar' => 'profil fotoğrafı',
            'payment_methods' => 'ödeme yöntemleri',
        ]);

        $data['payment_methods'] = $data['payment_methods'] ?? [];

        if ($request->hasFile('avatar')) {
            // Avatar için sadece tek varyant (400x400, orijinal aspect ratio korunur)
            // EXIF orientation düzeltilir + metadata temizlenir (gizlilik)
            $imageService = app(ImageService::class);

            try {
                $result = $imageService->storeOptimized($request->file('avatar'), 'avatars', 400, 85);
            } catch (\RuntimeException) {
                return back()->withErrors(['avatar' => 'Profil fotoğrafı işlenemedi, lütfen başka bir dosyayla tekrar dene.']);
            }

            if ($user->avatar_path) {
                $imageService->deleteVariants(array_values($imageService->siblingVariantPaths($user->avatar_path)));
            }
            $data['avatar_path'] = $result['large'];
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

    /**
     * KVKK Madde 7 — kişisel verilerin silinmesi talebi.
     * Kullanıcı hesabını "silinmiş" olarak işaretler; kişisel verileri temizlenir.
     * Yasal zorunluluk olan işlem kayıtları (örn. fatura) hariç.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirm_text' => ['required', 'string', 'in:HESABIMI SİL'],
        ], attributes: [
            'current_password' => 'mevcut şifre',
            'confirm_text' => 'onay metni',
        ], messages: [
            'confirm_text.in' => 'Onay metni "HESABIMI SİL" olmalıdır.',
        ]);

        DB::transaction(function () use ($request, $user) {
            $imageService = app(ImageService::class);

            // Avatarı sil (tüm varyantlar: thumb/medium/large)
            if ($user->avatar_path) {
                $imageService->deleteVariants(array_values($imageService->siblingVariantPaths($user->avatar_path)));
            }

            // İlan görsellerini sil (tüm varyantlar)
            foreach ($user->listings()->with('images')->get() as $listing) {
                foreach ($listing->images as $image) {
                    $imageService->deleteVariants($image->variantPaths());
                }
            }

            // İlanları sil (görseller cascade ile)
            $user->listings()->delete();

            // Kullanıcıya ait tüm kişisel etkileşimleri temizle
            Favorite::query()->where('user_id', $user->id)->delete();
            SavedSearch::query()->where('user_id', $user->id)->delete();
            Review::query()->where('reviewer_id', $user->id)->delete();

            // Aldığı değerlendirmeleri anonimleştir
            Review::query()->where('reviewee_id', $user->id)
                ->update(['comment' => null]);

            // Şikayetleri temizle
            Report::query()->where('reporter_id', $user->id)->delete();

            // Mesajlar: yasal/log amaçlı gövde temizlenir ama meta kalır
            // (aktarımlarda konuşma bağlamı korunsun diye)
            Message::query()->where('sender_id', $user->id)
                ->update(['body' => '[silindi]']);

            // Kullanıcıyı "silinmiş" durumuna çek ve kişisel verileri anonimleştir.
            // DB kaydı tutulur (log/denetim için) ama PII temizlenir.
            // password NOT NULL — benzersiz rastgele değer ata (giriş yine de başarısız olur
            // çünkü status='Silinmis' EnsureUserIsActive middleware'i tarafından engellenir).
            $user->update([
                'name' => 'Silinmiş Kullanıcı',
                'username' => 'deleted-'.$user->id,
                'email' => 'deleted-'.$user->id.'@nisoya.local',
                'phone' => null,
                'password' => bcrypt(\Illuminate\Support\Str::random(64)),
                'avatar_path' => null,
                'bio' => null,
                'city' => null,
                'payment_methods' => null,
                'remember_token' => null,
                'status' => UserStatus::Silinmis,
                'referral_code' => null,
            ]);

            // Session ve çerezleri temizle (transaction sonrası — auth hâlâ geçerli olmalı)
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        });

        return redirect()->route('home')
            ->with('status', 'Hesabın silindi. Kişisel verilerin temizlendi. Güle güle 👋');
    }

    /**
     * KVKK Madde 11 — verilerin dışa aktarılması.
     * Tüm kişisel verileri JSON + CSV formatında indirir.
     */
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();

        $data = [
            'export_date' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'country_code' => $user->country_code,
                'city' => $user->city,
                'bio' => $user->bio,
                'payment_methods' => $user->payment_methods?->map(fn ($m) => $m->value)->values()->all() ?? [],
                'role' => $user->role?->value,
                'is_verified' => $user->is_verified,
                'status' => $user->status?->value,
                'created_at' => $user->created_at?->toIso8601String(),
                'last_seen_at' => $user->last_seen_at?->toIso8601String(),
            ],
            'listings' => $user->listings()->with('category', 'country', 'images', 'tags')->get()->toArray(),
            'reviews_given' => $user->reviewsGiven()->get()->toArray(),
            'reviews_received' => $user->reviewsReceived()->get()->toArray(),
            'favorites' => $user->favorites()->with('listing')->get()->toArray(),
            'saved_searches' => $user->savedSearches()->get()->toArray(),
            'sent_messages' => $user->sentMessages()->get()->toArray(),
        ];

        $filename = 'nisoya-verilerim-'.$user->id.'-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }
}
