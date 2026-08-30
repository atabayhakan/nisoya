<?php

namespace App\Http\Controllers\Football;

use App\Enums\FootballLevel;
use App\Enums\FootballPosition;
use App\Enums\FootballRequestType;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\FootballPlayerRequest;
use App\Models\FootballPlayerRequestApplication;
use App\Notifications\FootballPlayerAppliedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FootballRequestController extends Controller
{
    public function index(Request $request, string $city): View
    {
        $city = trim(str_replace('-', ' ', $city));
        $cityName = mb_convert_case($city, MB_CASE_TITLE, 'UTF-8');

        $type = $request->string('type')->toString();

        $query = FootballPlayerRequest::query()
            ->active()
            ->city($cityName)
            ->with(['user.footballProfile', 'team', 'match', 'applications.user']);

        if ($type === 'oyuncu') {
            $query->where('type', FootballRequestType::OyuncuAraniyor->value);
        } elseif ($type === 'mac') {
            $query->where('type', FootballRequestType::MacAriyorum->value);
        }

        $requests = $query->latest()->paginate(12)->withQueryString();

        return view('football.requests.index', [
            'currentCity' => $cityName,
            'requests' => $requests,
            'currentType' => $type,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $captainTeams = $user->captainTeams()->where('is_active', true)->get();
        $countries = Country::query()->where('is_active', true)->orderBy('sort_order')->get();
        $userCity = $user->city ?: 'Berlin';
        $userCountry = $user->country_code ?: 'DE';

        return view('football.requests.create', [
            'myTeams' => $captainTeams,
            'countries' => $countries,
            'defaultCity' => $userCity,
            'defaultCountry' => $userCountry,
            'positions' => FootballPosition::cases(),
            'levels' => FootballLevel::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:oyuncu_araniyor,mac_ariyorum'],
            'team_id' => ['nullable', 'exists:football_teams,id'],
            'city' => ['required', 'string', 'max:50'],
            'country_code' => ['required', 'string', 'size:2', 'exists:countries,code'],
            'match_time' => ['nullable', 'date'],
            'venue_name' => ['nullable', 'string', 'max:100'],
            'needed_count' => ['required', 'integer', 'min:1', 'max:15'],
            'level' => ['nullable', 'string', 'in:baslangic,orta,iyi,ileri'],
            'positions' => ['nullable', 'array'],
            'positions.*' => ['string', 'in:kaleci,defans,orta_saha,kanat,forvet'],
            'description' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $playerRequest = FootballPlayerRequest::create([
            'user_id' => $request->user()->id,
            'team_id' => $validated['team_id'] ?? null,
            'type' => $validated['type'],
            'city' => mb_convert_case(trim($validated['city']), MB_CASE_TITLE, 'UTF-8'),
            'country_code' => strtoupper($validated['country_code']),
            'match_time' => $validated['match_time'] ?? null,
            'venue_name' => $validated['venue_name'] ?? null,
            'needed_count' => $validated['needed_count'],
            'level' => $validated['level'] ?? null,
            'positions' => $validated['positions'] ?? [],
            'description' => $validated['description'],
            'is_active' => true,
        ]);

        return to_route('football.requests.index', ['city' => Str::slug($playerRequest->city)])
            ->with('status', 'İlanınız yayınlandı!');
    }

    public function apply(Request $request, FootballPlayerRequest $playerRequest): RedirectResponse
    {
        $user = $request->user();

        if ((int) $user->id === (int) $playerRequest->user_id) {
            return back()->withErrors(['error' => 'Kendi ilanınıza başvuramazsınız.']);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        FootballPlayerRequestApplication::updateOrCreate(
            ['request_id' => $playerRequest->id, 'user_id' => $user->id],
            [
                'status' => 'beklemede',
                'message' => $validated['message'] ?? null,
            ]
        );

        // İlan sahibine bildirim gönder
        $requestUrl = route('football.requests.index', ['city' => Str::slug($playerRequest->city)]);
        $playerRequest->user->notify(new FootballPlayerAppliedNotification($playerRequest, $user->name, $requestUrl));

        return back()->with('status', 'Başvurunuz ilan sahibine iletildi.');
    }

    public function destroy(FootballPlayerRequest $playerRequest): RedirectResponse
    {
        Gate::authorize('delete', $playerRequest);

        $city = $playerRequest->city;
        $playerRequest->delete();

        return to_route('football.requests.index', ['city' => Str::slug($city)])
            ->with('status', 'İlan kaldırıldı.');
    }
}
