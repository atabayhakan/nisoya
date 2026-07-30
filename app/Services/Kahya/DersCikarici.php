<?php

namespace App\Services\Kahya;

use App\Ai\Kahya\DersCikariciAjani;
use App\Enums\HafizaTuru;
use App\Models\BekleyenHamle;
use App\Models\KahyaEylemKaydi;
use App\Models\KahyaHafizasi;
use App\Models\KahyaHarcamasi;
use App\Support\Settings;

/**
 * Haftalık öğrenme koşusu (F5): sahibin kararlarından ders damıt, hafızaya yaz.
 *
 * ---------------------------------------------------------------------------
 * SİNYAL YOKSA MODEL ÇAĞRILMAZ
 *
 * Boş haftadan ders çıkarmaya zorlanan bir model ders UYDURUR — ve uydurma
 * ders, hafızaya girip sonraki her sohbeti zehirler. Eşik ikidir: tek sinyal
 * çoğu zaman gürültü, iki sinyal en azından bakılmaya değer.
 *
 * ---------------------------------------------------------------------------
 * ÇIKARIM AYRI KAYNAK, TAVANLI VE SİLİNEBİLİR
 *
 * Üretilen dersler `kahya-cikarimi` kaynağıyla yazılır (panelde kırmızı
 * rozet, tek tıkla silinir), koşu başına en fazla 5'tir ve toplam çıkarım
 * sayısı tavanı aşarsa yeni koşu ÜRETMEZ — hafızayı modelin kendi sesiyle
 * doldurmak, sahibin sesini bastırmak olurdu.
 */
class DersCikarici
{
    private const HAFTA_GUN = 7;

    private const EN_AZ_SINYAL = 2;

    private const KOSU_TAVANI = 5;

    private const TOPLAM_CIKARIM_TAVANI = 30;

    /**
     * @return array{durum: string, sinyal: int, uretilen: int, dersler: list<string>}
     */
    public function calis(): array
    {
        $sinyaller = $this->sinyalleriTopla();

        if (count($sinyaller) < self::EN_AZ_SINYAL) {
            return ['durum' => 'sinyal-yok', 'sinyal' => count($sinyaller), 'uretilen' => 0, 'dersler' => []];
        }

        $mevcutCikarim = KahyaHafizasi::query()
            ->where('kaynak', KahyaHafizasi::KAYNAK_CIKARIM)
            ->aktif()
            ->count();

        if ($mevcutCikarim >= self::TOPLAM_CIKARIM_TAVANI) {
            return ['durum' => 'cikarim-tavani-dolu', 'sinyal' => count($sinyaller), 'uretilen' => 0, 'dersler' => []];
        }

        $saglayici = trim((string) Settings::get('ai.saglayici', '')) ?: (string) config('ai.default');
        $model = trim((string) Settings::get('kahya.sohbet_modeli', ''))
            ?: (trim((string) Settings::get('ai.model', ''))
            ?: (string) config("ai.providers.{$saglayici}.model"));

        $yanit = (new DersCikariciAjani)->prompt(
            $this->promptOlustur($sinyaller),
            provider: $saglayici,
            model: $model,
            timeout: 90,
        );

        rescue(fn () => KahyaHarcamasi::kaydet('ders-cikar', $saglayici, $model, $yanit->usage), report: true);

        $yazilan = [];

        foreach (array_slice((array) ($yanit['dersler'] ?? []), 0, self::KOSU_TAVANI) as $ders) {
            $metin = trim((string) ($ders['metin'] ?? ''));

            if (mb_strlen($metin) < 10 || mb_strlen($metin) > 500) {
                continue;
            }

            // Birebir tekrar koruması — "benzerlik" yargısı modelin işi
            // (yönergesinde), buradaki son savunma yalnız birebir kopya.
            if (KahyaHafizasi::query()->where('metin', $metin)->exists()) {
                continue;
            }

            KahyaHafizasi::create([
                'tur' => HafizaTuru::Ders,
                'metin' => $metin,
                'kaynak' => KahyaHafizasi::KAYNAK_CIKARIM,
            ]);

            $yazilan[] = $metin;
        }

        return [
            'durum' => 'tamam',
            'sinyal' => count($sinyaller),
            'uretilen' => count($yazilan),
            'dersler' => $yazilan,
        ];
    }

