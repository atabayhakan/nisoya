<?php

namespace App\Services;

use App\Models\Country;
use App\Models\FootballMatch;
use App\Models\FootballTeam;
use App\Models\FootballVenue;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Halı Saha ve Futbol Ekosisteminde Doğal Dil Araması.
 *
 * Ülke, şehir ve anahtar kelime ipuçlarını GERÇEK maç, takım ve tesis
 * kayıtlarına dönüştürür.
 *
 * Eğer aranan ülkede/şehirde henüz takım veya maç organizasyonu yoksa
 * kullanıcıyı yanıtsız bırakmaz; akıllı diaspora tavsiyesi ve doğrudan
 * eyleme geçirici butonlar ('İlk Takımı Sen Kur', 'Maç İlanı Ver', vb.) üretir.
 */
class SporDogalDilArama
{
    private const SONUC_LIMIT = 5;

    /**
     * @param  list<string>  $anahtarKelimeler
     * @return array{
     *     var_mi: bool,
     *     ulke_kodu: ?string,
     *     ulke_adi: string,
     *     ulke_emoji: string,
     *     baslik: string,
     *     mesaj: string,
     *     oneri: string,
     *     eylemler: list<array{baslik: string, url: string, stil: string, ikon?: string}>,
     *     sonuclar: Collection<int, array{baslik: string, altbaslik: string, url: string}>,
     *     ilanBaglantisi: string
     * }
     */
    public function ara(?string $ulkeKodu, ?string $sehir, array $anahtarKelimeler, ?string $varsayilanUlkeKodu = null): array
    {
        $hedefUlke = $this->dogrulaUlke($ulkeKodu) ?? $this->dogrulaUlke($varsayilanUlkeKodu);
        $country = $hedefUlke ? Country::query()->where('code', $hedefUlke)->first() : null;
        $ulkeAdi = $country ? $country->name_tr : 'bulunduğunuz ülkede';
        $ulkeEmoji = $country ? $country->emoji : '🌍';

        /** @var Collection<int, array{baslik: string, altbaslik: string, url: string}> $sonuclar */
        $sonuclar = collect();

        // 1. Maçlar (FootballMatch)
        $maclar = FootballMatch::query()
            ->with(['homeTeam', 'awayTeam', 'venue'])
            ->when($hedefUlke, fn ($q) => $q->where('country_code', $hedefUlke))
            ->when($sehir, fn ($q) => $q->where('city', 'like', "%{$sehir}%"))
            ->latest('match_date')
            ->take(self::SONUC_LIMIT)
            ->get();

        foreach ($maclar as $mac) {
            $evSahibi = $mac->homeTeam->name ?? 'Takım A';
            $deplasman = $mac->awayTeam->name ?? 'Takım B';
            $tarih = $mac->match_date ? $mac->match_date->format('d.m.Y H:i') : 'Tarih Belirleniyor';
            $sehirAdi = $mac->city ?: ($country->name_tr ?? 'Şehir');

            $sonuclar->push([
                'baslik' => "⚽ {$evSahibi} vs {$deplasman}",
                'altbaslik' => "{$sehirAdi} • {$tarih}".($mac->venue ? " • {$mac->venue->name}" : ''),
                'url' => route('football.city', ['city' => Str::slug($mac->city ?: 'sehir')]),
            ]);
        }

        // 2. Takımlar (FootballTeam)
        if ($sonuclar->count() < self::SONUC_LIMIT) {
            $takimlar = FootballTeam::query()
                ->where('is_active', true)
                ->when($hedefUlke, fn ($q) => $q->where('country_code', $hedefUlke))
                ->when($sehir, fn ($q) => $q->where('city', 'like', "%{$sehir}%"))
                ->latest()
                ->take(self::SONUC_LIMIT - $sonuclar->count())
                ->get();

            foreach ($takimlar as $takim) {
                $sehirAdi = $takim->city ?: ($country->name_tr ?? 'Şehir');
                $seviye = $takim->level?->getLabel() ?? 'Amatör';
                $sonuclar->push([
                    'baslik' => "🛡️ {$takim->name}",
                    'altbaslik' => "{$sehirAdi} • {$seviye} Takımı • {$takim->matches_count} Maç",
                    'url' => route('football.teams.show', [
                        'city' => Str::slug($takim->city ?: 'sehir'),
                        'team' => $takim->slug,
                    ]),
                ]);
            }
        }

        // 3. Halı Sahalar (FootballVenue)
        if ($sonuclar->count() < self::SONUC_LIMIT) {
            $sahalar = FootballVenue::query()
                ->where('is_active', true)
                ->when($hedefUlke, fn ($q) => $q->where('country_code', $hedefUlke))
                ->when($sehir, fn ($q) => $q->where('city', 'like', "%{$sehir}%"))
                ->take(self::SONUC_LIMIT - $sonuclar->count())
                ->get();

            foreach ($sahalar as $saha) {
                $sehirAdi = $saha->city ?: ($country->name_tr ?? 'Şehir');
                $sonuclar->push([
                    'baslik' => "🏟️ {$saha->name}",
                    'altbaslik' => "{$sehirAdi} • ".($saha->pitch_type ?? 'Halı Saha')." • Puan: {$saha->rating}",
                    'url' => route('football.venues.index', ['city' => Str::slug($saha->city ?: 'sehir')]),
                ]);
            }
        }

        $varMi = $sonuclar->isNotEmpty();

        if ($varMi) {
            $baslik = "{$ulkeEmoji} {$ulkeAdi} Futbol & Halı Saha Ağı";
            $mesaj = "{$ulkeAdi} genelinde aktif maçlar, takımlar ve halı saha tesisleri bulundu.";
            $oneri = 'Maç detaylarını inceleyebilir, takımlara katılabilir veya kendi ekibinizi oluşturabilirsiniz.';
            $eylemler = [
                ['baslik' => '⚽ Yeni Takım Kur', 'url' => route('football.teams.create'), 'stil' => 'primary', 'ikon' => 'shield'],
                ['baslik' => '📢 Maç İlanı Ver', 'url' => route('football.requests.create'), 'stil' => 'secondary', 'ikon' => 'megaphone'],
                ['baslik' => "🏆 Futbol Hub'ı", 'url' => route('football.index'), 'stil' => 'outline', 'ikon' => 'trophy'],
            ];
        } else {
            $baslik = "{$ulkeEmoji} {$ulkeAdi} için Henüz Maç veya Takım Bulunmuyor";
            $mesaj = "Şu anda bulunduğunuz ülkede ({$ulkeAdi}) henüz aktif bir futbol maçı veya takım organizasyonu oluşturulmamış.";
            $oneri = 'Topluluğu ilk başlatan siz olabilirsiniz! Şehrinizde ilk takımı kurabilir, maç ilanı vererek diğer gurbetçi oyuncuları toplayabilir veya halı saha ekleyebilirsiniz.';
            $eylemler = [
                ['baslik' => '⚽ İlk Takımı Sen Kur', 'url' => route('football.teams.create'), 'stil' => 'primary', 'ikon' => 'plus'],
                ['baslik' => '📢 Maç / Oyuncu İlanı Ver', 'url' => route('football.requests.create'), 'stil' => 'secondary', 'ikon' => 'megaphone'],
                ['baslik' => '🏟️ Halı Saha Ekle', 'url' => route('football.venues.create'), 'stil' => 'outline', 'ikon' => 'building'],
                ['baslik' => "🏆 Futbol Hub'ını Keşfet", 'url' => route('football.index'), 'stil' => 'outline', 'ikon' => 'globe'],
            ];
        }

        return [
            'var_mi' => $varMi,
            'ulke_kodu' => $hedefUlke,
            'ulke_adi' => $ulkeAdi,
            'ulke_emoji' => $ulkeEmoji,
            'baslik' => $baslik,
            'mesaj' => $mesaj,
            'oneri' => $oneri,
            'eylemler' => $eylemler,
            'sonuclar' => $sonuclar,
            'ilanBaglantisi' => route('football.index'),
        ];
    }

    private function dogrulaUlke(?string $kod): ?string
    {
        if ($kod === null || trim($kod) === '') {
            return null;
        }

        $kod = strtoupper(trim($kod));

        return Country::query()->where('is_active', true)->where('code', $kod)->exists() ? $kod : null;
    }
}
