<?php

namespace App\Services\Kahya\Dis;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Kalıcı engel (suppression) listesi — "istemeyene ikinci kez yazılmaz".
 *
 * ---------------------------------------------------------------------------
 * NEDEN AYRI SINIF (2026-08-07)
 *
 * Tablo F4'te kuruldu ama listeye adres EKLEYEN hiçbir kod yoktu: yalnız
 * `HamleGonderici` gönderim öncesi sorguluyordu. Yani liste teoride kalıcıydı,
 * pratikte hep boştu — dolması için sahibin veritabanına elle satır yazması
 * gerekiyordu ve bunun bir arayüzü de yoktu.
 *
 * Artık listeyi dolduran İKİ otomatik yol var ve ikisi de buradan geçiyor:
 *   · Alıcı postadaki "listeden çık" bağlantısına basar  (CikisController)
 *   · SES kalıcı bounce / şikâyet bildirir               (SesGeriBildirimController)
 *
 * Tek kapı olması şart: adres normalizasyonu (küçük harf + kırpma) tek yerde
 * kalmazsa "Info@X" ile "info@x" iki ayrı kayıt olur ve engel sessizce delinir.
 */
class EngelListesi
{
    public const TABLO = 'kahya_gonderim_engelleri';

    /** Karşılaştırma ve saklama için tek normalizasyon kuralı. */
    public static function normalize(string $eposta): string
    {
        return mb_strtolower(trim($eposta));
    }

    public function engelliMi(string $eposta): bool
    {
        return DB::table(self::TABLO)
            ->where('eposta', self::normalize($eposta))
            ->exists();
    }

    /**
     * Adresi kalıcı olarak engeller.
     *
     * IDEMPOTENT: aynı adres ikinci kez gelirse ilk gerekçe KORUNUR. Neden:
     * "kullanıcı kendi çıktı" ile "adres geçersiz" farklı şeyler ve ilki daha
     * ağır basar — sonradan gelen bir bounce onu ezip izi bulanıklaştırmamalı.
     *
     * @return bool Yeni kayıt açıldıysa true, zaten engelliyse false.
     */
    public function engelle(string $eposta, string $neden): bool
    {
        $adres = self::normalize($eposta);

        if ($adres === '' || ! filter_var($adres, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if ($this->engelliMi($adres)) {
            return false;
        }

        DB::table(self::TABLO)->insert([
            'eposta' => $adres,
            'neden' => mb_substr($neden, 0, 200),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Denetim izi: engel listesi büyümesi teslim edilebilirliğin sağlık
        // göstergesi; sessizce büyümemeli.
        Log::info('Kâhya engel listesine adres eklendi', ['neden' => $neden]);

        return true;
    }
}
