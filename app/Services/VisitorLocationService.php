<?php

namespace App\Services;

use App\Models\Country;
use GeoIp2\Database\Reader;
use Illuminate\Http\Request;

class VisitorLocationService
{
    /**
     * Ziyaretçinin IP'sinden ülkesini çözer. Yerel MaxMind GeoLite2 veritabanını
     * kullanır (ticari sitelerde kullanımı yasak olan ip-api.com/ipapi.co gibi
     * dış API'lerin aksine, dış çağrı yapmaz — bkz. config/services.php).
     * Veritabanı henüz indirilmemişse, IP özel/yerelse ya da çözümlenemezse
     * sessizce null döner — header bayrağı o zaman gösterilmez.
     *
     * @return object{code: string, name: string, emoji: string}|null
     */
    public function resolve(Request $request): ?object
    {
        if (app()->runningUnitTests()) {
            return null;
        }

        // Açıkça URL'den belirtilen ülke (?ulke=KG veya ?test_country=KG)
        if ($request->filled('ulke')) {
            $code = strtoupper(substr($request->string('ulke'), 0, 2));
            if ($request->hasSession()) {
                $request->session()->put('visitor_country_code', $code);
            }

            return $this->fromCode($code);
        }

        if (config('app.debug') && $request->filled('test_country')) {
            $code = strtoupper(substr($request->string('test_country'), 0, 2));
            if ($request->hasSession()) {
                $request->session()->put('visitor_country_code', $code);
            }

            return $this->fromCode($code);
        }

        // Giriş yapmış üyenin yaşadığı ülke her zaman birincil önceliktir
        if ($userCountry = $request->user()?->country_code) {
            $code = strtoupper(trim((string) $userCountry));
            if ($code !== '') {
                if ($request->hasSession() && ! $request->session()->has('visitor_country_code')) {
                    $request->session()->put('visitor_country_code', $code);
                }

                return $this->fromCode($code);
            }
        }

        // Oturumda kayıtlı ülke tercihi
        if ($request->hasSession() && $request->session()->has('visitor_country_code')) {
            $code = $request->session()->get('visitor_country_code');

            return $code ? $this->fromCode($code) : null;
        }

        $path = config('services.maxmind.database_path');
        $code = null;

        if (is_file($path)) {
            try {
                $code = (new Reader($path))->country($request->ip())->country->isoCode;
            } catch (\Throwable $e) {
                // Özel/yerel IP, veritabanında bulunamadı, dosya bozuk vb.
                $code = null;
            }
        }

        if ($request->hasSession()) {
            $request->session()->put('visitor_country_code', $code);
        }

        return $code ? $this->fromCode($code) : null;
    }

    /** Ülke koduna göre bayrak + Türkçe ad üretir — önce kürate edilmiş Country tablosuna bakar. */
    protected function fromCode(string $code): ?object
    {
        if (! preg_match('/^[A-Z]{2}$/', $code)) {
            return null;
        }

        $curated = Country::find($code);

        if ($curated) {
            return (object) ['code' => $code, 'name' => $curated->name_tr, 'emoji' => $curated->emoji];
        }

        // Curated listede olmayan ülkeler (ör. Türkiye'nin kendisi, ya da
        // hedef diaspora ülkeleri dışından bir ziyaretçi) için intl uzantısıyla
        // Türkçe ad üretilir; uzantı yoksa ülke kodu ad olarak kullanılır.
        $name = extension_loaded('intl') ? \Locale::getDisplayRegion('und-'.$code, 'tr') : null;

        return (object) [
            'code' => $code,
            'name' => $name ?: $code,
            'emoji' => mb_chr(127397 + ord($code[0])).mb_chr(127397 + ord($code[1])),
        ];
    }
}
