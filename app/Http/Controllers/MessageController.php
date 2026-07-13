<?php

namespace App\Http\Controllers;

use App\Enums\ListingType;
use App\Enums\PriceUnit;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->where(function ($q) use ($user) {
                $q->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id);
            })
            ->with(['listing', 'userOne', 'userTwo', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at')
            ->get();

        return view('panel.messages.index', compact('conversations'));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        abort_unless($conversation->isParticipant($request->user()), 403);

        // Karşı tarafın okunmamış mesajlarını okundu işaretle
        $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation->load(['messages.sender', 'listing']);

        return view('panel.messages.show', [
            'conversation' => $conversation,
            'other' => $conversation->other($request->user()),
        ]);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        abort_unless($conversation->isParticipant($request->user()), 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);
        $conversation->update(['last_message_at' => now()]);

        $conversation->other($request->user())?->notify(
            new NewMessageNotification($message->body, $request->user()->name, $conversation->id)
        );

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $message->id,
                'body' => $message->body,
                'mine' => true,
                'recalled' => false,
                'time' => $message->created_at->format('d.m H:i'),
            ]);
        }

        return redirect()->route('panel.messages.show', $conversation);
    }

    /**
     * Kendi mesajını geri al. body DB'de kalır (moderasyon için), sadece
     * recalled_at damgalanır; arayüzde "Bu mesaj geri alındı" gösterilir.
     */
    public function recall(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        abort_unless($conversation->isParticipant($request->user()), 403);
        // Mesaj bu konuşmaya ait mi + gönderen ben miyim?
        abort_unless($message->conversation_id === $conversation->id, 404);
        abort_unless($message->sender_id === $request->user()->id, 403);

        if (! $message->isRecalled()) {
            $message->update(['recalled_at' => now()]);
        }

        return response()->json(['id' => $message->id, 'recalled' => true]);
    }

    /** Canlı sohbet: belirli id'den sonraki mesajları JSON döndürür (polling). */
    public function stream(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->isParticipant($request->user()), 403);

        $me = $request->user()->id;
        $after = (int) $request->query('after', 0);

        $conversation->messages()
            ->where('sender_id', '!=', $me)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->where('id', '>', $after)
            ->orderBy('id')
            ->get()
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'body' => $m->displayBody(),
                'mine' => $m->sender_id === $me,
                'recalled' => $m->isRecalled(),
                'time' => $m->created_at->format('d.m H:i'),
            ]);

        // Mevcut (daha önce çekilmiş) mesajlardan geri alınanların id'leri —
        // karşı taraf açık ekranda geri almayı görebilsin diye.
        $recalledIds = $conversation->messages()
            ->whereNotNull('recalled_at')
            ->where('id', '<=', $after)
            ->pluck('id');

        return response()->json(['messages' => $messages, 'recalled' => $recalledIds]);
    }

    /** İlan detayından satıcıya ilk mesajı gönder. */
    public function start(Request $request, Listing $listing): RedirectResponse
    {
        $user = $request->user();

        if ($user->id === $listing->user_id) {
            return back()->with('status', 'Kendi ilanına mesaj gönderemezsin.');
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            // Emlak (kısa dönem) müsaitlik talebi — hepsi isteğe bağlı
            'giris' => ['nullable', 'date'],
            'cikis' => ['nullable', 'date', 'after:giris'],
            'kisi' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $body = $this->prependAvailabilityRequest($listing, $data);

        $conversation = Conversation::findOrCreateBetween($user->id, $listing->user_id, $listing->id);

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $body,
        ]);
        $conversation->update(['last_message_at' => now()]);

        $listing->user->notify(new NewMessageNotification($message->body, $user->name, $conversation->id));

        return redirect()->route('panel.messages.show', $conversation)
            ->with('status', 'Mesajın gönderildi.');
    }

    /**
     * Emlak ilanında tarih seçilmişse mesajın başına yapılandırılmış müsaitlik
     * talebi bloğu ekle (gece sayısı + gecelik fiyattan tahmini toplam dahil).
     */
    private function prependAvailabilityRequest(Listing $listing, array $data): string
    {
        if ($listing->type !== ListingType::Emlak || empty($data['giris']) || empty($data['cikis'])) {
            return $data['body'];
        }

        $in = Carbon::parse($data['giris']);
        $out = Carbon::parse($data['cikis']);
        $nights = max(1, (int) $in->diffInDays($out));

        $parts = ['📅 Müsaitlik talebi: '.$in->format('d.m.Y').' → '.$out->format('d.m.Y').' ('.$nights.' gece)'];

        if (! empty($data['kisi'])) {
            $parts[] = $data['kisi'].' kişi';
        }

        if ($listing->price !== null && $listing->price_unit === PriceUnit::Gecelik) {
            $total = number_format($nights * (float) $listing->price, 0);
            $parts[] = 'tahmini '.number_format((float) $listing->price, 0).' × '.$nights.' = '.$total.' '.$listing->currency;
        }

        return implode(' · ', $parts)."\n\n".$data['body'];
    }
}
