<?php

namespace App\Support;

/**
 * Anasayfa içerik bölümlerinin göster/gizle durumu (Faz 2 · G5).
 *
 * Sahibin anasayfada hangi bölümün görüneceğini panelden seçebilmesi. Değer
 * DB'de (site_settings, anahtar "home.section.<key>"); yoksa varsayılan GÖRÜNÜR.
 *
 * Not: Hero (her zaman), Nisoya Nabzı (nabiz.* ayarları), Nabız Haritası
 * (tasarım modu) ve reklam Zone'ları buraya DAHİL DEĞİL — kendi gate'leri var.
 *
 * SIRA (G5): site_settings "home.sira.<tema>", yoksa o temanın varsayılan sırası.
 *
 * NEDEN SIRA TEMA BAŞINA AYRI: Klasik ve Vitrin anasayfalarının bölüm sırası
 * BUGÜN de farklı (klasikte canlı akış en üstte, Vitrin'de kategori şeridi).
 * Tek ortak sıra ayarı, hangi temaya yazılırsa yazılsın diğerinin mevcut
 * görünümünü bozardı.
 *
 * ÇAPALAR: Sıralanamayan bloklar (Nisoya Nabzı, orta reklam alanı) bugünkü
 * yerlerini korusun diye bir SIRA İNDEKSİNE çapalanır — "şu kaçıncı bölümden
 * sonra basılır". Varsayılan sırada bu, bugünkü sayfanın birebir aynısını
 * üretir; sahip sırayı değiştirince çapa kendi indeksinde kalır, yani orta
 * reklam alanı sayfanın ortasında kalmaya devam eder.
 */
class HomeSections
{
    /** Yönetilebilir bölümler: anahtar => insan-okunur etiket. */
    public const SECTIONS = [
        'canli_akis' => 'Canlı akış şeridi (son ilanlar)',
        'deger_onerileri' => 'Değer önerileri + öne çıkanlar',
        'kategoriler' => 'Kategoriler',
        'ulkeler' => 'Ülkeler',
        'yeni_ilanlar' => 'Yeni ilanlar',
        'nasil_calisir' => 'Nasıl çalışır',
        'cta' => 'Kayıt çağrısı (CTA)',
    ];

    /**
     * Temaların BUGÜNKÜ bölüm sırası — değiştirmek görünümü değiştirir.
     *
     * Vitrin'de canlı akış şeridi yoktur (hero kendi akışını taşır), bu yüzden
     * listede geçmez ve Vitrin'de hiç sıralanmaz.
     *
     * @var array<string, array<int, string>>
     */
    public const VARSAYILAN_SIRA = [
        'klasik' => ['canli_akis', 'deger_onerileri', 'kategoriler', 'ulkeler', 'yeni_ilanlar', 'nasil_calisir', 'cta'],
        'vitrin' => ['kategoriler', 'yeni_ilanlar', 'deger_onerileri', 'ulkeler', 'nasil_calisir', 'cta'],
    ];

    /**
     * Sıralanamayan blokların çapaları: hangi indeksten SONRA basılırlar.
     *
     * @var array<string, array<string, int>>
     */
    public const CAPALAR = [
        'klasik' => ['nabiz' => 0, 'zone_orta' => 3],
        'vitrin' => ['nabiz' => 1],
    ];

    /** Bölüm görünür mü? Ayarsız/bilinmeyen anahtar varsayılan olarak GÖRÜNÜR. */
    public static function visible(string $key): bool
    {
        return (Settings::get("home.section.{$key}") ?? '1') === '1';
    }

    /**
     * Tüm bölümlerin durumu.
     *
     * @return array<string,bool>
     */
    public static function all(): array
    {
        $out = [];
        foreach (array_keys(self::SECTIONS) as $key) {
            $out[$key] = self::visible($key);
        }

        return $out;
    }

    /**
     * Temanın geçerli bölüm sırası.
     *
     * Kayıtlı sıra bozuk ya da eksik olsa bile sayfa ASLA bölüm kaybetmez:
     * bilinmeyen anahtarlar atılır, yinelenenler tekilleşir, eksik kalanlar
     * varsayılan sırasıyla sona eklenir. Böylece ileride yeni bir bölüm
     * eklendiğinde de kendiliğinden görünür.
     *
     * @return array<int, string>
     */
    public static function sirali(string $tema): array
    {
        $varsayilan = self::VARSAYILAN_SIRA[$tema] ?? self::VARSAYILAN_SIRA['klasik'];
        $kayitli = Settings::get("home.sira.{$tema}");

        if (! is_string($kayitli) || trim($kayitli) === '') {
            return $varsayilan;
        }

        $secilen = array_values(array_unique(array_filter(
            array_map('trim', explode(',', $kayitli)),
            fn (string $anahtar) => in_array($anahtar, $varsayilan, true),
        )));

        foreach ($varsayilan as $anahtar) {
            if (! in_array($anahtar, $secilen, true)) {
                $secilen[] = $anahtar;
            }
        }

        return $secilen;
    }

    /** Sıralanamayan bloğun çapa indeksi (o temada yoksa null). */
    public static function capa(string $tema, string $ad): ?int
    {
        return self::CAPALAR[$tema][$ad] ?? null;
    }

    /**
     * Sırayı kaydeder; geçersiz anahtarlar sessizce atılır.
     *
     * @param  array<int, mixed>  $sira
     */
    public static function siraKaydet(string $tema, array $sira): void
    {
        $varsayilan = self::VARSAYILAN_SIRA[$tema] ?? [];
        $temiz = array_values(array_unique(array_filter(
            $sira,
            fn ($anahtar) => is_string($anahtar) && in_array($anahtar, $varsayilan, true),
        )));

        Settings::setMany(["home.sira.{$tema}" => implode(',', $temiz)]);
    }

    /**
     * Sıralanabilir temalar.
     *
     * @return array<int, string>
     */
    public static function temalar(): array
    {
        return array_keys(self::VARSAYILAN_SIRA);
    }
}
