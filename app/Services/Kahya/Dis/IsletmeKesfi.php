<?php

namespace App\Services\Kahya\Dis;

use App\Models\KahyaHarcamasi;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * İşletme keşif motoru (F3): Google Places (New) Text Search.
 *
 * Tanıtım planının (docs/06) keşif kalbi — "{şehir} × {meslek} × {dil}"
 * sorgu permütasyonunun tek sorgu ayağı. RESMÎ API kullanılır: Maps
 * kazıması ToS ihlalidir ve zaten e-posta da vermez; buradan yalnız
 * ad/adres/site gelir, iletişim zenginleştirme ayrı ve hedefli yapılır
 * (docs/06 §2). Place ID süresiz saklanabilir; koordinat saklamıyoruz
 * (30 günlük önbellek kuralına hiç girmemek için).
 *
 * Her çağrı deftere yazılır ve aylık limite tabidir — Places ücretli.
 */
class IsletmeKesfi
{
    public const KAYNAK = 'isletme-kesfet';

    public function hazirMi(): bool
    {
        return $this->anahtar() !== '';
    }

    public function buAykiKullanim(): int
    {
        return KahyaHarcamasi::query()
            ->where('kaynak', self::KAYNAK)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function aylikLimit(): int
    {
        return max(0, (int) (Settings::get('kahya.aylik_kesif_limiti') ?: 200));
    }

    /**
     * Metin aramasıyla işletme keşfi. Ör: "Turkish barber Rotterdam".
     *
     * @return list<array{ad: string, adres: string, puan: ?float, site: ?string, place_id: string}>
     *
     * @throws \RuntimeException sağlayıcı hatasında
     */
    public function kesfet(string $sorgu, int $sonucSayisi = 10): array
    {
        $sonucSayisi = min(max($sonucSayisi, 1), 20);

        $yanit = Http::timeout(20)
            ->withHeaders([
                'X-Goog-Api-Key' => $this->anahtar(),
                // FieldMask maliyetin kendisidir: yalnız istenen alan ödenir.
                // websiteUri iletişim zenginleştirmenin kapısı olduğu için
                // listede; telefon/e-posta İSTENMEZ (Places e-posta vermez,
                // telefonu da bu aşamada toplamıyoruz).
                'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.rating,places.websiteUri',
            ])
            ->post('https://places.googleapis.com/v1/places:searchText', [
                'textQuery' => $sorgu,
                'maxResultCount' => $sonucSayisi,
            ]);

        if (! $yanit->successful()) {
            Log::error('Kâhya işletme keşfi başarısız', ['status' => $yanit->status()]);

            throw new \RuntimeException("Places API hata döndürdü (HTTP {$yanit->status()}). Anahtarı ve faturalandırmayı Kâhya Ayarları'ndan/Google Cloud'dan kontrol et.");
        }

        KahyaHarcamasi::create([
            'kaynak' => self::KAYNAK,
            'saglayici' => 'google-places',
            'model' => '',
            'detay' => mb_substr($sorgu, 0, 200),
        ]);

        return collect($yanit->json('places') ?? [])
            ->map(fn (array $p): array => [
                'ad' => (string) (($p['displayName']['text'] ?? null) ?? ''),
                'adres' => (string) ($p['formattedAddress'] ?? ''),
                'puan' => isset($p['rating']) ? (float) $p['rating'] : null,
                'site' => isset($p['websiteUri']) ? (string) $p['websiteUri'] : null,
                'place_id' => (string) ($p['id'] ?? ''),
            ])
            ->values()
            ->all();
    }

    private function anahtar(): string
    {
        return trim((string) Settings::get('kahya.places_anahtari', ''));
    }
}
