<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Settings
{
    public const CACHE_KEY = 'site_settings';

    /**
     * Veritabanında ŞİFRELİ saklanan ayarlar.
     *
     * -----------------------------------------------------------------------
     * NEDEN (2026-08-05)
     *
     * `site_settings.value` düz metin bir TEXT kolonu. SMTP parolası ve YZ
     * sağlayıcı anahtarları da orada duruyordu — yani:
     *
     *   · Veritabanı dökümü sızarsa sırlar doğrudan okunur.
     *   · Panelin YEDEKLEME özelliği her ZIP'e tam veritabanı dökümü koyuyor
     *     (bkz. BackupService). İndirilen her yedek, sırları açık taşıyordu.
     *
     * Proje 2FA sırlarını zaten şifreliyordu ve gerekçesi User modelinde
     * yazılı: "DB sızıntısı TOTP secret'ını ifşa etmesin". İlke kuruluydu,
     * ayarlara uygulanmamıştı.
     *
     * Liste DAR TUTULUYOR: her ayarı şifrelemek arama/filtrelemeyi bozar ve
     * hata ayıklamayı zorlaştırır. Yalnız gerçekten sır olanlar.
     */
    public const SIRLI_ANAHTARLAR = [
        'mail.password',
        'ai.api_anahtari',
        'growth.google_places_api_key',
        'kahya.gonderim_parola',
    ];

    /** Tüm ayarları (key => value) cache'den döndürür. */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => SiteSetting::query()->pluck('value', 'key')->all());
    }

    /** Bir ayarı döndürür; DB'de yoksa config varsayılanına, o da yoksa $default'a düşer. */
    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::all();

        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return self::coz($key, $all[$key]);
        }

        // Not: anahtarlar nokta içerdiği için config() nokta-notasyonu yerine
        // diziyi doğrudan literal anahtarla indeksliyoruz.
        $fields = config('site_defaults.fields', []);

        return $fields[$key]['default'] ?? $default;
    }

    /** Bu anahtar sır mı? */
    public static function sirliMi(string $key): bool
    {
        return in_array($key, self::SIRLI_ANAHTARLAR, true);
    }

    /**
     * Sırlı anahtarı yazmadan önce şifreler; diğerlerini olduğu gibi bırakır.
     *
     * Zaten şifreli bir değer TEKRAR şifrelenmez — aksi hâlde aynı ayarı iki
     * kez kaydetmek katman katman sarardı ve migration'ın yeniden çalışması
     * değeri bozardı.
     */
    private static function sifrele(string $key, ?string $value): ?string
    {
        if (! self::sirliMi($key) || $value === null || $value === '') {
            return $value;
        }

        return self::sifreliMi($value) ? $value : Crypt::encryptString($value);
    }

    /**
     * Sırlı anahtarı okurken çözer.
     *
     * Çözülemeyen değer (APP_KEY değişmiş, satır bozulmuş, migration öncesi
     * düz metin kalıntısı) İSTİSNA FIRLATMAZ: null döner. Sebep, Kurtarma
     * Kiti'nde öğrenildi — tek bozuk satırın koca bir sayfayı 500'e düşürmesi.
     * Burada bedeli daha da yüksek olurdu: `mail.password` her istekte
     * (AppServiceProvider::mergeMailConfig) okunuyor, yani TÜM SİTE düşerdi.
     *
     * null dönmek "parola yok" demektir; e-posta gönderimi durur ama site
     * ayakta kalır ve panelden yeniden girilebilir.
     */
    private static function coz(string $key, string $value): ?string
    {
        if (! self::sirliMi($key)) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    /** Değer Laravel şifreli yükü mü? (migration idempotentliği için) */
    public static function sifreliMi(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }

    /** Ayarları toplu kaydeder (upsert) ve cache'i temizler. */
    public static function setMany(array $values): void
    {
        $fields = config('site_defaults.fields', []);

        foreach ($values as $key => $value) {
            $group = $fields[$key]['group'] ?? 'genel';
            SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => self::sifrele($key, $value), 'group' => $group]);
        }

        self::forget();

        self::logChange(array_keys($values));
    }

    /**
     * Denetim izi: kim hangi ayar alanını değiştirdi (Faz 4 · İşlem Geçmişi).
     * Yalnızca panelden (kimlik doğrulanmış) yapılan değişiklikler loglanır;
     * seeder/console/test sessizce atlanır. Değerler DEĞİL, yalnızca anahtarlar
     * kaydedilir (parola gibi hassas değerler günlüğe sızmaz).
     *
     * @param  array<int,string>  $keys
     */
    protected static function logChange(array $keys): void
    {
        if ($keys === [] || ! auth()->check()) {
            return;
        }

        try {
            activity('ayar')
                ->causedBy(auth()->user())
                ->withProperties(['keys' => $keys])
                ->log(self::describeChange($keys));
        } catch (\Throwable) {
            // Denetim kaydı ayar kaydını asla bozmamalı.
        }
    }

    /**
     * Değişen anahtarların ön eklerinden okunur bir alan adı üretir
     * (ör. "E-posta (SMTP), Modüller ayarları güncellendi").
     *
     * @param  array<int,string>  $keys
     */
    protected static function describeChange(array $keys): string
    {
        $labels = [
            'mail' => 'E-posta (SMTP)', 'mail_template' => 'E-posta metinleri',
            'modul' => 'Modüller', 'seo' => 'SEO', 'duyuru' => 'Duyuru bandı',
            'ai' => 'Yapay zeka', 'home' => 'Anasayfa', 'gorunum' => 'Görünüm',
            'hero' => 'Hero (Vitrin)',
            'reklam' => 'Reklam', 'bagis' => 'Bağış', 'nabiz' => 'Nabız',
            'iletisim' => 'İletişim', 'footer' => 'Footer', 'header' => 'Header',
            'genel' => 'Genel',
        ];

        $prefixes = array_values(array_unique(array_map(
            fn (string $key): string => explode('.', $key)[0],
            $keys
        )));

        $names = array_map(fn (string $prefix): string => $labels[$prefix] ?? $prefix, $prefixes);
        $shown = implode(', ', array_slice($names, 0, 4));

        return $shown.' ayarları güncellendi';
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
