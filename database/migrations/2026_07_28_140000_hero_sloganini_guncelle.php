<?php

use App\Support\Settings;
use Illuminate\Database\Migrations\Migration;

/**
 * Hero sloganını yeniler (2026-07-28).
 *
 * NEDEN MIGRATION: hero metinleri config/site_defaults.php'de değil,
 * site_settings TABLOSUNDA yaşıyor (canlıda hepsi yazılı). Yalnız config
 * varsayılanını değiştirmek siteyi HİÇ etkilemezdi.
 *
 * NEDEN KORUMALI: her değer ancak BUGÜNKÜ değeri eski varsayılana birebir
 * eşitse güncellenir. Sahip aradan sonra kendi metnini yazdıysa dokunulmaz —
 * bir migration kullanıcının yazdığı içeriği asla ezmemeli.
 *
 * Yeni metin, çok-ajanlı bir tasarım turunun çıktısı: jüri "Öneri 3'ün
 * kurgusuna Öneri 1'in metni giydirilsin" dedi. Duygusal gerçek: yabancı bir
 * şehirde bozulan kombiyi el kol hareketiyle anlatmaya çalışma anı.
 */
return new class extends Migration
{
    private const DEGISIKLIKLER = [
        'home.hero_satir1' => [
            'eski' => 'Nakliyeci mi, hoca mı?',
            'yeni' => 'Tarif etmeye çalışma.',
        ],
        'home.hero_vurgu' => [
            'eski' => 'Hepsi burada, Türkçe.',
            'yeni' => 'Türkçe anlat, iş bitsin.',
        ],
        'home.hero_aciklama' => [
            'eski' => 'Taşınma, ders, tamir, ev yemeği, davetiye — yaşadığın şehirde Türkçe konuşan birini dakikalar içinde bul, direkt yaz.',
            'yeni' => 'Nakliyeci, hoca, tamirci, kuaför, tercüman — şehrindeki Türkçe konuşan kişiyi bul, aracısız yaz, işini gör.',
        ],
        // Placeholder dokunmatikte 16px'e çıkınca kesin kırpılıyordu; örnek
        // zaten çip satırında var, burada tekrarına gerek yok.
        'home.arama_placeholder' => [
            'eski' => "Kim lazım? (ör. Berlin'de nakliyeci)",
            'yeni' => 'Kim lazım?',
        ],
    ];

    public function up(): void
    {
        $yazilacak = [];

        foreach (self::DEGISIKLIKLER as $anahtar => $d) {
            if (Settings::get($anahtar) === $d['eski']) {
                $yazilacak[$anahtar] = $d['yeni'];
            }
        }

        if ($yazilacak !== []) {
            Settings::setMany($yazilacak);
        }
    }

    public function down(): void
    {
        $yazilacak = [];

        foreach (self::DEGISIKLIKLER as $anahtar => $d) {
            if (Settings::get($anahtar) === $d['yeni']) {
                $yazilacak[$anahtar] = $d['eski'];
            }
        }

        if ($yazilacak !== []) {
            Settings::setMany($yazilacak);
        }
    }
};
