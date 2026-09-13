<?php

declare(strict_types=1);

namespace App\Services\Diaspora;

use App\Models\DiasporaReel;

/**
 * Diaspora Reels etkileşim, popülerlik ve vitrin sıralama motoru.
 *
 * Beğeni, izlenme ve güncellik çarpanlarını hesaplayarak yüksek
 * performanslı içerikleri otomatik olarak 2x2 geniş bento karta terfi ettirir.
 */
class DiasporaRankingEngine
{
    /**
     * Tüm aktif reels içeriklerinin etkileşim puanlarını yeniden hesaplar
     * ve vitrin sıralamasını günceller.
     *
     * @return array{recalculated_count: int, promoted_featured: int, demoted_featured: int}
     */
    public function recalculateAndRank(): array
    {
        $reels = DiasporaReel::query()
            ->where('status', DiasporaReel::STATUS_PUBLISHED)
            ->where('is_active', true)
            ->get();

        $promoted = 0;
        $demoted = 0;

        foreach ($reels as $reel) {
            $score = $this->calculateScore($reel);
            $reel->engagement_score = $score;
            $reel->saveQuietly();
        }

        // Sıralamayı engagement_score azalan ve en son eklenenlere göre güncelle
        $sorted = DiasporaReel::query()
            ->where('status', DiasporaReel::STATUS_PUBLISHED)
            ->where('is_active', true)
            ->orderByDesc('engagement_score')
            ->orderByDesc('id')
            ->get();

        $order = 1;
        foreach ($sorted as $index => $reel) {
            $reel->sort_order = $order++;

            // İlk 2 en popüler içeriği öne çıkan (geniş bento kart) yap
            $shouldBeFeatured = ($index < 2) && ($reel->engagement_score > 80);

            if ($shouldBeFeatured && ! $reel->is_featured) {
                $reel->is_featured = true;
                $promoted++;
            } elseif (! $shouldBeFeatured && $reel->is_featured && $index >= 3) {
                $reel->is_featured = false;
                $demoted++;
            }

            $reel->saveQuietly();
        }

        return [
            'recalculated_count' => $reels->count(),
            'promoted_featured' => $promoted,
            'demoted_featured' => $demoted,
        ];
    }

    /**
     * Tek bir reel için etkileşim puanını hesaplar.
     */
    public function calculateScore(DiasporaReel $reel): int
    {
        $likesWeight = $reel->likes_count * 2;
        $viewsWeight = (int) ($reel->views_count / 100);

        // Güncellik Bonusu (Son 7 gün ise +50 puan, son 30 gün ise +20 puan)
        $recencyBonus = 0;
        if ($reel->created_at) {
            $daysOld = $reel->created_at->diffInDays(now());
            if ($daysOld <= 7) {
                $recencyBonus = 50;
            } elseif ($daysOld <= 30) {
                $recencyBonus = 20;
            }
        }

        // Güvenlik puanı etkisi (Güvenlik yüksekse çarpan tam, düşükse ceza)
        $safetyMultiplier = ($reel->safety_score ?? 100) >= 80 ? 1.0 : 0.5;

        $raw = (int) (($likesWeight + $viewsWeight + $recencyBonus) * $safetyMultiplier);

        return max(10, $raw);
    }
}
