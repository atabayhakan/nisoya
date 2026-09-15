<?php

namespace App\Support\GlobalCommand;

use App\Models\DiasporaReel;
use App\Models\JobListing;
use App\Models\Listing;
use Illuminate\Support\Facades\DB;

class QualityScanner
{
    public function scan(ContentQuality $quality, int $limit): int
    {
        $scanned = 0;
        foreach (['listing' => Listing::class, 'job' => JobListing::class, 'reel' => DiasporaReel::class] as $kind => $class) {
            $scanned += DB::transaction(function () use ($kind, $class, $quality, $limit): int {
                DB::table('quality_scan_cursors')->insertOrIgnore(['kind' => $kind, 'last_id' => 0]);
                $cursor = DB::table('quality_scan_cursors')->where('kind', $kind)->lockForUpdate()->first();
                // Freeze the end of this pass so continuous arrivals cannot starve older edits.
                $through = $cursor->through_id ?? (int) $class::max('id');
                $query = $class::where('id', '>', $cursor->last_id)->where('id', '<=', $through)->orderBy('id')->limit(max(1, min(500, $limit)));
                if ($class === Listing::class) {
                    $query->withCount('images');
                }
                $sources = $query->get();
                foreach ($sources as $source) {
                    $quality->request($kind, $source, null);
                }
                $lastSource = $sources->last();
                $last = $lastSource !== null ? $lastSource->id : $through;
                $complete = $last >= $through || $sources->count() < max(1, min(500, $limit));
                DB::table('quality_scan_cursors')->where('kind', $kind)->update([
                    'last_id' => $complete ? 0 : $last,
                    'through_id' => $complete ? null : $through,
                    'last_scanned_at' => now(),
                    'cycle_completed_at' => $complete ? now() : $cursor->cycle_completed_at,
                ]);

                return $sources->count();
            });
        }

        return $scanned;
    }
}
