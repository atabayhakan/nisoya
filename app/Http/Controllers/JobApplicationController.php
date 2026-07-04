<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Notifications\ApplicationStatusNotification;
use App\Notifications\NewApplicationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobApplicationController extends Controller
{
    /** Aday bir iş ilanına başvurur. */
    public function store(Request $request, JobListing $job): RedirectResponse
    {
        $user = $request->user();

        // Kendi şirketinin ilanına başvuramaz
        if ($user->company && $user->company->id === $job->company_id) {
            return back()->with('status', 'Kendi ilanına başvuramazsın.');
        }

        if (! $job->isOpen()) {
            return back()->with('status', 'Bu ilana artık başvuru alınmıyor.');
        }

        // Zaten başvurmuş mu?
        if ($job->applications()->where('user_id', $user->id)->exists()) {
            return back()->with('status', 'Bu ilana zaten başvurdun.');
        }

        $data = $request->validate([
            'cover_letter' => ['nullable', 'string', 'max:3000'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ], attributes: ['cover_letter' => 'ön yazı', 'cv' => 'CV']);

        $cvPath = null;
        if ($request->hasFile('cv')) {
            // CV'ler herkese açık olmayan disk'te saklanır (sadece işveren indirir).
            $cvPath = $request->file('cv')->store('cvs', 'local');
        }

        $job->applications()->create([
            'user_id' => $user->id,
            'cover_letter' => $data['cover_letter'] ?? null,
            'cv_path' => $cvPath,
            'status' => ApplicationStatus::Gonderildi,
        ]);

        $job->company->user?->notify(new NewApplicationNotification($user->name, $job->title, $job->id));

        return back()->with('status', 'Başvurun gönderildi! İşveren profilini ve (varsa) CV\'ni görebilecek.');
    }

    /** İşveren: bir ilana gelen başvurular. */
    public function applicants(Request $request, JobListing $job): View
    {
        Gate::authorize('manageApplications', $job);

        $applications = $job->applications()
            ->with('applicant')
            ->latest()
            ->get();

        return view('panel.jobs.applicants', compact('job', 'applications'));
    }

    /** İşveren: başvuru durumunu değiştirir → adayı bilgilendirir. */
    public function updateStatus(Request $request, JobApplication $application): RedirectResponse
    {
        $job = $application->jobListing;
        Gate::authorize('manageApplications', $job);

        $request->validate(['status' => ['required', 'in:gonderildi,incelendi,gorusme,kabul,red']]);
        $application->update(['status' => $request->input('status')]);

        $application->applicant?->notify(new ApplicationStatusNotification(
            $job->title,
            $application->status->getLabel(),
            $job->id,
            $job->slug,
        ));

        return back()->with('status', 'Başvuru durumu güncellendi.');
    }

    /** Aday: kendi başvurularım. */
    public function mine(Request $request): View
    {
        $applications = $request->user()->jobApplications()
            ->with(['jobListing.company'])
            ->latest()
            ->paginate(15);

        return view('panel.jobs.my-applications', compact('applications'));
    }

    /** İşveren: bir başvurunun CV'sini indirir. */
    public function downloadCv(Request $request, JobApplication $application): StreamedResponse
    {
        Gate::authorize('manageApplications', $application->jobListing);

        abort_if(! $application->cv_path || ! Storage::disk('local')->exists($application->cv_path), 404);

        $ext = pathinfo($application->cv_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download(
            $application->cv_path,
            'cv-'.($application->applicant->username ?? $application->id).'.'.$ext,
        );
    }
}
