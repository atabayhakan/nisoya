<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ev sahibinin davetiye/etkinlik yönetimi (panel).
 * Public davetiye sayfası için bkz. EventInvitationController.
 */
class EventController extends Controller
{
    public function index(Request $request): View
    {
        $events = $request->user()->events()
            ->withCount('guests')
            ->orderByDesc('starts_at')
            ->paginate(12);

        return view('panel.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('panel.events.create', $this->formData());
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $event = $request->user()->events()->create([
            'type' => $data['type'],
            'title' => $data['title'],
            'starts_at' => $data['starts_at'],
            'venue_name' => $data['venue_name'] ?? null,
            'venue_address' => $data['venue_address'] ?? null,
            'description' => $data['description'] ?? null,
            'theme' => $data['theme'] ?? '',
            'is_active' => true,
        ]);

        return redirect()->route('panel.events.show', $event)
            ->with('status', 'Davetiyeni oluşturdun! Şimdi linkini paylaşabilirsin. 🎉');
    }

    /** Yönetim sayfası: LCV sayaçları + davetli listesi + paylaşım. */
    public function show(Request $request, Event $event): View
    {
        abort_unless($event->user_id === $request->user()->id, 403);

        return view('panel.events.show', [
            'event' => $event,
            'summary' => $event->rsvpSummary(),
            'guests' => $event->guests()->paginate(50),
        ]);
    }

    public function edit(Request $request, Event $event): View
    {
        abort_unless($event->user_id === $request->user()->id, 403);

        return view('panel.events.edit', array_merge(['event' => $event], $this->formData()));
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        abort_unless($event->user_id === $request->user()->id, 403);

        $data = $request->validated();

        $event->update([
            'type' => $data['type'],
            'title' => $data['title'],
            'starts_at' => $data['starts_at'],
            'venue_name' => $data['venue_name'] ?? null,
            'venue_address' => $data['venue_address'] ?? null,
            'description' => $data['description'] ?? null,
            'theme' => $data['theme'] ?? $event->theme,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('panel.events.show', $event)
            ->with('status', 'Davetiye güncellendi.');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->user_id === $request->user()->id, 403);

        $event->delete(); // davetliler FK cascade ile silinir

        return redirect()->route('panel.events.index')
            ->with('status', 'Etkinlik ve davetli listesi silindi.');
    }

    protected function formData(): array
    {
        return [
            'types' => EventType::cases(),
            'themes' => config('event_themes'),
        ];
    }
}
