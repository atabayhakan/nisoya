<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * User::casts()'a two_factor_secret / two_factor_recovery_codes için 'encrypted'
 * cast eklendi (2FA login challenge, Tranş B). Bu cast, DB'deki değerleri okurken
 * ÇÖZMEYE çalışır — mevcut DÜZ-METİN değerler DecryptException fırlatır.
 *
 * Bu migration mevcut düz-metin değerleri güvenli hale getirir:
 *  - Onaylı 2FA kullanıcıları (two_factor_confirmed_at != null): düz-metin
 *    secret/recovery şifrelenir, böylece yeni cast onları çözebilir.
 *  - Onaylanmamış artık kurulumlar (secret var ama confirmed_at null): 2FA hiç
 *    login akışına bağlı olmadığı için bu secret hiçbir şeyi korumuyor →
 *    güvenle temizlenir (kullanıcı isterse yeniden kurar).
 *
 * DB facade (raw) kullanılır ki Eloquent cast'i devreye girip düz-metni çözmeye
 * çalışmasın. Fresh kurulumda (kullanıcı yok) no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('users')
            ->whereNotNull('two_factor_secret')
            ->get(['id', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);

        foreach ($rows as $row) {
            if ($row->two_factor_confirmed_at === null) {
                DB::table('users')->where('id', $row->id)->update([
                    'two_factor_secret' => null,
                    'two_factor_recovery_codes' => null,
                ]);

                continue;
            }

            DB::table('users')->where('id', $row->id)->update([
                'two_factor_secret' => Crypt::encryptString($row->two_factor_secret),
                'two_factor_recovery_codes' => $row->two_factor_recovery_codes !== null
                    ? Crypt::encryptString($row->two_factor_recovery_codes)
                    : null,
            ]);
        }
    }

    public function down(): void
    {
        // Bilinçli olarak geri alınamaz: şifreli 2FA sırlarını düz-metne çevirmek
        // güvenlik açısından istenmez. Rollback gerekiyorsa cast kaldırılıp bu
        // migration atlanmalı.
    }
};
