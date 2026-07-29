<?php

use App\Mcp\Sunucular\KahyaSunucusu;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP sunucuları
|--------------------------------------------------------------------------
| Bu dosyayı `laravel/mcp` paketi kendisi yükler; bootstrap/app.php'e
| eklenmemeli.
|
| DİKKAT — DOSYA YOKSA HİÇBİR UYARI ÇIKMAZ: McpServiceProvider dosyayı
| bulamazsa sessizce geri döner ve `mcp:start kahya` "sunucu bulunamadı" der.
| Asıl sebep (dosya hiç yüklenmedi) gizli kalır. Bu dosya silinmemeli.
|
| `local` = stdio taşıması, yani süreç STDIN/STDOUT üzerinden konuşur.
| Üretime bağlanmak SSH ile aynı komutu uzaktan çalıştırmaktır; ayrıntı
| docs/07-kahya-mcp.md içinde.
|
| WEB UÇ NOKTASI BİLEREK AÇILMADI (`Mcp::web`): sunucu salt-okunur olsa da
| internete açık bir teşhis uç noktası, kimliği doğrulanmamış birine sitenin
| iç durumunu anlatır. SSH zaten var ve kimlik doğrulaması çözülmüş durumda.
*/

Mcp::local('kahya', KahyaSunucusu::class);
