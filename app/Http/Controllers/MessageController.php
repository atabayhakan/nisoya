<?php

namespace App\Http\Controllers;

use App\Enums\ListingType;
use App\Enums\PriceUnit;
use App\Models\Conversation;
use App\Models\Currency;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\ImageModerationService;
use App\Services\ImageService;
use App\Services\ProfanityFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
            'deal' => $conversation->latestDeal(),
            'currencies' => Currency::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        abort_unless($conversation->isParticipant($request->user()), 403);

        // Manuel Validator: bootstrap/app.php'deki shouldRenderJsonWhen yalnızca
        // api/* için JSON döndürüyor; panel fetch uçlarında $request->validate()
        // 422 yerine redirect üretir. Bu yüzden hataları elle JSON'a çeviriyoruz.
        $validator = Validator::make($request->all(), [
            'body' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
        ]);

        if ($validator->fails()) {
            return $request->wantsJson()
                ? response()->json(['errors' => $validator->errors()], 422)
                : back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $type = Message::TYPE_TEXT;
        $body = trim((string) ($data['body'] ?? ''));
        $attachmentPath = null;

        if ($body !== '') {
            $profanityError = app(ProfanityFilterService::class)->validateText($body);
            if ($profanityError) {
                return $request->wantsJson()
                    ? response()->json(['message' => $profanityError], 422)
                    : back()->withErrors(['body' => $profanityError])->withInput();
            }
        }

        if ($request->hasFile('photo')) {
            // Sohbet fotoğrafı: EXIF/GPS strip edilir (konum sızmasın).
            $type = Message::TYPE_IMAGE;
            $attachmentPath = app(ImageService::class)->storeSingle(
                $request->file('photo'),
                'message-attachments/'.$conversation->id,
            );

            // AI moderasyonu: uygunsuz bulunursa mesaj hiç oluşturulmaz (fail-open
            // — AI kapalı/başarısızsa buradan hiç geçmeden devam eder). SENKRON
            // (istek içi) olduğu için kısa timeout ile çağrılır: yavaş AI gününde
            // kullanıcı 30s beklemesin, hızlıca fail-open'a düşülsün. Güvenlik
            // modeli korunur (uygunsuz foto yine gönderilmeden reddedilir);
            // yalnızca AI ÇOK yavaşsa moderasyonsuz geçme penceresi kısalır.
            $moderation = app(ImageModerationService::class)->check(
                Storage::disk('public')->path($attachmentPath),
                ImageModerationService::SYNC_TIMEOUT_SECONDS,
            );
            if ($moderation && $moderation['flagged']) {
                Storage::disk('public')->delete($attachmentPath);

                return $request->wantsJson()
                    ? response()->json(['message' => 'Bu fotoğraf gönderilemedi.'], 422)
                    : back()->withErrors(['photo' => 'Bu fotoğraf gönderilemedi.']);
            }
        } elseif (isset($data['lat'], $data['lng'])) {
            $type = Message::TYPE_LOCATION;
            $body = $data['lat'].','.$data['lng'];
        } elseif ($body === '') {
            // Boş metin mesajı — reddet.
            return $request->wantsJson()
                ? response()->json(['message' => 'Mesaj boş olamaz.'], 422)
                : back();
        }

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'type' => $type,
            'body' => $body,
            'attachment_path' => $attachmentPath,
        ]);
        $conversation->update(['last_message_at' => now()]);

        // Yazıyor bayrağını temizle (mesaj gönderildi).
        Cache::forget($this->typingKey($conversation, $request->user()->id));

        $conversation->other($request->user())?->notify(
            new NewMessageNotification($this->notificationPreview($message), $request->user()->name, $conversation->id)
        );

        if ($request->wantsJson()) {
            return response()->json($message->toChatArray($request->user()->id));
        }

        return redirect()->route('panel.messages.show', $conversation);
    }

    /** Bildirim önizlemesi: metin gövdesi ya da tür etiketi. */
    private function notificationPreview(Message $message): string
    {
        return match ($message->type) {
            Message::TYPE_IMAGE => '📷 Fotoğraf',
            Message::TYPE_LOCATION => '📍 Konum',
            default => $message->body,
        };
    }

    /** "Yazıyor..." bayrağı için cache anahtarı (konuşma + kullanıcı). */
    private function typingKey(Conversation $conversation, int $userId): string
    {
        return "chat-typing:{$conversation->id}:{$userId}";
    }

    /** Kullanıcı yazıyor sinyali — kısa TTL'li cache bayrağı (poll'a piggyback). */
    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->isParticipant($request->user()), 403);

        Cache::put($this->typingKey($conversation, $request->user()->id), true, now()->addSeconds(6));

        return response()->json(['ok' => true]);
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

        // Okunmamış mesajları "okundu" işaretle — ama YALNIZCA gerçekten
        // okunmamış varsa. İstemci ~2 sn'de bir poll'ladığı için koşulsuz UPDATE
        // her poll'da (okunacak bir şey olmasa bile) bir yazma üretiyordu; önce
        // hafif bir EXISTS ile gereksiz yazmayı ele.
        $hasUnread = $conversation->messages()
            ->where('sender_id', '!=', $me)
            ->whereNull('read_at')
            ->exists();

        if ($hasUnread) {
            $conversation->messages()
                ->where('sender_id', '!=', $me)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $messages = $conversation->messages()
            ->where('id', '>', $after)
            ->orderBy('id')
            ->get()
            ->map(fn (Message $m) => $m->toChatArray($me));

        // Mevcut (daha önce çekilmiş) mesajlardan geri alınanların id'leri —
        // karşı taraf açık ekranda geri almayı görebilsin diye.
        $recalledIds = $conversation->messages()
            ->whereNotNull('recalled_at')
            ->where('id', '<=', $after)
            ->pluck('id');

        // Karşı taraf yazıyor mu? (kısa TTL'li cache bayrağı)
        $otherId = $conversation->other($request->user())?->id;
        $typing = $otherId ? Cache::has($this->typingKey($conversation, $otherId)) : false;

        return response()->json([
            'messages' => $messages,
            'recalled' => $recalledIds,
            'typing' => $typing,
        ]);
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

        $profanityError = app(ProfanityFilterService::class)->validateText($data['body']);
        if ($profanityError) {
            return back()->withErrors(['body' => $profanityError])->withInput();
        }

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
     * Bir KİŞİYE doğrudan mesaj (ilan üzerinden değil, profilinden).
     *
     * KIRIK HUNİ: mesajlaşma yalnız bir ilan üzerinden başlatılabiliyordu.
     * Aktif ilanı olmayan bir yetenek /adaylar listesinde görünüyor, profiline
     * girilebiliyor ama kendisine ULAŞILAMIYORDU — yani "yeteneğini paraya
     * dönüştür" vaadinin karşılığı olan tek yol kapalıydı.
     * conversations.listing_id zaten nullable; eksik olan giriş noktasıydı.
     */
    public function startWithUser(Request $request, User $user): RedirectResponse
    {
        $gonderen = $request->user();

        if ($gonderen->id === $user->id) {
            return back()->with('status', 'Kendine mesaj gönderemezsin.');
        }

        $data = $request->validate(
            ['body' => ['required', 'string', 'max:2000']],
            attributes: ['body' => 'mesaj'],
        );

        $profanityError = app(ProfanityFilterService::class)->validateText($data['body']);
        if ($profanityError) {
            return back()->withErrors(['body' => $profanityError])->withInput();
        }

        $conversation = Conversation::findOrCreateBetween($gonderen->id, $user->id, null);

        $message = $conversation->messages()->create([
            'sender_id' => $gonderen->id,
            'body' => $data['body'],
        ]);
        $conversation->update(['last_message_at' => now()]);

        $user->notify(new NewMessageNotification($message->body, $gonderen->name, $conversation->id));

        return redirect()->route('panel.messages.show', $conversation)
            ->with('status', 'Mesajın gönderildi.');
    }

    /**
     * Emlak/vasıta ilanında tarih seçilmişse mesajın başına yapılandırılmış
     * müsaitlik talebi bloğu ekle (süre + birim fiyattan tahmini toplam dahil).
     */
    private function prependAvailabilityRequest(Listing $listing, array $data): string
    {
        $calendarTypes = [ListingType::Emlak, ListingType::Vasita];

        if (! in_array($listing->type, $calendarTypes, true) || empty($data['giris']) || empty($data['cikis'])) {
            return $data['body'];
        }

        $in = Carbon::parse($data['giris']);
        $out = Carbon::parse($data['cikis']);
        $units = max(1, (int) $in->diffInDays($out));
        $unitWord = $listing->type === ListingType::Emlak ? 'gece' : 'gün';

        $parts = ['📅 Müsaitlik talebi: '.$in->format('d.m.Y').' → '.$out->format('d.m.Y').' ('.$units.' '.$unitWord.')'];

        if (! empty($data['kisi'])) {
            $parts[] = $data['kisi'].' kişi';
        }

        // Süre birimiyle fiyat birimi uyuşuyorsa tahmini toplam göster
        $perUnitPrices = [PriceUnit::Gecelik, PriceUnit::Gunluk];
        if ($listing->price !== null && in_array($listing->price_unit, $perUnitPrices, true)) {
            $total = number_format($units * (float) $listing->price, 0);
            $parts[] = 'tahmini '.number_format((float) $listing->price, 0).' × '.$units.' = '.$total.' '.$listing->currency;
        }

        return implode(' · ', $parts)."\n\n".$data['body'];
    }
}
