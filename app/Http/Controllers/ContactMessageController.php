<?php

namespace App\Http\Controllers;

use App\Enums\ContactCategory;
use App\Models\ContactMessage;
use App\Notifications\NewContactMessageNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ContactMessageController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'category' => ['required', Rule::enum(ContactCategory::class)],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ], attributes: [
            'name' => 'ad soyad', 'email' => 'e-posta', 'phone' => 'telefon',
            'category' => 'konu', 'message' => 'mesaj',
        ]);

        $contactMessage = ContactMessage::create([
            ...$data,
            'user_id' => $request->user()?->id,
        ]);

        Notification::route('mail', setting('iletisim.eposta', 'destek@nisoya.com'))
            ->notify(new NewContactMessageNotification($contactMessage));

        return back()->with('status', 'Mesajın bize ulaştı, teşekkürler! En kısa sürede dönüş yapacağız.');
    }
}
