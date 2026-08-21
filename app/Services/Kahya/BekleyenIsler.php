<?php

namespace App\Services\Kahya;

use App\Enums\ListingStatus;
use App\Models\ContactMessage;
use App\Models\FeatureRequest;
use App\Models\JobFeatureRequest;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\RehberGeriBildirimi;
use App\Models\Report;
use App\Models\Story;
use App\Models\TemsilcilikIslemi;
use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonuOnerisi;
use App\Services\Rehber\RehberBoslukAvcisi;
use App\Support\Modules;

/**
 * Sahibin müdahalesini bekleyen işlerin TEK kaynağı.
 *
 * NEDEN SERVİS: bu liste `BekleyenIslerWidget` içinde gömülüydü ve yalnız
 * panele bakan biri görebiliyordu. Günlük rapor da aynı listeye ihtiyaç
 * duyuyor; iki yerde ayrı ayrı yazmak, birinin diğerinden sürüklenmesi
 * demekti (bu depoda tam olarak böyle bir sürüklenme yaşandı — bkz.
 * SaglikKontrolu docblock'u).
 *
 * ÜÇ KUYRUK YENİ VE ÖNEMLİ. Widget beş kuyruk gösteriyordu; aşağıdaki
 * üçü hiçbir panoda görünmüyordu:
 *
 *   1. `Listing status=beklemede` — AI görsel moderasyonu şüpheli bulduğunda
 *      ilanı YAYINDAN DÜŞÜRÜYOR (ProcessListingImage.php:130). Sahip bunu
 *      ancak ilan sahibi şikâyet edince öğreniyordu. Kullanıcının ilanı
 *      sessizce görünmez oluyor.
 *   2. `ListingImage is_flagged` — aynı moderasyonun işaretlediği görseller
 *      (ProcessListingImage.php:122). İnceleyen olmazsa ilan askıda kalır.
 *   3. 24 saattir yanıtsız açık bilet — SLA sinyali. `scopeAcik` zaten var
 *      ama "ne kadar süredir bekliyor" hiçbir yerde ölçülmüyordu.
 *
 * Sıfır olan kuyruk DÖNDÜRÜLMEZ: bu bir "yapılacak iş" listesidir, boş
 * satır gürültüdür. Widget'ın kararı korundu.
 */
class BekleyenIsler
{
    /** Bir biletin "geciken" sayılması için gereken yanıtsız saat. */
    public const SLA_SAAT = 24;

