<?php

namespace App\Support;

/**
 * Acil yardım verisine tek erişim noktası.
 *
 * Numaralar `config/acil.php`'de; oradaki dosya başlığı bu verinin neden
 * panelden düzenlenemez olduğunu anlatır (güvenlik verisi, kod incelemesinden
 * geçmeli).
 */
class AcilNumaralar
{
    /**
     * Arayüze verilecek ülke → numara haritası. Alpine tarafında ülke
     * değiştikçe gösterilecek, o yüzden tek seferde JSON olarak geçiyoruz
     * (25 kayıt, boyutu ihmal edilebilir).
     *
     * @return array<string, array{genel: string, genel_etiket: string, polis?: string, ambulans?: string, itfaiye?: string, not: string}>
     */
    public static function harita(): array
    {
        $ham = config('acil.ulkeler', []);

        // `dogrulandi` yalnız bakım içindir, arayüze taşınmaz.
        return array_map(
            fn (array $k) => array_diff_key($k, ['dogrulandi' => null]),
            $ham
        );
    }

    /** @return array{numara: string, gosterim: string, aciklama: string} */
    public static function konsoloslukCagriMerkezi(): array
    {
        return config('acil.konsolosluk_cagri_merkezi');
    }
}
