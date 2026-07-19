<?php

namespace App\Http\Controllers;

use App\Enums\DealStatus;
use App\Models\Conversation;
use App\Models\Deal;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\DealNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Anlaşma (Deal) durum makinesi aksiyonları. Nisoya para akışını görmez —
 * bu kayıt yalnızca tarafların niyetini paylaştığı bir defterdir (K-C).
 */
class DealController extends Controller
{
    /** Sohbet içinden yeni anlaşma teklif et. */
    public function propose(Request $request, Conversation $conversation): RedirectResponse
    {
        $user = $request->user();
        abort_unless($conversation->isParticipant($user), 403);

        $other = $conversation->other($user);
        abort_if($other === null, 404);

        // Aynı anda tek AÇIK anlaşma (teklif/kabul) olsun.
        if ($conversation->deals()->whereIn('status', [DealStatus::Teklif->value, DealStatus::Kabul->value])->exists()) {
            return back()->withErrors(['deal' => 'Bu sohbette hâlâ süren bir anlaşma var; önce onu sonuçlandır.']);
        }

        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'currency' => ['nullable', 'string', 'exists:currencies,code'],
        ], attributes: ['amount' => 'tutar', 'currency' => 'para birimi']);

        // Rol: ilan varsa satıcı = ilan sahibi; yoksa teklif eden = alıcı varsayılır.
        // (Model property erişimi larastan'da tipsiz/non-null kalıyor → skaler
        // dönen value() ile oku; whereKey(null) güvenli biçimde null döner.)
        $sellerId = Listing::whereKey($conversation->listing_id)->value('user_id') ?? $other->id;
        $buyerId = $sellerId === $user->id ? $other->id : $user->id;

        $hasAmount = ($data['amount'] ?? null) !== null;
        $listingCurrency = Listing::whereKey($conversation->listing_id)->value('currency');

        $conversation->deals()->create([
            'listing_id' => $conversation->listing_id,
            'seller_id' => $sellerId,
            'buyer_id' => $buyerId,
            'proposed_by' => $user->id,
            'amount' => $hasAmount ? $data['amount'] : null,
            'currency' => $hasAmount ? ($data['currency'] ?? $listingCurrency ?? $user->preferred_currency) : null,
            'status' => DealStatus::Teklif->value,
        ]);

        $other->notify(new DealNotification('proposed', $user->name, route('panel.messages.show', $conversation)));

        return back()->with('status', 'Anlaşma teklifin gönderildi.');
    }

    /** Teklifi kabul et (yalnızca teklifi YAPMAYAN taraf). */
    public function accept(Request $request, Deal $deal): RedirectResponse
    {
        $user = $request->user();
        abort_unless($deal->isParticipant($user), 403);
        abort_unless($deal->status === DealStatus::Teklif && ! $deal->wasProposedBy($user), 403, 'Bu anlaşma kabul edilemez.');

        $deal->update(['status' => DealStatus::Kabul->value, 'accepted_at' => now()]);

        return $this->backToConversation($deal, 'Anlaşmayı kabul ettin.');
    }

    /** Anlaşmayı tamamlandı işaretle (her iki taraf da yapabilir). */
    public function complete(Request $request, Deal $deal): RedirectResponse
    {
        $user = $request->user();
        abort_unless($deal->isParticipant($user), 403);
        abort_unless($deal->status === DealStatus::Kabul, 403, 'Yalnızca kabul edilmiş bir anlaşma tamamlanabilir.');

        $deal->update(['status' => DealStatus::Tamamlandi->value, 'completed_at' => now()]);

        return $this->backToConversation($deal, 'Anlaşma tamamlandı olarak işaretlendi. Artık birbirinizi değerlendirebilirsiniz.');
    }

    /** Süren anlaşmayı iptal et (teklif/kabul aşamasında). */
    public function cancel(Request $request, Deal $deal): RedirectResponse
    {
        $user = $request->user();
        abort_unless($deal->isParticipant($user), 403);
        abort_unless(in_array($deal->status, [DealStatus::Teklif, DealStatus::Kabul], true), 403, 'Bu anlaşma iptal edilemez.');

        $deal->update(['status' => DealStatus::Iptal->value, 'cancelled_at' => now()]);

        return $this->backToConversation($deal, 'Anlaşma iptal edildi.');
    }

    /** Anlaşmada sorun bildir (itiraz) — kabul veya tamamlanmış anlaşmada. */
    public function dispute(Request $request, Deal $deal): RedirectResponse
    {
        $user = $request->user();
        abort_unless($deal->isParticipant($user), 403);
        abort_unless(
            in_array($deal->status, [DealStatus::Kabul, DealStatus::Tamamlandi], true),
            403,
            'Bu anlaşma için sorun bildirilemez.'
        );

        $data = $request->validate([
            'dispute_note' => ['nullable', 'string', 'max:1000'],
        ], attributes: ['dispute_note' => 'açıklama']);

        $deal->update([
            'status' => DealStatus::Sorunlu->value,
            'disputed_at' => now(),
            'dispute_note' => $data['dispute_note'] ?? null,
        ]);

        User::find($deal->counterpartId($user))
            ?->notify(new DealNotification('disputed', $user->name, route('panel.messages.show', $deal->conversation_id)));

        return $this->backToConversation($deal, 'Sorun bildirimin alındı, ekibimiz inceleyecek. Ayrıca profildeki "Dolandırıcılık bildir" seçeneğini de kullanabilirsin.');
    }

    private function backToConversation(Deal $deal, string $status): RedirectResponse
    {
        return redirect()->route('panel.messages.show', $deal->conversation_id)->with('status', $status);
    }
}
