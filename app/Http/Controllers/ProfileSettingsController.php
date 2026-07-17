<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\UserStatus;
use App\Models\CompanyReview;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Favorite;
use App\Models\JobBookmark;
use App\Models\JobCategory;
use App\Models\JobFeatureRequest;
use App\Models\JobSavedSearch;
use App\Models\Message;
use App\Models\Report;
use App\Models\Review;
use App\Models\SavedSearch;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $paymentLinks = $user->paymentLinks()->get();

        return view('panel.profile.edit', [
            'user' => $user,
            'countries' => Country::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'currencies' => Currency::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'paymentLinks' => $paymentLinks,
            'availablePaymentMethods' => collect(PaymentMethod::cases())
                ->reject(fn ($m) => $paymentLinks->contains('method', $m))
                ->values(),
            'suggestedPaymentMethods' => PaymentMethod::suggestedFor($user->country_code),
            'portfolioItems' => $user->portfolioItems,
            'jobCategories' => JobCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:1000'],
            'skills' => ['nullable', 'string', 'max:500'],
            'country_code' => ['required', 'exists:countries,code'],
            'city' => ['nullable', 'string', 'max:255'],
            'preferred_currency' => ['required', 'exists:currencies,code'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=32,min_height=32'],
            'is_searchable' => ['nullable', 'boolean'],
            'job_category_id' => ['nullable', 'exists:job_categories,id'],
        ], attributes: [
            'name' => 'ad soyad', 'username' => 'kullanıcı adı', 'bio' => 'hakkında', 'skills' => 'yetenekler',
            'country_code' => 'ülke', 'city' => 'şehir', 'preferred_currency' => 'para birimi', 'avatar' => 'profil fotoğrafı',
            'is_searchable' => 'yetenek havuzunda görünürlük', 'job_category_id' => 'uzmanlık alanı',
        ]);

        $data['is_searchable'] = $request->boolean('is_searchable');

        // Virgülle ayrılmış serbest metin girişini temiz bir diziye çevir
        // (boş/tekrar eden girdileri at, en fazla 15 yetenek).
        $data['skills'] = collect(explode(',', $data['skills'] ?? ''))
            ->map(fn ($skill) => trim($skill))
            ->filter()
            ->unique()
            ->take(15)
            ->values()
            ->all() ?: null;

        $oldAvatarPath = null;
        $oldCroppedPath = null;
        // Yalnızca yeni avatar yüklenirken değil, aşağıda eski dosyaları
        // silerken de kullanılıyor — koşulun dışında, en başta çözülür.
        $imageService = app(ImageService::class);

        if ($request->hasFile('avatar')) {
            // EXIF orientation düzeltilir + metadata temizlenir (gizlilik)
            try {
                $result = $imageService->storeOptimized($request->file('avatar'), 'avatars', 400, 85);
            } catch (\RuntimeException) {
                return back()->withErrors(['avatar' => 'Profil fotoğrafı işlenemedi, lütfen başka bir dosyayla tekrar dene.']);
            }

            // Eski dosyalar burada SİLİNMEZ — $user->update() başarılı
            // olana kadar bekletilir (aşağıda). DB yazımı başarısız olursa
            // kullanıcı kırık bir avatarla kalmasın diye (2026-07-17 karşıt
            // inceleme raporu).
            $oldAvatarPath = $user->avatar_path;
            $oldCroppedPath = $user->avatar_cropped_path;
            $data['avatar_path'] = $result['large'];

            // Yeni fotoğrafa hemen ORTALANMIŞ kare kırpım üret: gösterim her
            // yerde baştan itibaren kare dosya kullanır; kullanıcı "düzenle"
            // ile sonradan istediği gibi kaydırıp yakınlaştırabilir.
            [$w, $h] = [$result['original_dimensions']['width'], $result['original_dimensions']['height']];
            // large varyantı 1600px'e küçültülmüş olabilir — kırpım o dosya
            // üzerinden yapılacağı için boyutları dosyadan tazele.
            $largeSize = @getimagesize(Storage::disk('public')->path($result['large']));
            if ($largeSize) {
                [$w, $h] = [$largeSize[0], $largeSize[1]];
            }
            $size = min($w, $h);
            $x = intdiv($w - $size, 2);
            $y = intdiv($h - $size, 2);

            try {
                $data['avatar_cropped_path'] = $imageService->cropSquare($result['large'], $x, $y, $size, 'avatars/cropped');
                $data['avatar_crop_x'] = $x;
                $data['avatar_crop_y'] = $y;
                $data['avatar_crop_size'] = $size;
            } catch (\RuntimeException) {
                // Kırpım üretilemezse eski odak-noktası gösterimine düş —
                // yükleme yine başarılı sayılır.
                $data['avatar_cropped_path'] = null;
                $data['avatar_crop_x'] = null;
                $data['avatar_crop_y'] = null;
                $data['avatar_crop_size'] = null;
            }
            $data['avatar_focal_x'] = 50;
            $data['avatar_focal_y'] = 50;
        }

        unset($data['avatar']);
        $user->update($data);

        // DB güncellemesi başarıyla tamamlandıktan SONRA eski dosyaları sil.
        if ($oldAvatarPath) {
            $imageService->deleteVariants(array_values($imageService->siblingVariantPaths($oldAvatarPath)));
        }
        if ($oldCroppedPath) {
            $imageService->deleteVariants([$oldCroppedPath]);
        }

        return back()->with('status', 'Profilin güncellendi.');
    }

    /**
     * Profil fotoğrafı hizalama (kaydır + yakınlaştır, bkz. avatarCropModal).
     * İstemci, modaldaki pan/zum durumunu orijinal görselin piksel
     * koordinatlarında KARE bir kırpım dikdörtgenine çevirip gönderir; sunucu
     * sınırları doğrulayıp kare dosyayı üretir. Gösterim her yerde bu dosyayı
     * kullandığı için transform matematiği tek yerde (burada) çözülür.
     *
     * Sıra ÖNEMLİ: önce yeni dosya üretilir, SONRA DB güncellenir, EN SON eski
     * dosya silinir. DB güncellemesi (ör. bağlantı hatası) başarısız olursa
     * eski dosya hâlâ diskte durur — kullanıcı kırık bir avatarla kalmaz
     * (2026-07-17 karşıt inceleme raporu). Kullanıcı satırı `lockForUpdate`
     * ile kilitlenip aynı kullanıcıdan gelen eşzamanlı (çift tıklama/script)
     * istekler sıraya sokulur — aksi halde iki isteğin ikisi de aynı "eski"
     * yolu görüp onu silmeye çalışır, biri sahipsiz bir dosya bırakır.
     */
    public function alignAvatar(Request $request): JsonResponse
    {
        if (! $request->user()->avatar_path) {
            return response()->json(['message' => 'Önce bir profil fotoğrafı yükle.'], 422);
        }

        $data = $request->validate([
            'crop_x' => ['required', 'integer', 'min:0', 'max:20000'],
            'crop_y' => ['required', 'integer', 'min:0', 'max:20000'],
            'crop_size' => ['required', 'integer', 'min:16', 'max:20000'],
        ]);

        $imageService = app(ImageService::class);
        $userId = $request->user()->id;

        try {
            return DB::transaction(function () use ($userId, $data, $imageService) {
                $user = User::whereKey($userId)->lockForUpdate()->first();

                if (! $user->avatar_path) {
                    // Kilit alınana kadar geçen sürede (ör. moderasyon) avatar
                    // kaldırılmış olabilir — kaynak yoksa devam etme.
                    throw new \RuntimeException('Profil fotoğrafı artık mevcut değil.');
                }

                $croppedPath = $imageService->cropSquare(
                    $user->avatar_path,
                    $data['crop_x'],
                    $data['crop_y'],
                    $data['crop_size'],
                    'avatars/cropped',
                );

                $oldCroppedPath = $user->avatar_cropped_path;

                $user->update([
                    'avatar_cropped_path' => $croppedPath,
                    'avatar_crop_x' => $data['crop_x'],
                    'avatar_crop_y' => $data['crop_y'],
                    'avatar_crop_size' => $data['crop_size'],
                ]);

                if ($oldCroppedPath) {
                    $imageService->deleteVariants([$oldCroppedPath]);
                }

                return response()->json([
                    'status' => 'ok',
                    'cropped_url' => Storage::url($croppedPath),
                ]);
            });
        } catch (\RuntimeException) {
            return response()->json(['message' => 'Kırpım uygulanamadı — sayfayı yenileyip tekrar dene.'], 422);
        }
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

            // Avatarı sil (tüm varyantlar: thumb/medium/large + kare kırpım)
            if ($user->avatar_path) {
                $imageService->deleteVariants(array_values($imageService->siblingVariantPaths($user->avatar_path)));
            }
            if ($user->avatar_cropped_path) {
                $imageService->deleteVariants([$user->avatar_cropped_path]);
            }

            // Ödeme linki QR kodlarını sil, sonra kayıtları temizle
            foreach ($user->paymentLinks as $paymentLink) {
                if ($paymentLink->qr_path) {
                    $imageService->deleteVariants(array_values($imageService->siblingVariantPaths($paymentLink->qr_path)));
                }
            }
            $user->paymentLinks()->delete();

            // Portfolyo görsellerini sil, sonra kayıtları temizle
            foreach ($user->portfolioItems as $portfolioItem) {
                $imageService->deleteVariants($portfolioItem->variantPaths());
            }
            $user->portfolioItems()->delete();

            // Gurbet Günlüğü hikayelerini sil (onaylanmış olsalar bile —
            // herkese açık tarafta zaten anonim gösteriliyordu ama hesap
            // silme talebinde veri tamamen kaldırılır).
            $user->stories()->delete();

            // İş ilanı başvuruları (aday olarak yaptıkları)
            $user->jobApplications()->delete();

            // Şirket profili + logo + galeri görselleri + tüm iş ilanları
            // (job_listings ve onların başvuruları FK cascade ile silinir).
            if ($user->company) {
                if ($user->company->logo_path) {
                    $imageService->deleteVariants(array_values($imageService->siblingVariantPaths($user->company->logo_path)));
                }
                foreach ($user->company->galleryImages as $galleryImage) {
                    $imageService->deleteVariants($galleryImage->variantPaths());
                }
                $user->company()->delete();
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
            JobBookmark::query()->where('user_id', $user->id)->delete();
            CompanyReview::query()->where('reviewer_id', $user->id)->delete();
            SavedSearch::query()->where('user_id', $user->id)->delete();
            JobSavedSearch::query()->where('user_id', $user->id)->delete();
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
                'password' => bcrypt(Str::random(64)),
                'avatar_path' => null,
                'avatar_cropped_path' => null,
                'bio' => null,
                'skills' => null,
                'city' => null,
                'is_searchable' => false,
                'job_category_id' => null,
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
                'skills' => $user->skills,
                'yetenek_havuzunda_gorunur' => $user->is_searchable,
                'uzmanlik_alani' => $user->jobCategory?->name,
                'role' => $user->role?->value,
                'is_verified' => $user->is_verified,
                'status' => $user->status?->value,
                'created_at' => $user->created_at?->toIso8601String(),
                'last_seen_at' => $user->last_seen_at?->toIso8601String(),
            ],
            'payment_links' => $user->paymentLinks()->get()->map(fn ($pl) => [
                'method' => $pl->method->value,
                'detail' => $pl->detail,
                'has_qr' => (bool) $pl->qr_path,
            ])->all(),
            'portfolio_items' => $user->portfolioItems()->get()->map(fn ($item) => [
                'caption' => $item->caption,
            ])->all(),
            'gurbet_gunlugu_hikayeleri' => $user->stories()->get()->map(fn ($story) => [
                'body' => $story->body,
                'status' => $story->status->value,
                'created_at' => $story->created_at?->toIso8601String(),
            ])->all(),
            'sirket' => $user->company ? [
                'name' => $user->company->name,
                'sector' => $user->company->sector,
                'website' => $user->company->website,
                'about' => $user->company->about,
            ] : null,
            'sirket_galeri' => $user->company
                ? $user->company->galleryImages()->get()->map(fn ($img) => ['caption' => $img->caption])->all()
                : [],
            'is_ilani_one_cikarma_taleplerim' => $user->jobFeatureRequests()->with('jobListing')->get()->map(fn (JobFeatureRequest $r) => [
                'ilan' => $r->jobListing?->title,
                'gun' => $r->days,
                'durum' => $r->status->value,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->all(),
            'is_basvurularim' => $user->jobApplications()->with('jobListing')->get()->map(fn ($a) => [
                'ilan' => $a->jobListing?->title,
                'durum' => $a->status->value,
                'created_at' => $a->created_at?->toIso8601String(),
            ])->all(),
            'listings' => $user->listings()->with('category', 'country', 'images', 'tags')->get()->toArray(),
            'reviews_given' => $user->reviewsGiven()->get()->toArray(),
            'reviews_received' => $user->reviewsReceived()->get()->toArray(),
            'favorites' => $user->favorites()->with('listing')->get()->toArray(),
            'is_yer_imlerim' => $user->jobBookmarks()->with('jobListing')->get()->map(fn ($b) => [
                'ilan' => $b->jobListing?->title,
                'created_at' => $b->created_at?->toIso8601String(),
            ])->all(),
            'sirket_degerlendirmelerim' => $user->companyReviewsGiven()->with('company')->get()->map(fn ($r) => [
                'sirket' => $r->company?->name,
                'puan' => $r->rating,
                'yorum' => $r->comment,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->all(),
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
