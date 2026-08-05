<?php

use App\Support\Settings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mevcut düz metin sırları şifreler (2026-08-05).
 *
 * ---------------------------------------------------------------------------
 * NEDEN
 *
 * `site_settings.value` şifresiz bir TEXT kolonu ve SMTP parolası ile YZ
 * sağlayıcı anahtarları orada duruyordu. Panelin YEDEKLEME özelliği her
 * ZIP'e tam veritabanı dökümü koyduğu için (bkz. BackupService), indirilen
 * her yedek bu sırları AÇIK taşıyordu.
 *
 * Bundan sonrasını `Settings::setMany()` hallediyor; bu migration yalnız
 * canlıda ZATEN YAZILI olan değerleri devralır.
 *
 * ---------------------------------------------------------------------------
 * İDEMPOTENT
 *
 * Her değer önce çözülmeye çalışılır. Çözülüyorsa zaten şifrelidir ve
 * ATLANIR — aksi hâlde migration'ın tekrar koşması değeri katman katman
 * sarar ve geri döndürülemez hâle getirirdi.
 *
 * ---------------------------------------------------------------------------
 * GERİ ALMA BİLİNÇLİ OLARAK YOK
 *
 * down() sırları düz metne geri yazardı — yani bu migration'ı geri almak,
 * düzeltmeye çalıştığı sızıntıyı yeniden açmak olurdu. Şema değişmediği için
 * geri almaya teknik bir ihtiyaç da yok.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $satirlar = DB::table('site_settings')
            ->whereIn('key', Settings::SIRLI_ANAHTARLAR)
            ->get(['id', 'value']);

        foreach ($satirlar as $satir) {
            if ($satir->value === null || $satir->value === '') {
                continue;
            }

            if (Settings::sifreliMi($satir->value)) {
                continue; // Zaten şifreli.
            }

            DB::table('site_settings')
                ->where('id', $satir->id)
                ->update(['value' => Crypt::encryptString($satir->value)]);
        }

        Settings::forget();
    }

    public function down(): void
    {
        // Bilinçle boş — gerekçe sınıf açıklamasında.
    }
};
