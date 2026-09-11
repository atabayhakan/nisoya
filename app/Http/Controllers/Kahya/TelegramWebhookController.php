<?php

namespace App\Http\Controllers\Kahya;

use App\Http\Controllers\Controller;
use App\Jobs\TelegramGuncellemesiIsleJob;
use App\Services\Kahya\Dis\TelegramDinleyici;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Telegram Bot API webhook'u — Kâhya Telegram'ın tek giriş noktası.
 *
 * DOĞRULAMA: Telegram, `setWebhook` çağrısında verilen `secret_token`'ı her
 * istekte `X-Telegram-Bot-Api-Secret-Token` başlığıyla geri gönderir (bkz.
 * Kâhya Telegram ayarları sayfasındaki kurulum notu). Bu olmadan herkes bu
 * uca rastgele "Telegram mesajı" gönderip Kâhya'yı istismar edebilirdi —
 * SesGeriBildirimController'daki imza doğrulamasıyla aynı gerekçe.
 *
 * HTTP 200 CÖMERTLİĞİ: doğrulamadan geçen HER istek 200 alır (işlemenin
 * kendisi kuyruğa devredilir). Telegram 200 dışını başarısızlık sayıp
 * tekrar dener; anlamadığımız/gecikmeli bir güncelleme için onu bloke
 * etmenin anlamı yok.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramDinleyici $dinleyici): Response
    {
        if (! $dinleyici->hazirMi()) {
            Log::warning('Kâhya Telegram: webhook geldi ama yapılandırılmamış (token/grup boş).');

            return response('Yapılandırılmamış', 503);
        }

        if (! $dinleyici->dogruSir($request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            Log::warning('Kâhya Telegram: webhook doğru gizli anahtarı taşımıyor.');

            return response('Geçersiz', 403);
        }

        $update = json_decode($request->getContent(), true);

        if (is_array($update)) {
            TelegramGuncellemesiIsleJob::dispatch($update)->onConnection('database');
        }

        return response('İşlendi', 200);
    }
}
