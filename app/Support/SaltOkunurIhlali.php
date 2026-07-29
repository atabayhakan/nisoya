<?php

namespace App\Support;

use RuntimeException;

/**
 * Salt-okunur kipte yazma denemesi — bkz. {@see SaltOkunurBekci}.
 *
 * MESAJ SQL'İN YALNIZ İLK KELİMESİNİ TAŞIR. Tam sorgu metni taşınmaz: bu
 * istisna bir MCP yanıtına dönüşüp yapay zekâya gidebilir ve Laravel'in sorgu
 * metni bağlanmış değerleri — yani kullanıcı verisini — içerir. Aynı kural
 * `LogOzeti` içinde de geçerli ve nedeni oradaki docblock'ta uzun uzun yazılı.
 */
final class SaltOkunurIhlali extends RuntimeException
{
    public function __construct(string $sql)
    {
        $ilk = strtoupper(strtok(trim($sql), " \t\n(") ?: 'BİLİNMEYEN');

        parent::__construct(
            "Kâhya salt-okunur kipte: [{$ilk}] ile başlayan sorgu engellendi. ".
            'Kâhya hiçbir ortamda veri yazmaz; değişiklik yönetim panelinden yapılır.'
        );
    }
}
