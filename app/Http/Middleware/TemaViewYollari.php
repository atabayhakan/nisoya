<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Tema;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\View\FileViewFinder;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vitrin teması aktifken resources/views/vitrin dizinini view finder
 * yollarının BAŞINA ekler: view('home') önce vitrin/home.blade.php'a
 * çözülür; override dosyası yoksa klasik dosyaya düşer. Klasik temada
 * tam no-op — finder vitrin dizinine hiç bakmaz.
 *
 * - 'web' grubuna eklenir → StartSession'dan SONRA koşar (admin önizleme
 *   oturumu okunabilir). Console/queue/mail/Filament bu gruptan geçmez →
 *   `view:cache` ve e-postalar daima tema-bağımsız derlenir. Bu yüzden
 *   vitrin'e ÖZGÜ yeni bileşenler resources/views/components/vitrin/**
 *   altında (klasik ağaçta) yaşamalıdır — vitrin/ altına konursa
 *   view:cache derleme anında bulamaz.
 * - Finder 'view' singleton'ında yaşar; yol eşitleme İDEMPOTENT'tir
 *   (bkz. finderYollariniEsitle) — feature testleri ve olası Octane gibi
 *   uzun ömürlü süreçlerde birikme/cache zehirlenmesi olmaz.
 */
class TemaViewYollari
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->onizlemeyiIsle($request);

        $this->finderYollariniEsitle();

        return $next($request);
    }

    /**
     * Finder yollarını aktif temayla İDEMPOTENT olarak eşitler. Koşulsuz
     * prependLocation kullanılamaz: finder 'view' singleton'ında yaşar ve
     * aynı uygulama ömründeki ardışık isteklerde (feature testleri; ileride
     * Octane) yol birikir, ad→dosya cache'i ($views) temalar arası zehirlenir
     * — klasiğe dönüş sessizce etkisiz kalırdı (P0 inceleme bulgusu #1).
     * Yol yalnız durum DEĞİŞİMİNDE eklenir/çıkarılır ve cache o anda flush
     * edilir; kararlı durumda memoizasyon korunur.
     */
    private function finderYollariniEsitle(): void
    {
        $finder = View::getFinder();

        // ViewFinderInterface bu API'yi vaat etmez; farklı bir finder
        // bağlanmışsa (test double vb.) tema no-op kalır — site asla düşmez.
        if (! $finder instanceof FileViewFinder) {
            return;
        }

        // prependLocation yolu realpath ile çözerek saklar — karşılaştırma
        // aynı normalizasyonla yapılmalı (dizin henüz yoksa ham yol kalır).
        $yol = resource_path('views/vitrin');
        $hedef = realpath($yol) ?: $yol;
        $yollar = $finder->getPaths();
        $iceriyor = in_array($hedef, $yollar, true);

        if (Tema::vitrinMi() && ! $iceriyor) {
            $finder->prependLocation($yol);
            $finder->flush();
        } elseif (! Tema::vitrinMi() && $iceriyor) {
            $finder->setPaths(array_values(array_filter($yollar, fn ($p) => $p !== $hedef)));
            $finder->flush();
        }
    }

    /**
     * Admin gizli önizlemesi: ?tema_onizleme=vitrin yalnızca panel yetkisi
     * olan kullanıcıda oturuma yazılır (gezinmede korunur, DB bayrağına
     * dokunulmaz); ?tema_onizleme=kapat herkes için oturumdan siler.
     * Canlıda bayrağı açmadan Vitrin QA'sı bununla yapılır.
     */
    private function onizlemeyiIsle(Request $request): void
    {
        $istek = $request->query('tema_onizleme');

        if ($istek === null || ! is_string($istek) || ! $request->hasSession()) {
            return;
        }

        if ($istek === 'kapat') {
            $request->session()->forget('tema_onizleme');

            return;
        }

        // isAdmin() || isModerator() ≡ UserRole::canAccessAdminPanel() —
        // enum metodunu burada doğrudan çağırmak larastan'ın role cast'ini
        // görmeyen bilinen yanlış-pozitifine takılıyor (bkz. baseline'daki
        // aynı mesajlar); modelin mevcut yardımcıları aynı kümeyi verir.
        $user = $request->user();
        $yetkili = $user instanceof User && ($user->isAdmin() || $user->isModerator());

        if ($yetkili && in_array($istek, Tema::TEMALAR, true)) {
            $request->session()->put('tema_onizleme', $istek);
        }
    }
}
