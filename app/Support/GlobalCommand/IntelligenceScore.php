<?php

declare(strict_types=1);

namespace App\Support\GlobalCommand;

use App\Models\DiasporaReel;

final class IntelligenceScore
{
    /** @return array{label: string, color: string, explanation: string, ai: string} */
    public static function forReel(DiasporaReel $reel): array
    {
        $score = $reel->safety_score;
        $status = $reel->safety_status;

        // Existing scores come from rules. No model/version/hash assessment exists.
        $result = [
            'label' => 'Ön tarama: İncelenmedi',
            'color' => 'gray',
            'explanation' => 'Geçerli bir güvenlik ön taraması bulunmuyor. Etkileşim puanı güvenlik kanıtı değildir.',
            'ai' => 'AI: İncelenmedi',
        ];

        $assessment = $reel->relationLoaded('latestAssessment') ? $reel->latestAssessment : null;
        if ($assessment && $assessment->status === 'completed'
            && app(ContentQuality::class)->hash($reel) === $assessment->source_hash) {
            $result['ai'] = 'AI risk: '.$assessment->risk_score.'/100 · güven '.(int) round(($assessment->ai_result['confidence'] ?? 0) * 100).'%';
        }

        if ($score === null || $score < 0 || $score > 100
            || ! in_array($status, ['safe', 'review_needed', 'rejected'], true)) {
            return $result;
        }

        $color = match (true) {
            $status === 'rejected', $score < 60 => 'danger',
            $status === 'review_needed', $score < 85 => 'warning',
            default => 'success',
        };

        $meaning = match ($color) {
            'danger' => 'Risk sinyali',
            'warning' => 'İnceleme gerekli',
            default => 'Düşük risk sinyali',
        };

        return array_replace($result, [
            'label' => "Ön tarama: {$score}/100 · {$meaning}",
            'color' => $color,
            'explanation' => 'Kural tabanlı ön tarama; yüksek puan daha güvenli sinyal demektir. İnsan doğrulaması veya AI değerlendirmesi değildir.',
        ]);
    }
}
