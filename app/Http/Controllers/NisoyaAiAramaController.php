<?php

namespace App\Http\Controllers;

use App\Services\NisoyaAiYonlendirici;
use App\Services\RehberYuzeyi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Anasayfa "Nisoya AI ile ara" çubuğu — herkese açık, salt-okunur JSON uç.
 * Sitenin en görünür AI yüzeyi (bkz. NisoyaAiYonlendirici docblock'u);
 * `throttle:nisoya-ai-arama` rotada (bootstrap/app.php).
 */
class NisoyaAiAramaController extends Controller
{
    public function ara(Request $request, NisoyaAiYonlendirici $yonlendirici, RehberYuzeyi $rehberYuzeyi): JsonResponse
    {
        $sorgu = trim((string) $request->query('q', ''));

        if (! $yonlendirici->aranmaliMi($sorgu)) {
            return response()->json([
                'niyet' => 'belirsiz',
                'sonuclar' => [],
                'ilanBaglantisi' => null,
                'aktif' => $yonlendirici->isEnabled(),
            ]);
        }

        // K1'in aynısı (Rehber ülke önceliği): üye ikameti > GeoIP. Kişisel-
        // leştirme burada da geçerli — "pasaportum kayboldu" yazan bir üye,
        // kendi ülkesindeki temsilciliğe düşer, rastgele bir ülkeye değil.
        $varsayilanUlke = $rehberYuzeyi->cozulenUlkeKodu($request->user(), $request);

        $sonuc = $yonlendirici->ara($sorgu, $varsayilanUlke);

        return response()->json([
            'niyet' => $sonuc['niyet'],
            'sonuclar' => $sonuc['sonuclar']->values(),
            'ilanBaglantisi' => $sonuc['ilanBaglantisi'],
            'aktif' => true,
        ]);
    }
}
