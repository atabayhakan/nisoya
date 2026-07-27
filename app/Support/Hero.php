<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Hero Yöneticisi (Vitrin — Faz P3) okuma katmanı.
 *
 * MİMARİ KARARI: ayrı bir `hero_settings` tablosu YERİNE `hero.*` ön ekli
 * site_settings anahtarları kullanılır. Gerekçe: bu depodaki 16 admin
 * sayfasının tamamı Settings::setMany() üzerinden yazıyor ve cache
 * invalidasyonu (Settings::forget) ile denetim izini (Settings::logChange)
 * oradan alıyor. Ayrı tablo bu ikisini elle yeniden yazmayı gerektirirdi.
 * Kampanya/A-B ikinci tura ertelendiği için tarih tabanlı cache
 * invalidasyonu bugün gerekmiyor — ertelemenin asıl teknik gerekçesi bu.
 *
 * Hero Yöneticisi VİTRİN temasına özgüdür; klasik tema hero metinlerini
 * `home.hero_*` üzerinden (İçerik sayfası) yönetmeye devam eder. Vitrin
 * hero'su, kendi alanı boşsa klasik değere düşer — böylece panel hiç
 * açılmadan da tutarlı çalışır (geri uyum).
 */
final class Hero
{
    public const DUZENLER = ['bento', 'sahne'];

    /** Bloklar: anahtar => [etiket, açıklama]. Sıra ve açık/kapalılık ayardan gelir. */
    public const BLOKLAR = [
        'arama' => ['Arama kutusu', 'Anahtar kelime + ülke seçici'],
        'populer_etiketler' => ['Popüler etiketler', 'Aramaya kısayol çipleri'],
        'canli_sayaclar' => ['Canlı sayaçlar', 'Ülke · kategori · şehir sayıları'],
    ];

    /** 3×3 odak noktası → CSS object-position karşılığı. */
    public const ODAKLAR = [
        'sol-ust' => 'left top', 'merkez-ust' => 'center top', 'sag-ust' => 'right top',
        'sol-merkez' => 'left center', 'merkez' => 'center', 'sag-merkez' => 'right center',
        'sol-alt' => 'left bottom', 'merkez-alt' => 'center bottom', 'sag-alt' => 'right bottom',
    ];

    public static function duzen(): string
    {
        $duzen = Settings::get('hero.duzen', 'bento');

        return in_array($duzen, self::DUZENLER, true) ? $duzen : 'bento';
    }

    /** Başlığın ilk satırı — boşsa klasik hero metnine düşer. */
    public static function baslik(): string
    {
        return trim((string) (Settings::get('hero.baslik') ?: setting('home.hero_satir1', '')));
    }

    /** Başlığın vurgulu (birincil renkli) ikinci satırı. */
    public static function vurgu(): string
    {
        return trim((string) (Settings::get('hero.vurgu') ?: setting('home.hero_vurgu', '')));
    }

    public static function altBaslik(): string
    {
        return trim((string) (Settings::get('hero.alt_baslik') ?: setting('home.hero_aciklama', '')));
    }

    public static function rozet(): string
    {
        return trim((string) (Settings::get('hero.rozet') ?: setting('home.hero_badge', '')));
    }

    /**
     * Birincil/ikincil çağrı butonları. İkincil yalnız açıkken ve etiketi
     * doluyken döner — kapalı buton DOM'a hiç basılmaz.
     *
     * @return array{etiket: string, url: string}|null
     */
    public static function cta(int $sira): ?array
    {
        if ($sira === 2 && Settings::get('hero.cta2_aktif', '0') !== '1') {
            return null;
        }

        $etiket = trim((string) Settings::get("hero.cta{$sira}_etiket", ''));
        $url = trim((string) Settings::get("hero.cta{$sira}_url", ''));

        if ($etiket === '' || $url === '') {
            return null;
        }

        return ['etiket' => $etiket, 'url' => $url];
    }