    /**
     * @return list<array{anahtar: string, etiket: string, adet: int, aciliyet: string, aciklama: string}>
     */
    public function topla(): array
    {
        $kuyruklar = [
            [
                'anahtar' => 'iletisim_yeni',
                'etiket' => 'Yeni iletişim mesajı',
                'adet' => ContactMessage::query()->where('status', 'yeni')->count(),
                'aciliyet' => 'yuksek',
                'aciklama' => 'Henüz okunmamış',
            ],
            [
                'anahtar' => 'bilet_geciken',
                'etiket' => self::SLA_SAAT.' saattir yanıtsız bilet',
                'adet' => ContactMessage::query()
                    ->acik()
                    ->whereNull('first_replied_at')
                    ->where('created_at', '<=', now()->subHours(self::SLA_SAAT))
                    ->count(),
                'aciliyet' => 'yuksek',
                'aciklama' => 'Açık ve hiç yanıtlanmamış',
            ],
            [
                'anahtar' => 'sikayet_acik',
                'etiket' => 'Açık şikayet',
                'adet' => Report::query()->where('status', 'acik')->count(),
                'aciliyet' => 'yuksek',
                'aciklama' => 'Kullanıcı şikayeti',
            ],
            [
                'anahtar' => 'ilan_beklemede',
                'etiket' => 'Moderasyonda bekleyen ilan',
                'adet' => Listing::query()->where('status', ListingStatus::Beklemede)->count(),
                'aciliyet' => 'yuksek',
                'aciklama' => 'Yayından düşürüldü — ilan sahibi göremiyor',
            ],
            [
                'anahtar' => 'gorsel_isaretli',
                'etiket' => 'İşaretlenmiş görsel',
                'adet' => ListingImage::query()->where('is_flagged', true)->count(),
                'aciliyet' => 'orta',
                'aciklama' => 'AI moderasyonu şüpheli buldu',
            ],
            [
                'anahtar' => 'ani_beklemede',
                'etiket' => 'Onay bekleyen anı',
                'adet' => Story::query()->where('status', 'beklemede')->count(),
                'aciliyet' => 'orta',
                'aciklama' => 'Yayınlanmayı bekliyor',
            ],
            [
                'anahtar' => 'one_cikarma',
                'etiket' => 'Öne çıkarma talebi',
                'adet' => FeatureRequest::query()->where('status', 'beklemede')->count(),
                'aciliyet' => 'orta',
                'aciklama' => 'Ücretli talep',
            ],
            [
                'anahtar' => 'is_one_cikarma',
                'etiket' => 'İş ilanı öne çıkarma talebi',
                'adet' => JobFeatureRequest::query()->where('status', 'beklemede')->count(),
                'aciliyet' => 'orta',
                'aciklama' => 'Ücretli talep',
            ],
        ];

        /*
         * Ülke Rehberi (K7): yayında olup doğrulaması BAYATLIK_GUN'ü aşan
         * içerik. Yanlış evrak listesi kullanıcıyı konsolosluk kapısından
         * geri çevirtir — bayatlık bir estetik sorunu değil, güven sorunudur.
         * İncelenmemiş "güncel mi?" geri bildirimleri de aynı sebepten burada.
         * Modül kapalıyken iki kuyruk da eklenmez (kapalı yüzeyin bakımı
         * gürültüdür).
         */
        if (Modules::enabled('rehber')) {
            $kuyruklar[] = [
                'anahtar' => 'rehber_bayat',
                'etiket' => 'Doğrulaması eskimiş rehber içeriği',
                'adet' => TemsilcilikIslemi::query()->bayat()->count(),
                'aciliyet' => 'orta',
                'aciklama' => TemsilcilikIslemi::BAYATLIK_GUN.' günden eski doğrulama — yayında',
            ];
            $kuyruklar[] = [
                'anahtar' => 'rehber_bosluk',
                'etiket' => 'Rehberde eksik sayfa',
                'adet' => app(RehberBoslukAvcisi::class)->sayi(),
                'aciliyet' => 'orta',
                'aciklama' => 'Kâhya cevap veremedi — soruldu ama rehberde yok',
            ];
            $kuyruklar[] = [
                'anahtar' => 'rehber_geri_bildirim',
                'etiket' => 'İncelenmemiş rehber geri bildirimi',
                'adet' => RehberGeriBildirimi::query()->where('incelendi', false)->count(),
                'aciliyet' => 'orta',
                'aciklama' => 'Ziyaretçi "bilgi güncel değil" dedi',
            ];

            // Yaşam Rehberi (2026-08-21) — Ülke Rehberi ile AYNI bayatlık
            // kuralı, AYNI modül anahtarı altında (bkz. tasarım K7/F0).
            $kuyruklar[] = [
                'anahtar' => 'yasam_bayat',
                'etiket' => 'Doğrulaması eskimiş Yaşam Rehberi içeriği',
                'adet' => YasamKonuIcerigi::query()->bayat()->count(),
                'aciliyet' => 'orta',
                'aciklama' => YasamKonuIcerigi::BAYATLIK_GUN.' günden eski doğrulama — yayında',
            ];
            $kuyruklar[] = [
                'anahtar' => 'yasam_oneri_bekleyen',
                'etiket' => 'Bekleyen Yaşam Rehberi önerisi',
                'adet' => YasamKonuOnerisi::query()->bekleyen()->count(),
                'aciliyet' => 'orta',
                'aciklama' => 'Üye düzeltme/ek bilgi önerdi',
            ];
        }

        return array_values(array_filter($kuyruklar, fn (array $k) => $k['adet'] > 0));
    }

    /** Bekleyen toplam iş sayısı — rapor başlığı için. */
    public function toplam(): int
    {
        return array_sum(array_column($this->topla(), 'adet'));
    }
}
