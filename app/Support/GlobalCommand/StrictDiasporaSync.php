<?php

namespace App\Support\GlobalCommand;

use App\Models\DiasporaAccount;
use App\Models\DiasporaReel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Adapter for the existing provider contract. Verify that contract in staging before enabling. */
final class StrictDiasporaSync
{
    public function run(DiasporaAccount $account): int
    {
        if (! $account->is_active || ! $account->is_verified) {
            throw new \RuntimeException('Yalnız aktif ve doğrulanmış hesaplar taranabilir.');
        }
        $host = 'instagram-scraper-api2.p.rapidapi.com';
        $key = config('services.rapidapi.key');
        if (! is_string($key) || $key === '' || config('services.rapidapi.instagram_host', $host) !== $host) {
            throw new \RuntimeException('Diaspora sağlayıcısı yapılandırılmamış veya desteklenmiyor.');
        }
        if (! Schema::hasIndex('diaspora_reels', ['shortcode'], 'unique')) {
            throw new \RuntimeException('Diaspora unique shortcode migration eksik.');
        }
        $username = ltrim($account->username, '@');
        if (! preg_match('/^[A-Za-z0-9._]{1,30}$/D', $username)) {
            throw ValidationException::withMessages(['username' => 'Geçersiz kullanıcı adı.']);
        }
        $response = Http::withHeaders(['x-rapidapi-key' => $key, 'x-rapidapi-host' => $host])
            ->connectTimeout(5)->timeout(15)->withoutRedirecting()
            ->get('https://'.$host.'/v1/posts', ['username_or_id_or_url' => $username])->throw();
        if (strlen($response->body()) > 5 * 1024 * 1024) {
            throw new \RuntimeException('Sağlayıcı yanıtı boyut sınırını aştı.');
        }
        $items = $response->json('data.items');
        if (! is_array($items) || ! array_is_list($items)) {
            throw new \RuntimeException('Sağlayıcı yanıt şeması geçersiz.');
        }
        // A bounded first page. Empty is a valid success; it never generates sample content.
        $rows = [];
        foreach (array_slice($items, 0, 30) as $item) {
            $code = $item['code'] ?? $item['shortcode'] ?? null;
            if (! is_string($code) || ! preg_match('/^[A-Za-z0-9_-]{7,35}$/D', $code)) {
                throw new \RuntimeException('Sağlayıcı içerik kimliği geçersiz.');
            }
            $caption = $item['caption']['text'] ?? $item['title'] ?? null;
            if ($caption !== null && ! is_string($caption)) {
                throw new \RuntimeException('Sağlayıcı açıklaması geçersiz.');
            }
            $rows[] = ['shortcode' => $code, 'caption' => $caption === null ? null : mb_substr($caption, 0, 20000),
                'views_count' => $this->metric($item['view_count'] ?? $item['play_count'] ?? null),
                'likes_count' => $this->metric($item['like_count'] ?? null),
                'thumbnail_url' => RadarMediaUrl::thumbnail($item['thumbnail_url'] ?? null)];
        }

        return DB::transaction(function () use ($account, $rows): int {
            $current = DiasporaAccount::query()->lockForUpdate()->findOrFail($account->id);
            if (! $current->is_active || ! $current->is_verified || $current->country_code !== $account->country_code
                || $current->city !== $account->city || $current->username !== $account->username) {
                throw new \RuntimeException('Hesap tarama sırasında değişti; sonuç uygulanmadı.');
            }
            $created = 0;
            foreach ($rows as $row) {
                $reel = DiasporaReel::query()->firstOrCreate(['shortcode' => $row['shortcode']], [
                    ...$row, 'account_id' => $current->id, 'instagram_username' => $current->username,
                    'instagram_url' => 'https://www.instagram.com/reel/'.$row['shortcode'].'/',
                    'title' => Str::limit($row['caption'] ?: 'Yeni diaspora içeriği', 100),
                    'country_code' => $current->country_code, 'city' => $current->city,
                    'status' => 'draft', 'is_active' => false, 'safety_score' => null,
                    'safety_status' => 'review_needed', 'engagement_score' => 0,
                ]);
                if ($reel->wasRecentlyCreated) {
                    $created++;
                } elseif ($reel->account_id === $current->id) {
                    // Editorial caption/status stay intact. Refresh only observed source metrics.
                    $reel->update(['views_count' => $row['views_count'], 'likes_count' => $row['likes_count']]);
                }
            }
            $current->update(['last_synced_at' => now(), 'reels_count' => $current->reels()->count()]);

            return $created;
        });
    }

    private function metric(mixed $value): ?int
    {
        return is_int($value) && $value >= 0 && $value <= 4294967295 ? $value : null;
    }
}
