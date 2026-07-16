<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\ListingType;
use App\Enums\UserStatus;
use App\Models\JobListing;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Header komut paletinin (Cmd+K, Faz H2) "canlı sonuçlar" kısmını besler.
 * Emlak/Vasıta ayrı sorgu gerektirmez — ikisi de Listing tablosunun bir alt
 * kümesi (bkz. App\Enums\ListingType), tek sorgudan type bazlı etiketle döner.
 * Davetiye/Anılar (Event) kasıtlı olarak dışarıda bırakıldı: albümler
 * varsayılan özel ve token'la erişilir, genel aramaya sızdırılmamalı.
 */
class QuickSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $listings = Listing::query()->active()
            ->where(fn ($sub) => $sub->where('title', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%"))
            ->orderByDesc('is_featured')->latest()
            ->limit(5)->get()
            ->map(fn (Listing $listing) => [
                'category' => $listing->type instanceof ListingType ? $listing->type->getLabel() : (string) $listing->type,
                'title' => $listing->title,
                'subtitle' => collect([$listing->city, $listing->country_code])->filter()->implode(' · ') ?: null,
                'url' => route('listings.show', [$listing, $listing->slug]),
            ]);

        $jobs = JobListing::query()->active()->with('company')
            ->where(fn ($sub) => $sub->where('title', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")
                ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$q}%")))
            ->latest()
            ->limit(5)->get()
            ->map(fn (JobListing $job) => [
                'category' => 'İş İlanı',
                'title' => $job->title,
                'subtitle' => $job->company?->name,
                'url' => route('jobs.show', [$job, $job->slug]),
            ]);

        $candidates = User::query()
            ->where('is_searchable', true)
            ->where('status', UserStatus::Aktif->value)
            ->where('account_type', AccountType::Bireysel->value)
            ->where(fn ($sub) => $sub->where('name', 'like', "%{$q}%")
                ->orWhere('bio', 'like', "%{$q}%")
                ->orWhere('skills', 'like', "%{$q}%"))
            ->limit(5)->get()
            ->map(fn (User $user) => [
                'category' => 'Yetenek',
                'title' => $user->name,
                'subtitle' => $user->bio ? Str::limit($user->bio, 60) : null,
                'url' => route('profiles.show', $user->username),
            ]);

        return response()->json([
            'results' => $listings->concat($jobs)->concat($candidates)->values(),
        ]);
    }
}