    /**
     * Son haftanın ham karar sinyalleri — deterministik, LLM'siz.
     *
     * @return list<string>
     */
    public function sinyalleriTopla(): array
    {
        $esik = now()->subDays(self::HAFTA_GUN);
        $sinyaller = [];

        // 1) Geri alınan eylemler: "yaptım ama sahip istemedi" — en net sinyal.
        $geriAlinanlar = KahyaEylemKaydi::query()
            ->where('durum', KahyaEylemKaydi::DURUM_GERI_ALINDI)
            ->where('geri_alindi_at', '>=', $esik)
            ->latest('geri_alindi_at')
            ->limit(20)
            ->get();

        foreach ($geriAlinanlar as $kayit) {
            $sinyaller[] = "GERİ ALINDI: '{$kayit->eylem}' eylemi — önizlemesi: {$kayit->onizleme}";
        }

        // 2) Reddedilen onay bekleyenler.
        $reddedilenler = KahyaEylemKaydi::query()
            ->where('durum', KahyaEylemKaydi::DURUM_REDDEDILDI)
            ->where('updated_at', '>=', $esik)
            ->latest('updated_at')
            ->limit(20)
            ->get();

        foreach ($reddedilenler as $kayit) {
            $sinyaller[] = "REDDEDİLDİ: '{$kayit->eylem}' önerisi — önizlemesi: {$kayit->onizleme}";
        }

        // 3) Tekrarlayan eylem hataları (tek hata gürültüdür, desen sinyaldir —
        //    yine de modele veriyoruz, eşiği yönergesi koyar).
        $hatalar = KahyaEylemKaydi::query()
            ->where('durum', KahyaEylemKaydi::DURUM_HATA)
            ->where('created_at', '>=', $esik)
            ->get()
            ->groupBy('eylem');

        foreach ($hatalar as $eylem => $grup) {
            if ($grup->count() >= 2) {
                $ornekHata = (string) $grup->first()->hata;
                $sinyaller[] = "TEKRARLI HATA: '{$eylem}' {$grup->count()} kez düştü — örnek: {$ornekHata}";
            }
        }

        // 4) Hamle kartı kararları — sahibin notu en değerli öğrenme malzemesi.
        $kararlar = BekleyenHamle::query()
            ->whereNotNull('karar_at')
            ->where('karar_at', '>=', $esik)
            ->latest('karar_at')
            ->limit(20)
            ->get();

        foreach ($kararlar as $hamle) {
            $karar = $hamle->durum === BekleyenHamle::DURUM_ONAYLANDI ? 'ONAYLANDI' : 'REDDEDİLDİ';
            $sinyaller[] = "HAMLE {$karar}: '{$hamle->baslik}' ({$hamle->tur})"
                .($hamle->karar_notu ? " — sahibin notu: \"{$hamle->karar_notu}\"" : ' — not yok');
        }

        return $sinyaller;
    }

    /** @param  list<string>  $sinyaller */
    private function promptOlustur(array $sinyaller): string
    {
        $mevcutDersler = KahyaHafizasi::query()
            ->where('kaynak', KahyaHafizasi::KAYNAK_CIKARIM)
            ->aktif()
            ->latest('id')
            ->limit(30)
            ->pluck('metin');

        $mevcut = $mevcutDersler->isEmpty()
            ? '(henüz yok)'
            : $mevcutDersler->map(fn (string $m): string => "- {$m}")->implode("\n");

        return "## Son haftanın karar sinyalleri\n"
            .implode("\n", array_map(fn (string $s): string => "- {$s}", $sinyaller))
            ."\n\n## Mevcut dersler (bunları TEKRAR üretme)\n{$mevcut}";
    }
}
