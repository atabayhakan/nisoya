<?php

namespace App\Http\Controllers\Football;

use App\Enums\FootballLevel;
use App\Enums\FootballMemberStatus;
use App\Enums\FootballPosition;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\FootballMatch;
use App\Models\FootballTeam;
use App\Models\FootballTeamMember;
use App\Models\User;
use App\Notifications\FootballTeamInviteNotification;
use App\Services\ImageService;
use App\Services\ProfanityFilterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FootballTeamController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
        private readonly ProfanityFilterService $profanityFilter,
    ) {}

    public function index(Request $request, string $city): View
    {
        $city = trim(str_replace('-', ' ', $city));
        $cityName = mb_convert_case($city, MB_CASE_TITLE, 'UTF-8');

        $query = FootballTeam::query()
            ->active()
            ->city($cityName)
            ->with(['captain', 'country'])
            ->withCount('activeMembers');

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($request->filled('level')) {
            $level = FootballLevel::tryFrom($request->string('level'));
            if ($level) {
                $query->where('level', $level->value);
            }
        }

        $teams = $query->orderBy('points', 'desc')->paginate(12)->withQueryString();

        return view('football.teams.index', [
            'currentCity' => $cityName,
            'teams' => $teams,
        ]);
    }

    public function show(string $city, FootballTeam $team): View
    {
        $team->load(['captain', 'country', 'activeMembers.user.footballProfile']);

        $recentMatches = FootballMatch::query()
            ->verified()
            ->where(function ($q) use ($team) {
                $q->where('home_team_id', $team->id)
                    ->orWhere('away_team_id', $team->id);
            })
            ->with(['homeTeam', 'awayTeam', 'venue'])
            ->orderBy('match_date', 'desc')
            ->take(5)
            ->get();

        $upcomingMatches = FootballMatch::query()
            ->where('match_date', '>=', now())
            ->whereIn('status', ['planlandi', 'onaylandi'])
            ->where(function ($q) use ($team) {
                $q->where('home_team_id', $team->id)
                    ->orWhere('away_team_id', $team->id);
            })
            ->with(['homeTeam', 'awayTeam', 'venue'])
            ->orderBy('match_date', 'asc')
            ->take(3)
            ->get();

        $userMembership = auth()->check()
            ? FootballTeamMember::where('team_id', $team->id)->where('user_id', auth()->id())->first()
            : null;

        return view('football.teams.show', [
            'currentCity' => $team->city,
            'team' => $team,
            'recentMatches' => $recentMatches,
            'upcomingMatches' => $upcomingMatches,
            'userMembership' => $userMembership,
        ]);
    }

    public function create(Request $request): View
    {
        $countries = Country::query()->where('is_active', true)->orderBy('sort_order')->get();
        $userCity = $request->user()->city ?: 'Berlin';
        $userCountry = $request->user()->country_code ?: 'DE';

        return view('football.teams.create', [
            'countries' => $countries,
            'defaultCity' => $userCity,
            'defaultCountry' => $userCountry,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:60'],
            'city' => ['required', 'string', 'max:50'],
            'country_code' => ['required', 'string', 'size:2', 'exists:countries,code'],
            'level' => ['required', 'string', 'in:baslangic,orta,iyi,ileri'],
            'primary_kit_color' => ['nullable', 'string', 'max:30'],
            'secondary_kit_color' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($this->profanityFilter->hasProfanity($validated['name'])) {
            return back()->withInput()->withErrors(['name' => 'Takım ismi uygunsuz kelimeler içeremez.']);
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $processed = $this->imageService->storeOptimized($request->file('logo'), 'football/teams');
            $logoPath = $processed['medium'] ?? ($processed['thumb'] ?? null);
        }

        $team = FootballTeam::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'city' => mb_convert_case(trim($validated['city']), MB_CASE_TITLE, 'UTF-8'),
            'country_code' => strtoupper($validated['country_code']),
            'level' => $validated['level'],
            'primary_kit_color' => $validated['primary_kit_color'] ?? null,
            'secondary_kit_color' => $validated['secondary_kit_color'] ?? null,
            'description' => $validated['description'] ?? null,
            'logo_path' => $logoPath,
            'is_active' => true,
        ]);

        // Kaptanı aktif üye olarak takıma kaydet
        FootballTeamMember::create([
            'team_id' => $team->id,
            'user_id' => $request->user()->id,
            'role' => 'captain',
            'status' => FootballMemberStatus::Aktif->value,
            'joined_at' => now(),
        ]);

        return redirect()->route('football.teams.show', ['city' => Str::slug($team->city), 'team' => $team->slug])
            ->with('status', 'Takımınız başarıyla kuruldu! Şimdi oyuncu davet edebilir veya maç planlayabilirsiniz.');
    }

    public function edit(FootballTeam $team): View
    {
        Gate::authorize('update', $team);

        $countries = Country::query()->where('is_active', true)->orderBy('sort_order')->get();

        return view('football.teams.edit', [
            'team' => $team,
            'countries' => $countries,
        ]);
    }

    public function update(Request $request, FootballTeam $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:60'],
            'city' => ['required', 'string', 'max:50'],
            'country_code' => ['required', 'string', 'size:2', 'exists:countries,code'],
            'level' => ['required', 'string', 'in:baslangic,orta,iyi,ileri'],
            'primary_kit_color' => ['nullable', 'string', 'max:30'],
            'secondary_kit_color' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($this->profanityFilter->hasProfanity($validated['name'])) {
            return back()->withInput()->withErrors(['name' => 'Takım ismi uygunsuz kelimeler içeremez.']);
        }

        if ($request->hasFile('logo')) {
            $processed = $this->imageService->storeOptimized($request->file('logo'), 'football/teams');
            $team->logo_path = $processed['medium'] ?? ($processed['thumb'] ?? null);
        }

        $team->update([
            'name' => $validated['name'],
            'city' => mb_convert_case(trim($validated['city']), MB_CASE_TITLE, 'UTF-8'),
            'country_code' => strtoupper($validated['country_code']),
            'level' => $validated['level'],
            'primary_kit_color' => $validated['primary_kit_color'] ?? null,
            'secondary_kit_color' => $validated['secondary_kit_color'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('football.teams.show', ['city' => Str::slug($team->city), 'team' => $team->slug])
            ->with('status', 'Takım bilgileri güncellendi.');
    }

    public function invitePlayer(Request $request, FootballTeam $team): RedirectResponse
    {
        Gate::authorize('invite', $team);

        $validated = $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
            'primary_position' => ['nullable', 'string', 'in:kaleci,defans,orta_saha,kanat,forvet'],
            'jersey_number' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $targetUser = User::where('username', $validated['username'])->firstOrFail();

        $existing = FootballTeamMember::where('team_id', $team->id)->where('user_id', $targetUser->id)->first();
        if ($existing) {
            if ($existing->status === FootballMemberStatus::Aktif) {
                return back()->withErrors(['username' => 'Bu oyuncu zaten takım kadrosunda.']);
            }
            if ($existing->status === FootballMemberStatus::DavetEdildi) {
                return back()->withErrors(['username' => 'Bu oyuncuya zaten davet gönderilmiş.']);
            }
            $existing->update([
                'status' => FootballMemberStatus::DavetEdildi->value,
                'primary_position' => $validated['primary_position'] ?? null,
                'jersey_number' => $validated['jersey_number'] ?? null,
            ]);
        } else {
            FootballTeamMember::create([
                'team_id' => $team->id,
                'user_id' => $targetUser->id,
                'role' => 'player',
                'status' => FootballMemberStatus::DavetEdildi->value,
                'primary_position' => $validated['primary_position'] ?? null,
                'jersey_number' => $validated['jersey_number'] ?? null,
            ]);
        }

        $teamUrl = route('football.teams.show', ['city' => Str::slug($team->city), 'team' => $team->slug]);
        $targetUser->notify(new FootballTeamInviteNotification($team, $request->user()->name, $teamUrl));

        return back()->with('status', "{$targetUser->name} takım kadrosuna davet edildi.");
    }

    public function requestToJoin(Request $request, FootballTeam $team): RedirectResponse
    {
        $user = $request->user();

        $existing = FootballTeamMember::where('team_id', $team->id)->where('user_id', $user->id)->first();
        if ($existing) {
            if ($existing->status === FootballMemberStatus::Aktif) {
                return back()->with('status', 'Zaten bu takımın bir parçasısınız.');
            }
            if ($existing->status === FootballMemberStatus::DavetEdildi) {
                // Daveti doğrudan kabul et
                $existing->update(['status' => FootballMemberStatus::Aktif->value, 'joined_at' => now()]);

                return back()->with('status', "{$team->name} takımına başarıyla katıldınız!");
            }
            if ($existing->status === FootballMemberStatus::Basvurdu) {
                return back()->with('status', 'Katılma talebiniz kaptan onayını bekliyor.');
            }
            $existing->update(['status' => FootballMemberStatus::Basvurdu->value]);
        } else {
            FootballTeamMember::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => 'player',
                'status' => FootballMemberStatus::Basvurdu->value,
            ]);
        }

        return back()->with('status', 'Takıma katılma talebiniz kaptana iletildi.');
    }

    public function respondInvite(Request $request, FootballTeamMember $member): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:accept,reject'],
        ]);

        $user = $request->user();
        $isPlayer = (int) $user->id === (int) $member->user_id;
        $isCaptain = (int) $user->id === (int) $member->team->user_id;

        if (! $isPlayer && ! $isCaptain && ! ($user->role?->canAccessAdminPanel() ?? false)) {
            abort(403);
        }

        if ($validated['action'] === 'accept') {
            $member->update([
                'status' => FootballMemberStatus::Aktif->value,
                'joined_at' => now(),
            ]);

            return back()->with('status', 'Takım üyeliği onaylandı.');
        }

        $member->update([
            'status' => FootballMemberStatus::Reddedildi->value,
        ]);

        return back()->with('status', 'Davet / talep reddedildi.');
    }

    public function leaveTeam(Request $request, FootballTeam $team): RedirectResponse
    {
        $userId = $request->input('user_id', $request->user()->id);
        $targetUser = User::findOrFail($userId);

        Gate::authorize('removeMember', [$team, $targetUser]);

        if ((int) $targetUser->id === (int) $team->user_id) {
            return back()->withErrors(['error' => 'Takım kaptanı takımdan ayrılamaz. Önce kaptanlığı devretmeli veya takımı silmelisiniz.']);
        }

        FootballTeamMember::where('team_id', $team->id)->where('user_id', $targetUser->id)->delete();

        return back()->with('status', 'Oyuncu takım kadrosundan ayrıldı.');
    }
}
