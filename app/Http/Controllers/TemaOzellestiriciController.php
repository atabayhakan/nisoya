<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use App\Support\Tema;
use App\Support\TemaJetonlari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Canlı tema özelleştirici (2026-07-28).
 *
 * Önizleme tamamen istemcide yapılır (CSS değişkenleri), sunucuya yalnız
 * KAYDET basılınca gelinir. Böylece ne her tıklamada istek atılır ne de
 * yarım kalmış bir deneme siteye yazılır.
 */
class TemaOzellestiriciController extends Controller
{
    public const KOSELER = ['sharp', 'soft', 'modern', 'pill'];

    /**
     * Seçilebilir yazı tipleri — TEK KAYNAK App\Support\TemaJetonlari::FONTLAR.
     *
     * Orada yalnız gerçekten self-host edilen aileler bulunur; liste bir kez
     * çoğaltıldığı için panelde 'inter'/'outfit' gibi hiçbir şey yapmayan
     * seçenekler yıllarca durabilmişti.
     *
     * @return array<int, string>
     */
    public static function fontAnahtarlari(): array
    {
        return array_keys(TemaJetonlari::FONTLAR);
    }

    private function yetkiKontrol(Request $request): void
    {
        abort_unless($request->user()?->isAdmin() ?? false, 403);
    }

    public function kaydet(Request $request): JsonResponse
    {
        $this->yetkiKontrol($request);

        // Manuel doğrulayıcı, request()->validate() DEĞİL: bootstrap/app.php'de
        // shouldRenderJsonWhen yalnız `api/*` için JSON render ediyor, dolayısıyla
        // varsayılan yol burada 302 yönlendirme üretirdi. Bu uç yalnızca fetch
        // tarafından çağrılıyor; yönlendirme yerine gerçek bir JSON hatası
        // döndürmek, widget'ın kullanıcıya doğru mesajı gösterebilmesi için şart.
        $dogrulayici = Validator::make($request->all(), [
            'tema' => ['nullable', Rule::in(Tema::TEMALAR)],
            'vitrin_aksan' => ['nullable', Rule::in(TemaJetonlari::vitrinAksanAdlari())],
            'marka_rengi' => ['nullable', Rule::in(array_keys(config('brand_colors', [])))],
            // Serbest hex <style> bloğuna basıldığı için burada da doğrulanır;
            // tasarim-theme.blade.php'deki ikinci savunma hattı korunuyor
            // (denetim #10: <style> içine CSS enjeksiyonu).
            'primary_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'font_family' => ['nullable', Rule::in(self::fontAnahtarlari())],
            'border_radius' => ['nullable', Rule::in(self::KOSELER)],
            'glassmorphism' => ['nullable', 'boolean'],
            'smooth_animations' => ['nullable', 'boolean'],
        ]);

        if ($dogrulayici->fails()) {
            return response()->json([
                'mesaj' => 'Geçersiz değer — kaydedilmedi.',
                'hatalar' => $dogrulayici->errors()->all(),
            ], 422);
        }

        /** @var array<string, mixed> $veri */
        $veri = $dogrulayici->validated();

        $yazilacak = [];

        if (isset($veri['tema'])) {
            $yazilacak['gorunum.tema'] = $veri['tema'];
        }
        if (isset($veri['vitrin_aksan'])) {
            $yazilacak['gorunum.vitrin_aksan'] = $veri['vitrin_aksan'];
        }

        // Renk TEK kontroldür. Klasik iskelet hem brand-theme (hazır aile) hem
        // tasarim-theme (serbest hex) basıyor ve ikincisi birinciyi eziyor —
        // ikisini bağımsız kontrol olarak sunmak, biri her zaman etkisiz
        // kalacağı için ölü kontrol üretirdi. Bu yüzden biri yazılırken diğeri
        // varsayılana çekilir; çakışma yapısal olarak imkânsız olur.
        if (isset($veri['marka_rengi'])) {
            $yazilacak['gorunum.marka_rengi'] = $veri['marka_rengi'];
            $yazilacak['gorunum.primary_color'] = '#059669';
        } elseif (isset($veri['primary_color'])) {
            $yazilacak['gorunum.primary_color'] = $veri['primary_color'];
            $yazilacak['gorunum.marka_rengi'] = 'emerald';
        }

        foreach (['font_family', 'border_radius'] as $alan) {
            if (isset($veri[$alan])) {
                $yazilacak['gorunum.'.$alan] = $veri[$alan];
            }
        }
        foreach (['glassmorphism', 'smooth_animations'] as $alan) {
            if (array_key_exists($alan, $veri) && $veri[$alan] !== null) {
                $yazilacak['gorunum.'.$alan] = $veri[$alan] ? '1' : '0';
            }
        }

        if ($yazilacak !== []) {
            Settings::setMany($yazilacak);
        }

        return response()->json([
            'kaydedildi' => array_keys($yazilacak),
            'mesaj' => 'Görünüm kaydedildi — siteyi gezen herkes artık bunu görüyor.',
        ]);
    }

    /** Görünümü fabrika ayarlarına döndürür (tema seçimine dokunmaz). */
    public function sifirla(Request $request): JsonResponse
    {
        $this->yetkiKontrol($request);

        Settings::setMany([
            'gorunum.vitrin_aksan' => 'deniz',
            'gorunum.marka_rengi' => 'emerald',
            'gorunum.primary_color' => '#059669',
            'gorunum.font_family' => 'sans',
            'gorunum.border_radius' => 'modern',
            'gorunum.glassmorphism' => '1',
            'gorunum.smooth_animations' => '1',
        ]);

        return response()->json(['mesaj' => 'Görünüm varsayılana döndü.']);
    }
}
