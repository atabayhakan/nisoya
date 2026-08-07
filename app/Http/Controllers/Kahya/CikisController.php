<?php

namespace App\Http\Controllers\Kahya;

use App\Http\Controllers\Controller;
use App\Models\BekleyenHamle;
use App\Services\Kahya\Dis\EngelListesi;
use Illuminate\View\View;

/**
 * Erişim postasından "listeden çık" — herkese açık, oturumsuz.
 *
 * ---------------------------------------------------------------------------
 * İKİ YOL, TEK SONUÇ
 *
 * GET  — insan postadaki bağlantıya tıklar; onay düğmesi olan bir sayfa görür.
 * POST — hem o düğme hem de RFC 8058 tek-tık çıkışı buraya düşer (Gmail /
 *        Outlook kendi "Abonelikten çık" düğmesinden doğrudan POST atar).
 *
 * GET'İN NEDEN DOĞRUDAN ÇIKARMADIĞI: kurumsal posta tarayıcıları ve önizleme
 * botları mesajdaki bağlantıları KENDİLİĞİNDEN açar. GET çıkışı yapsaydı,
 * alıcı postayı okumadan listeden düşerdi ve kimse nedenini bilemezdi.
 * Değiştiren eylem POST'ta durur — HTTP'nin kendi kuralı, burada bedeli somut.
 *
 * CSRF MUAF (bkz. bootstrap/app.php): POST'u gönderen taraf bizim sayfamız
 * değil, alıcının posta istemcisi — jeton taşıyamaz. Yetkilendirme jetonun
 * kendisidir: tahmin edilemez, tek mesaja bağlı ve yalnız o adresi engeller.
 */
class CikisController extends Controller
{
    public function __construct(private readonly EngelListesi $engeller) {}

    /** Onay sayfası — henüz hiçbir şey değişmez. */
    public function goster(string $jeton): View
    {
        $hamle = $this->hamle($jeton);

        return view('kahya.cikis', [
            'jeton' => $jeton,
            'eposta' => (string) $hamle->alici_eposta,
            'cikildi' => $this->engeller->engelliMi((string) $hamle->alici_eposta),
        ]);
    }

    /** Çıkışı uygular. Idempotent: ikinci kez basmak hata vermez. */
    public function cik(string $jeton): View
    {
        $hamle = $this->hamle($jeton);

        $this->engeller->engelle(
            (string) $hamle->alici_eposta,
            'Alıcı listeden çıktı (hamle #'.$hamle->id.')',
        );

        return view('kahya.cikis', [
            'jeton' => $jeton,
            'eposta' => (string) $hamle->alici_eposta,
            'cikildi' => true,
        ]);
    }

    /**
     * Jetonu karta çevirir.
     *
     * Yalnız GÖNDERİLMİŞ kartın jetonu geçerli sayılır: jeton gönderim anında
     * yazılıyor, ama koşul yine de açık yazıldı — taslak bir kartın jetonu
     * sızsa bile çıkış bağlantısına dönüşmesin.
     */
    private function hamle(string $jeton): BekleyenHamle
    {
        return BekleyenHamle::query()
            ->where('cikis_jetonu', $jeton)
            ->whereNotNull('gonderildi_at')
            ->whereNotNull('alici_eposta')
            ->firstOrFail();
    }
}
