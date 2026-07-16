<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Web push abonelik uçları (Faz M1.3). Tarayıcının PushManager.subscribe()
 * çıktısını kullanıcıya bağlar; gönderim tarafı için bkz.
 * App\Notifications\NewMessageNotification::toWebPush().
 *
 * NOT: Validasyon manuel Validator ile — bootstrap/app.php'deki
 * shouldRenderJsonWhen yalnızca api/* yollarında JSON render ettiği için
 * $request->validate() burada 422 yerine redirect üretirdi.
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => ['required', 'string', 'url', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:512'],
            'keys.auth' => ['required', 'string', 'max:512'],
            'content_encoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['content_encoding'] ?? 'aes128gcm',
        );

        return response()->json(['subscribed' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => ['required', 'string', 'url', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $request->user()->deletePushSubscription($validator->validated()['endpoint']);

        return response()->json(['subscribed' => false]);
    }
}
