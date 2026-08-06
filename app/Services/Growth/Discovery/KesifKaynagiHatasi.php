<?php

namespace App\Services\Growth\Discovery;

use RuntimeException;

/**
 * Keşif kaynağı sorguyu yanıtlayamadı — "sonuç yok" ile karıştırılmamalı.
 *
 * ---------------------------------------------------------------------------
 * NEDEN AYRI BİR İSTİSNA
 *
 * 2026-08-06'da ölçüldü: `growth:discover US` "Bulunan işletme: 0" diyordu ve
 * bu, "yurtdışında Türk işletmesi bulunamadı" gibi okunuyordu. Gerçek başkaydı
 * — Overpass sorguları zaman aşımına uğruyordu ve kod bunu göremiyordu, çünkü
 * OVERPASS HATALARINI HTTP 200 İLE DÖNER:
 *
 *   1. `{"elements": [], "remark": "runtime error: Query timed out..."}`
 *   2. Sunucu meşgulken JSON yerine düpedüz bir HTML hata sayfası.
 *
 * İkisi de `$response->successful()` kontrolünden geçiyordu; `json('elements')`
 * boş/null dönüyor, kod da sessizce boş liste veriyordu. Yani araç arıza
 * anında "arama yaptım, hiçbir şey yok" diyordu.
 *
 * "Bulamadım" ile "bakamadım" apayrı şeylerdir. Bu istisna o ayrımı taşır:
 * runner onu sayar, komut kırmızıyla basar — sıfır sonuç bir daha asla sessiz
 * bir arızayı gizlemez.
 */
class KesifKaynagiHatasi extends RuntimeException {}
