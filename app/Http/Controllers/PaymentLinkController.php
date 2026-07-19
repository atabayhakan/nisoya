<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\PaymentLink;
use App\Services\FraudBlocklist;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Kullanıcının kendi ödeme linkini/QR kodunu ekleyip kaldırdığı uçlar.
 * Nisoya bu linkler/kodlar üzerinden hiçbir para akışını görmez veya
 * işlemez — sadece satıcının kendi ödeme sayfasına yönlendirir.
 */
class PaymentLinkController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'method' => ['required', 'string', Rule::enum(PaymentMethod::class), Rule::unique('payment_links')->where('user_id', $user->id)],
            'detail' => ['nullable', 'string', 'max:255'],
            'qr' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], attributes: [
            'method' => 'ödeme yöntemi', 'detail' => 'bağlantı/bilgi', 'qr' => 'QR kod görseli',
        ], messages: [
            'method.unique' => 'Bu ödeme yöntemini zaten eklemişsin.',
        ]);

        // Dolandırıcılık nedeniyle kara listeye alınmış bir IBAN/ödeme handle'ı
        // yeni bir hesapta tekrar kullanılamaz (K-D — dolandırıcının en kıt
        // kaynağı: gerçek para kanalı).
        if (filled($data['detail'] ?? null)) {
            $blocklist = app(FraudBlocklist::class);
            $type = $data['method'] === PaymentMethod::SepaIban->value
                ? FraudBlocklist::TYPE_IBAN
                : FraudBlocklist::TYPE_HANDLE;

            if ($blocklist->isBlocked($type, $data['detail'])) {
                return back()->withErrors(['detail' => 'Bu ödeme bilgisi kullanılamıyor. Yardım için bizimle iletişime geç.']);
            }
        }

        if ($request->hasFile('qr')) {
            $imageService = app(ImageService::class);

            try {
                $result = $imageService->storeOptimized($request->file('qr'), 'payment-qr', 500, 85);
            } catch (\RuntimeException) {
                return back()->withErrors(['qr' => 'QR kod görseli işlenemedi, lütfen başka bir dosyayla tekrar dene.']);
            }
            $data['qr_path'] = $result['large'];
        }

        unset($data['qr']);
        $user->paymentLinks()->create($data);

        return back()->with('status', 'Ödeme yöntemi eklendi.');
    }

    public function destroy(Request $request, PaymentLink $paymentLink): RedirectResponse
    {
        abort_if($paymentLink->user_id !== $request->user()->id, 403);

        if ($paymentLink->qr_path) {
            $imageService = app(ImageService::class);
            $imageService->deleteVariants(array_values($imageService->siblingVariantPaths($paymentLink->qr_path)));
        }

        $paymentLink->delete();

        return back()->with('status', 'Ödeme yöntemi kaldırıldı.');
    }
}
