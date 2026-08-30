<?php

namespace App\Http\Controllers\Football;

use App\Enums\FootballLevel;
use App\Enums\FootballPosition;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\FootballPlayerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FootballPlayerController extends Controller
{
    public function index(Request $request, string $city): View
    {
        $city = trim(str_replace('-', ' ', $city));
        $cityName = mb_convert_case($city, MB_CASE_TITLE, 'UTF-8');

        $query = FootballPlayerProfile::query()
            ->city($cityName)
            ->with(['user', 'user.footballTeams'])
            ->whereHas('user', fn ($q) => $q->where('status', 'aktif'));

        if ($request->filled('position')) {
            $pos = $request->string('position');
            $query->whereJsonContains('positions', $pos);
        }

        if ($request->filled('level')) {
            $level = FootballLevel::tryFrom($request->string('level'));
            if ($level) {
                $query->where('level', $level->value);
            }
        }

        if ($request->boolean('looking_for_team')) {
            $query->where('is_looking_for_team', true);
        }

        if ($request->boolean('looking_for_match')) {
            $query->where('is_looking_for_match', true);
        }

        $players = $query->orderBy('rating', 'desc')->paginate(16)->withQueryString();

        return view('football.players.index', [
            'currentCity' => $cityName,
            'players' => $players,
        ]);
    }

    public function edit(Request $request): View
    {
        $user = $request->user();
        $profile = $user->footballProfile ?: new FootballPlayerProfile([
            'user_id' => $user->id,
            'city' => $user->city,
            'country_code' => $user->country_code,
            'level' => FootballLevel::Orta,
        ]);

        $countries = Country::query()->where('is_active', true)->orderBy('sort_order')->get();

        return view('football.players.edit', [
            'profile' => $profile,
            'countries' => $countries,
            'positions' => FootballPosition::cases(),
            'levels' => FootballLevel::cases(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'city' => ['nullable', 'string', 'max:50'],
            'country_code' => ['nullable', 'string', 'size:2', 'exists:countries,code'],
            'positions' => ['nullable', 'array'],
            'positions.*' => ['string', 'in:kaleci,defans,orta_saha,kanat,forvet'],
            'preferred_foot' => ['nullable', 'string', 'in:sol,sag,cift'],
            'level' => ['required', 'string', 'in:baslangic,orta,iyi,ileri'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'is_looking_for_team' => ['nullable', 'boolean'],
            'is_looking_for_match' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        $profile = FootballPlayerProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'city' => filled($validated['city']) ? mb_convert_case(trim($validated['city']), MB_CASE_TITLE, 'UTF-8') : $user->city,
                'country_code' => filled($validated['country_code']) ? strtoupper($validated['country_code']) : $user->country_code,
                'positions' => $validated['positions'] ?? [],
                'preferred_foot' => $validated['preferred_foot'] ?? null,
                'level' => $validated['level'],
                'bio' => $validated['bio'] ?? null,
                'is_looking_for_team' => $request->boolean('is_looking_for_team'),
                'is_looking_for_match' => $request->boolean('is_looking_for_match'),
            ]
        );

        return redirect()->route('profiles.show', $user->username)
            ->with('status', 'Futbol profiliniz başarıyla güncellendi.');
    }
}
