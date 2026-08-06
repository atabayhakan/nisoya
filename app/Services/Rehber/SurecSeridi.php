<?php

namespace App\Services\Rehber;

use App\Enums\ListingStatus;
use App\Models\Listing;
use Illuminate\Support\Facades\DB;

/**
 * İlan yaşam döngüsü şeridi — El Kitabı'nın tek animasyonlu bölümü.
 *
 * ---------------------------------------------------------------------------
 * NEDEN LOTTIE/VİDEO DEĞİL (araştırma kararı, plan 2026-08-04)
 *
 * Hazır animasyon biçimleri içine gömülü SABİT sayılarla gelir. Bu projede
 * "her şey gerçek veriden türemeli" kuralı var ve sahip (geliştirici değil)
 * bir Lottie dosyasını güncelleyemez. Süreç ilk değiştiğinde animasyon
 * sessizce YALAN SÖYLEMEYE başlar — bu, animasyonun hiç olmamasından kötüdür.
 *
 * Burada adımlar `ListingStatus` enum'undan türüyor ve altlarındaki sayılar
 * canlı sorgudan geliyor. Enum'a yeni bir durum eklendiğinde şerit kendiliğinden
 * büyür; eklenmezse `SurecSeridiTest` kırılır ve kimse unutamaz.
 *
 * ---------------------------------------------------------------------------
 * DEMO SAYILMAZ
 *
 * Sayılar `is_demo = false` süzgeciyle geliyor — Kâhya teşhisiyle ve ana
 * sayfadaki kanıt şeridiyle AYNI kural. İki ayrı "gerçek" tanımı olmamalı.
 */
class SurecSeridi
{
    /**
     * Ana hat: ilanın normal yolculuğu. Yan dal: çıkış durumları.
     *
     * Bu ayrım enum'da YOK (enum yalnız durumları bilir, akışı değil) ve
     * bilerek burada: akış bir ürün kararıdır, veri modeli değil. Ama her
     * enum durumunun burada bir karşılığı OLMAK ZORUNDA — testle mühürlü.
     */
    private const AKIS = [
        ['durum' => ListingStatus::Taslak, 'dal' => 'ana', 'aciklama' => 'Üye ilanı yazdı, henüz göndermedi.'],
        ['durum' => ListingStatus::Beklemede, 'dal' => 'ana', 'aciklama' => 'Moderasyon kuyruğunda; senin onayını bekliyor.'],
        ['durum' => ListingStatus::Aktif, 'dal' => 'ana', 'aciklama' => 'Sitede görünür, aramada çıkar, mesaj alabilir.'],
        // İKİ AYRI YOLDAN gelinir ve şerit ikisini de söylemek zorunda:
        // üye kendi kaldırdıysa (unpublished_at dolu) düğmeyle geri açar;
        // hesabı askıya alındığı için sistem kaldırdıysa açamaz. Bu satır
        // 2026-08-06'ya kadar yalnız birincisini yazıyordu — üstelik o gün o
        // eylem kodda HİÇ YOKTU; şerit kullanıcıya olmayan bir düğmeyi
        // anlatıyordu. Metin değişmeden önce eylem eklendi (bkz.
        // ListingController::yayindanKaldir).
        ['durum' => ListingStatus::Pasif, 'dal' => 'yan', 'aciklama' => 'Yayında değil: üye kendi kaldırdı (geri açabilir) ya da hesabı askıya alındı.'],
        ['durum' => ListingStatus::Reddedildi, 'dal' => 'yan', 'aciklama' => 'Moderasyonda reddedildi; üyeye bildirildi.'],
    ];

    /**
     * @return list<array{anahtar: string, etiket: string, aciklama: string, dal: string, adet: int}>
     */
    public function adimlar(): array
    {
        $sayimlar = $this->sayimlar();

        return array_map(fn (array $adim): array => [
            'anahtar' => $adim['durum']->value,
            'etiket' => $adim['durum']->getLabel(),
            'aciklama' => $adim['aciklama'],
            'dal' => $adim['dal'],
            'adet' => $sayimlar[$adim['durum']->value] ?? 0,
        ], self::AKIS);
    }

    /** Şeridin kapsadığı durumlar — testin enum'la karşılaştırdığı liste. */
    public function kapsananDurumlar(): array
    {
        return array_map(fn (array $a): ListingStatus => $a['durum'], self::AKIS);
    }

    /**
     * Durum başına gerçek ilan sayısı — TEK sorguda.
     *
     * Adım başına ayrı sorgu, beş sorgu demekti; şerit her El Kitabı
     * açılışında çiziliyor.
     *
     * @return array<string, int>
     */
    private function sayimlar(): array
    {
        return Listing::query()
            ->where('is_demo', false)
            ->groupBy('status')
            ->select('status', DB::raw('count(*) as adet'))
            ->pluck('adet', 'status')
            ->map(fn ($a): int => (int) $a)
            ->all();
    }
}
