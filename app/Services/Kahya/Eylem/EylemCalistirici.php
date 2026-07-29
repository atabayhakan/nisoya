<?php

namespace App\Services\Kahya\Eylem;

use App\Enums\EylemRiski;
use App\Models\KahyaEylemKaydi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Eylemleri çalıştıran tek kapı: doğrula → risk kapısı → uygula → deftere yaz.
 *
 * ---------------------------------------------------------------------------
 * RİSK KAPISI
 *
 * Düşük riskli eylem doğrudan uygulanır ve "şunu yaptım, geri al" denir.
 * Yüksek riskli eylem UYGULANMAZ; `beklemede` yazılır ve sahip panelde tam
 * olarak ne değişeceğini görüp onaylar.
 *
 * Ayrım "ne kadar önemli" değil "geri alması ne kadar zor" üzerinden yapılır
 * — bkz. {@see EylemRiski}.
 *
 * ---------------------------------------------------------------------------
 * DOĞRULAMA YAPAY ZEKÂYA BIRAKILMAZ
 *
 * Model parametreleri doldurur ama doğruluğunu sunucu karar verir. Bir dil
 * modelinin "TR" yerine "Türkiye" yazması ya da var olmayan bir kategori
 * kimliği uydurması olağan; bunun yakalanacağı yer burasıdır, veritabanı
 * değil.
 *
 * ---------------------------------------------------------------------------
 * DEFTER HER HÂLÜKÂRDA YAZILIR
 *
 * Eylem patlasa bile kayıt kalır (`durum = hata`). Sessizce başarısız olan bir
 * ajan, hiç olmayan bir ajandan kötüdür: sahip işin yapıldığını sanır.
 */
class EylemCalistirici
{
    public function __construct(private readonly EylemKatalogu $katalog) {}

    /**
     * Bir eylemi çalıştırır (ya da onaya sunar).
     *
     * @param  array<string, mixed>  $parametreler
     *
     * @throws RuntimeException katalogda olmayan eylem
     */
    public function calistir(string $ad, array $parametreler, ?User $isteyen = null): KahyaEylemKaydi
    {
        $eylem = $this->katalog->bul($ad);

        if ($eylem === null) {
            // Katalog dışı bir ad, güvenlik sınırının çalıştığı andır —
            // sessizce yutulmaz, deftere de yazılmaz (ortada eylem yok).
            throw new RuntimeException("Kâhya böyle bir iş yapamıyor: [{$ad}].");
        }

        try {
            $temiz = $eylem->dogrula($parametreler);
        } catch (ValidationException $e) {
            return KahyaEylemKaydi::create([
                'eylem' => $ad,
                'durum' => KahyaEylemKaydi::DURUM_HATA,
                'risk' => $eylem->risk(),
                'parametreler' => $parametreler,
                'onizleme' => 'Parametreler geçersiz.',
                'hata' => implode(' ', array_merge(...array_values($e->errors()))),
                'user_id' => $isteyen?->id,
            ]);
        }

        $kayit = KahyaEylemKaydi::create([
            'eylem' => $ad,
            'durum' => KahyaEylemKaydi::DURUM_BEKLEMEDE,
            'risk' => $eylem->risk(),
            'parametreler' => $temiz,
            'onizleme' => $eylem->onizleme($temiz),
            'user_id' => $isteyen?->id,
        ]);

        // Yüksek risk: burada DURULUR. Sahip onaylayana kadar hiçbir şey olmaz.
        if ($eylem->risk()->onayGerekir()) {
            return $kayit;
        }

        return $this->uygulaVeYaz($eylem, $kayit);
    }

    /** Bekleyen bir eylemi sahip onayladıktan sonra uygular. */
    public function onayla(KahyaEylemKaydi $kayit): KahyaEylemKaydi
    {
        if ($kayit->durum !== KahyaEylemKaydi::DURUM_BEKLEMEDE) {
            throw new RuntimeException('Yalnızca onay bekleyen bir eylem uygulanabilir.');
        }

        $eylem = $this->katalog->bul($kayit->eylem)
            ?? throw new RuntimeException("Eylem katalogda yok: [{$kayit->eylem}].");

        return $this->uygulaVeYaz($eylem, $kayit);
    }

    public function reddet(KahyaEylemKaydi $kayit): KahyaEylemKaydi
    {
        if ($kayit->durum !== KahyaEylemKaydi::DURUM_BEKLEMEDE) {
            throw new RuntimeException('Yalnızca onay bekleyen bir eylem reddedilebilir.');
        }

        $kayit->update(['durum' => KahyaEylemKaydi::DURUM_REDDEDILDI]);

        return $kayit;
    }

    /** Uygulanmış bir eylemi geri alır. */
    public function geriAl(KahyaEylemKaydi $kayit): KahyaEylemKaydi
    {
        if (! $kayit->geriAlinabilirMi()) {
            throw new RuntimeException('Bu eylem geri alınamaz (uygulanmamış ya da geri alma izi yok).');
        }

        $eylem = $this->katalog->bul($kayit->eylem)
            ?? throw new RuntimeException("Eylem katalogda yok: [{$kayit->eylem}].");

        try {
            $sonuc = DB::transaction(fn (): string => $eylem->geriAl($kayit->geri_alma ?? []));
        } catch (Throwable $e) {
            report($e);

            // Geri alma hatası eylemin durumunu DEĞİŞTİRMEZ: kayıt hâlâ
            // "uygulandı" kalmalı ki tekrar denenebilsin.
            $kayit->update(['hata' => 'Geri alınamadı: '.class_basename($e)]);

            throw new RuntimeException('Geri alma başarısız oldu; ayrıntı sunucu log\'unda.', 0, $e);
        }

        $kayit->update([
            'durum' => KahyaEylemKaydi::DURUM_GERI_ALINDI,
            'geri_alindi_at' => now(),
            'sonuc' => $sonuc,
            'hata' => null,
        ]);

        return $kayit;
    }

    private function uygulaVeYaz(Eylem $eylem, KahyaEylemKaydi $kayit): KahyaEylemKaydi
    {
        try {
            /*
             * İşlem (transaction) içinde: bir eylem birden çok satıra
             * dokunuyorsa yarım kalması, geri alma izini de yanlış yapar.
             */
            $sonuc = DB::transaction(fn (): array => $eylem->uygula($kayit->parametreler));
        } catch (Throwable $e) {
            report($e);

            $kayit->update([
                'durum' => KahyaEylemKaydi::DURUM_HATA,
                // İstisna metni dışarı taşınmaz — Kâhya'nın her yerinde aynı
                // kural: sorgu mesajları kullanıcı verisi içerebilir.
                'hata' => 'Uygulanamadı: '.class_basename($e),
            ]);

            return $kayit;
        }

        $kayit->update([
            'durum' => KahyaEylemKaydi::DURUM_UYGULANDI,
            'uygulandi_at' => now(),
            'sonuc' => $sonuc['sonuc'],
            'geri_alma' => $sonuc['geri_alma'],
        ]);

        return $kayit;
    }
}
