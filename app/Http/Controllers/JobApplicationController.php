<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Jobs\BasvuruDurumBildirimi;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Notifications\NewApplicationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

    /**
     * Bir sütunda en fazla kaç kart basılır.
     *
     * Tavanın altındaki gerçek toplam ayrı bir sayım sorgusundan gelir; kart
     * listesi kısıtlı olsa bile işveren "38 başvurudan 25'i" bilgisini görür.
     */
    public const SUTUN_TAVANI = 25;

    /** İşveren: bir ilana gelen başvurular — Kanban panosu. */
    public function applicants(Request $request, JobListing $job): View
    {
        Gate::authorize('manageApplications', $job);

        // Sütun başına ayrı, TAVANLI sorgu: tek sorgu + PHP'de gruplama,
        // 500 başvurulu bir ilanda hepsini belleğe alırdı.
        $sutunlar = [];
        foreach (ApplicationStatus::cases() as $durum) {
            $sutunlar[$durum->value] = $job->applications()
                ->where('status', $durum->value)
                ->with('applicant')
                ->latest()
                ->limit(self::SUTUN_TAVANI)
                ->get();
        }

        // Gerçek toplamlar tavandan bağımsız. toBase(): sayım satırlarını
        // model olarak hidrate etmeye gerek yok.
        $sayimlar = JobApplication::query()
            ->where('job_listing_id', $job->id)
            ->toBase()
            ->selectRaw('status, count(*) as adet')
            ->groupBy('status')
            ->pluck('adet', 'status')
            ->all();

        return view('panel.jobs.applicants', compact('job', 'sutunlar', 'sayimlar'));
    }

    /**
     * İşveren: başvuru durumunu değiştirir.
     *
     * Bildirim burada GÖNDERİLMEZ — gecikmeli bir işe bırakılır (bkz.
     * BasvuruDurumBildirimi). Böylece yanlış sütuna bırakılan bir kart geri
     * alınabilir ve arka arkaya taşımalar tek e-postada birleşir.
     */
    public function updateStatus(Request $request, JobApplication $application): RedirectResponse|JsonResponse
    {
        $job = $application->jobListing;
        Gate::authorize('manageApplications', $job);

        $data = $request->validate(
            ['status' => ['required', Rule::enum(ApplicationStatus::class)]],
            attributes: ['status' => 'durum'],
        );
        $yeni = ApplicationStatus::from($data['status']);

        // Kirli-kontrol: kartı geldiği sütuna geri bırakmak (ya da aynı
        // seçeneği yeniden seçmek) ne DB'ye yazar ne kuyruğa iş atar.
        if ($application->status === $yeni) {
            return $this->durumYaniti($request, $application, 'Durum zaten buydu.');
        }

        $application->update(['status' => $yeni]);

        if ($yeni->bildirimGerektirir()) {
            BasvuruDurumBildirimi::dispatch($application->id)
                ->delay(now()->addMinutes(BasvuruDurumBildirimi::GECIKME_DAKIKA));

            $mesaj = 'Durum güncellendi. Aday yaklaşık '.BasvuruDurumBildirimi::GECIKME_DAKIKA
                .' dakika içinde bilgilendirilecek — o süre içinde geri alırsan hiç haberi olmaz.';
        } else {
            $mesaj = 'Durum güncellendi. Bu sütun sessiz: adaya bildirim gitmez.';
        }

        return $this->durumYaniti($request, $application, $mesaj);
    }

    /** Pano sürüklemesi fetch ile gelir (JSON), açılır menü klasik form gönderir. */
    private function durumYaniti(Request $request, JobApplication $application, string $mesaj): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'durum' => $application->status->value,
                'etiket' => $application->status->getLabel(),
                'mesaj' => $mesaj,
            ]);
        }

        return back()->with('status', $mesaj);
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
