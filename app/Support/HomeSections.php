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
 * Bölüm SIRASI bu sürümde sabit; sürükle-sırala ayrı bir adımda (bölümlerin
 * partial'lara çıkarılmasını gerektirir).
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
}