    /** Arka plan tipi: yok | gorsel | video */
    public static function arkaplanTipi(): string
    {
        $tip = Settings::get('hero.arkaplan_tipi', 'yok');

        return in_array($tip, ['yok', 'gorsel', 'video'], true) ? $tip : 'yok';
    }

    /**
     * Arka plan görselinin URL'i (mobil ayrı yüklendiyse onu tercih eder).
     * Depolama yolu ya da tam URL kabul edilir (highlight-media ile aynı
     * sözleşme: http(s):// veya / ile başlıyorsa ham kullanılır).
     */
    public static function arkaplanGorseli(bool $mobil = false): ?string
    {
        $yol = $mobil
            ? (Settings::get('hero.gorsel_mobil') ?: Settings::get('hero.gorsel_masaustu'))
            : Settings::get('hero.gorsel_masaustu');

        return self::medyaUrl($yol);
    }

    public static function videoUrl(): ?string
    {
        return self::medyaUrl(Settings::get('hero.video_url'));
    }

    /** Karartma yüzdesi (0-100) — metnin görsel üzerinde okunabilirliği için. */
    public static function overlay(): int
    {
        return max(0, min(100, (int) Settings::get('hero.overlay', '58')));
    }

    /** Odak noktasının CSS object-position karşılığı. */
    public static function odakCss(): string
    {
        return self::ODAKLAR[Settings::get('hero.odak', 'merkez')] ?? 'center';
    }

    /**
     * Görünür blokların anahtarları — KAYITLI SIRAYLA.
     * Kapalı blok listeye hiç girmez (Blade tarafında @if ile atlanır,
     * CSS ile gizlenmez — handoff'un "kapalı blok DOM'a basılmaz" kuralı).
     *
     * @return array<int,string>
     */
    public static function aktifBloklar(): array
    {
        $ham = json_decode((string) Settings::get('hero.bloklar', ''), true);

        // Ayar hiç yazılmadıysa/bozuksa: tüm bloklar tanım sırasıyla açık.
        if (! is_array($ham) || $ham === []) {
            return array_keys(self::BLOKLAR);
        }

        return collect($ham)
            ->filter(fn ($blok) => is_array($blok)
                && isset($blok['anahtar'])
                && array_key_exists($blok['anahtar'], self::BLOKLAR)
                && ! empty($blok['acik']))
            ->pluck('anahtar')
            ->unique()
            ->values()
            ->all();
    }

    public static function blokAcikMi(string $anahtar): bool
    {
        return in_array($anahtar, self::aktifBloklar(), true);
    }

    /**
     * Filament formunun beklediği blok listesi: kayıtlı sıra korunur,
     * tanımlı ama kayıtta olmayan bloklar sona açık olarak eklenir
     * (yeni blok eklendiğinde panel onu kaybetmesin).
     *
     * @return array<int,array{anahtar: string, acik: bool}>
     */
    public static function blokFormDurumu(): array
    {
        $ham = json_decode((string) Settings::get('hero.bloklar', ''), true);
        $ham = is_array($ham) ? $ham : [];

        $sirali = collect($ham)
            ->filter(fn ($b) => is_array($b) && isset($b['anahtar']) && array_key_exists($b['anahtar'], self::BLOKLAR))
            ->map(fn ($b) => ['anahtar' => $b['anahtar'], 'acik' => (bool) ($b['acik'] ?? false)])
            ->unique('anahtar')
            ->values();

        $eksikler = collect(array_keys(self::BLOKLAR))
            ->diff($sirali->pluck('anahtar'))
            ->map(fn (string $anahtar) => ['anahtar' => $anahtar, 'acik' => true]);

        return $sirali->concat($eksikler)->values()->all();
    }

    /** Depolama yolu veya tam URL → tarayıcıya verilebilir URL. */
    private static function medyaUrl(?string $ham): ?string
    {
        $ham = trim((string) $ham);

        if ($ham === '') {
            return null;
        }

        if (str_starts_with($ham, 'http://') || str_starts_with($ham, 'https://') || str_starts_with($ham, '/')) {
            return $ham;
        }

        return Storage::disk('public')->url($ham);
    }
}
