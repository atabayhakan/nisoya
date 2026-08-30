<?php

namespace App\Http\Controllers\Football;

use App\Enums\FootballMatchStatus;
use App\Enums\FootballResultStatus;
use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use App\Models\FootballTeam;
use App\Models\FootballVenue;
use App\Models\User;
use App\Notifications\FootballMatchInviteNotification;
use App\Notifications\FootballMatchResultNotification;
use App\Notifications\FootballMatchVerifiedNotification;
use App\Services\Football\FootballNewsService;
use App\Services\Football\FootballStatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FootballMatchController extends Controller
{
    public function __construct(
        private readonly FootballStatsService $statsService,
        private readonly FootballNewsService $newsService,
    ) {}

    public function index(Request $request, string $city): View
    {
        $city = trim(str_replace('-', ' ', $city));
        $cityName = mb_convert_case($city, MB_CASE_TITLE, 'UTF-8');

        $tab = $request->string('tab', 'upcoming')->toString();

        $query = FootballMatch::query()
            ->city($cityName)
            ->with(['homeTeam', 'awayTeam', 'venue', 'mvpPlayer']);

        if ($tab === 'played') {
            $query->where('status', FootballMatchStatus::Oynandi->value)
                ->orderBy('match_date', 'desc');
        } else {
            $query->whereIn('status', [FootballMatchStatus::Planlandi->value, FootballMatchStatus::Onaylandi->value])
                ->where('match_date', '>=', now()->subHours(4))
                ->orderBy('match_date', 'asc');
        }

        $matches = $query->paginate(12)->withQueryString();

        return view('football.matches.index', [
            'currentCity' => $cityName,
            'matches' => $matches,
            'currentTab' => $tab,
        ]);
    }

    public function show(string $city, FootballMatch $match): View
    {
        $match->load(['homeTeam.captain', 'awayTeam.captain', 'venue', 'mvpPlayer', 'resultSubmittedBy', 'resultVerifiedBy']);

        $whatsAppShareText = $this->newsService->generateWhatsAppShareText($match);

        return view('football.matches.show', [
            'currentCity' => $match->city,
            'match' => $match,
            'whatsAppShareText' => $whatsAppShareText,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $captainTeams = $user->captainTeams()->where('is_active', true)->get();

        if ($captainTeams->isEmpty()) {
            return view('football.matches.create_blocked');
        }

        $firstTeam = $captainTeams->first();
        $userCity = $firstTeam->city ?: ($user->city ?: 'Berlin');

        $opponentTeams = FootballTeam::query()
            ->active()
            ->whereNotIn('id', $captainTeams->pluck('id'))
            ->city($userCity)
            ->get();

        $venues = FootballVenue::query()
            ->active()
            ->city($userCity)
            ->get();

        return view('football.matches.create', [
            'myTeams' => $captainTeams,
            'opponentTeams' => $opponentTeams,
            'venues' => $venues,
            'defaultCity' => $userCity,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'home_team_id' => ['required', 'exists:football_teams,id'],
            'away_team_id' => ['nullable', 'exists:football_teams,id', 'different:home_team_id'],
            'venue_id' => ['nullable', 'exists:football_venues,id'],
            'venue_custom_name' => ['nullable', 'string', 'max:100'],
            'match_date' => ['required', 'date', 'after:now'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $homeTeam = FootballTeam::findOrFail($validated['home_team_id']);
        if ((int) $homeTeam->user_id !== (int) $user->id && ! ($user->role?->canAccessAdminPanel() ?? false)) {
            abort(403, 'Sadece kaptanı olduğunuz takım adına maç oluşturabilirsiniz.');
        }

        $status = empty($validated['away_team_id']) ? FootballMatchStatus::Planlandi : FootballMatchStatus::Planlandi;

        $match = FootballMatch::create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $validated['away_team_id'] ?? null,
            'venue_id' => $validated['venue_id'] ?? null,
            'venue_custom_name' => $validated['venue_custom_name'] ?? null,
            'city' => $homeTeam->city,
            'country_code' => $homeTeam->country_code,
            'match_date' => $validated['match_date'],
            'status' => $status->value,
            'description' => $validated['description'] ?? null,
            'result_status' => FootballResultStatus::Beklemede->value,
        ]);

        // Rakip kaptana bildirim gönder
        if (! empty($validated['away_team_id'])) {
            $awayTeam = FootballTeam::find($validated['away_team_id']);
            if ($awayTeam && $awayTeam->captain) {
                $matchUrl = route('football.matches.show', ['city' => Str::slug($match->city), 'match' => $match->id]);
                $awayTeam->captain->notify(new FootballMatchInviteNotification($match, $homeTeam->name, $matchUrl));
            }
        }

        return redirect()->route('football.matches.show', ['city' => Str::slug($match->city), 'match' => $match->id])
            ->with('status', 'Maç başarıyla oluşturuldu.');
    }

    public function respondMatch(Request $request, FootballMatch $match): RedirectResponse
    {
        Gate::authorize('respond', $match);

        $action = $request->input('action');
        if ($action === 'accept') {
            $match->update(['status' => FootballMatchStatus::Onaylandi->value]);

            return back()->with('status', 'Maç teklifi kabul edildi.');
        }

        $match->update([
            'status' => FootballMatchStatus::Iptal->value,
        ]);

        return back()->with('status', 'Maç teklifi reddedildi.');
    }

    public function enterScore(FootballMatch $match): View
    {
        Gate::authorize('submitScore', $match);

        $match->load(['homeTeam.activeMembers.user', 'awayTeam.activeMembers.user']);

        return view('football.matches.score', [
            'match' => $match,
        ]);
    }

    public function submitScore(Request $request, FootballMatch $match): RedirectResponse
    {
        Gate::authorize('submitScore', $match);

        $validated = $request->validate([
            'home_score' => ['required', 'integer', 'min:0', 'max:50'],
            'away_score' => ['required', 'integer', 'min:0', 'max:50'],
            'mvp_player_id' => ['nullable', 'exists:users,id'],
            'home_scorers' => ['nullable', 'array'],
            'home_scorers.*.user_id' => ['nullable', 'exists:users,id'],
            'home_scorers.*.goals' => ['nullable', 'integer', 'min:1', 'max:30'],
            'away_scorers' => ['nullable', 'array'],
            'away_scorers.*.user_id' => ['nullable', 'exists:users,id'],
            'away_scorers.*.goals' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $user = $request->user();

        // Haber taslağı üret
        $match->home_score = $validated['home_score'];
        $match->away_score = $validated['away_score'];
        $match->mvp_player_id = $validated['mvp_player_id'] ?? null;
        $match->home_scorers = $validated['home_scorers'] ?? [];
        $match->away_scorers = $validated['away_scorers'] ?? [];

        $news = $this->newsService->generateMatchNews($match);

        $match->update([
            'home_score' => $validated['home_score'],
            'away_score' => $validated['away_score'],
            'result_status' => FootballResultStatus::Girildi->value,
            'result_submitted_by_id' => $user->id,
            'mvp_player_id' => $validated['mvp_player_id'] ?? null,
            'home_scorers' => $validated['home_scorers'] ?? [],
            'away_scorers' => $validated['away_scorers'] ?? [],
            'news_title' => $news['title'],
            'news_summary' => $news['summary'],
            'news_body' => $news['body'],
            'news_generated_at' => now(),
        ]);

        // Rakip kaptana bildirim gönder
        $isHomeSubmitter = (int) $user->id === (int) $match->homeTeam?->user_id;
        $opponentCaptain = $isHomeSubmitter ? $match->awayTeam?->captain : $match->homeTeam?->captain;
        $submittedTeamName = $isHomeSubmitter ? ($match->homeTeam?->name ?: 'Ev Sahibi') : ($match->awayTeam?->name ?: 'Deplasman');

        if ($opponentCaptain) {
            $matchUrl = route('football.matches.show', ['city' => Str::slug($match->city), 'match' => $match->id]);
            $opponentCaptain->notify(new FootballMatchResultNotification(
                $match,
                $submittedTeamName,
                (int) $validated['home_score'],
                (int) $validated['away_score'],
                $matchUrl
            ));
        }

        return redirect()->route('football.matches.show', ['city' => Str::slug($match->city), 'match' => $match->id])
            ->with('status', 'Maç skoru girildi. Rakip kaptanın onayı bekleniyor.');
    }

    public function verifyScore(Request $request, FootballMatch $match): RedirectResponse
    {
        Gate::authorize('verifyScore', $match);

        $user = $request->user();

        $match->update([
            'result_status' => FootballResultStatus::Dogrulandi->value,
            'status' => FootballMatchStatus::Oynandi->value,
            'result_verified_by_id' => $user->id,
        ]);

        // İstatistikleri uygula ve lig tablosunu güncelle
        $this->statsService->applyVerifiedMatchStats($match);

        // Her iki kaptana da başarı bildirimi gönder
        $matchUrl = route('football.matches.show', ['city' => Str::slug($match->city), 'match' => $match->id]);
        $match->homeTeam?->captain?->notify(new FootballMatchVerifiedNotification($match, $matchUrl));
        $match->awayTeam?->captain?->notify(new FootballMatchVerifiedNotification($match, $matchUrl));

        return redirect()->route('football.matches.show', ['city' => Str::slug($match->city), 'match' => $match->id])
            ->with('status', '🎉 Maç sonucu doğrulandı! Şehir lig tablosu ve istatistikler güncellendi.');
    }

    public function disputeScore(Request $request, FootballMatch $match): RedirectResponse
    {
        Gate::authorize('disputeScore', $match);

        $validated = $request->validate([
            'dispute_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $match->update([
            'result_status' => FootballResultStatus::Itiraz->value,
            'dispute_reason' => $validated['dispute_reason'],
        ]);

        return redirect()->route('football.matches.show', ['city' => Str::slug($match->city), 'match' => $match->id])
            ->with('status', 'Skora itiraz edildi. Yönetici incelemesine alındı.');
    }
}
