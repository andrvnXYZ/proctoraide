<?php

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Proctor\Concerns\AuthorizesExamination;
use App\Http\Requests\Proctor\StoreExaminationRequest;
use App\Models\Enrollment;
use App\Models\Examination;
use App\Support\ExaminationPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExaminationController extends Controller
{
    use AuthorizesExamination;

    public function index(Request $request): Response
    {
        $proctorId = (string) $request->user()->getKey();

        $enrollmentCounts = Enrollment::where('proctor_id', $proctorId)
            ->get()
            ->groupBy('section')
            ->map(fn ($enrollments) => $enrollments->pluck('student_id')->unique()->count());

        $examinations = Examination::where('proctor_id', $proctorId)
            ->orderByDesc('scheduled_date')
            ->orderByDesc('start_time')
            ->get()
            ->map(fn (Examination $examination) => ExaminationPresenter::toArray(
                $examination,
                $enrollmentCounts->get($examination->section, 0),
            ))
            ->values();

        return Inertia::render('proctor/examinations/index', [
            'examinations' => $examinations,
            'sections' => $enrollmentCounts->keys()->values(),
            'openCreateForm' => $request->boolean('create'),
        ]);
    }

    public function store(StoreExaminationRequest $request): RedirectResponse
    {
        $examination = Examination::create([
            ...$request->validated(),
            'proctor_id' => (string) $request->user()->getKey(),
            'access_code' => Examination::generateAccessCode(),
            'status' => Examination::STATUS_SCHEDULED,
        ]);

        return redirect()
            ->route('proctor.examinations.index')
            ->with('success', "Examination \"{$examination->title}\" created. Access code: {$examination->access_code}.");
    }

    public function start(Request $request, Examination $examination): RedirectResponse
    {
        $this->authorizeExamination($request, $examination);

        if ($examination->status !== Examination::STATUS_SCHEDULED) {
            return back()->with('error', 'Only a scheduled examination can be started.');
        }

        $examination->forceFill([
            'status' => Examination::STATUS_ONGOING,
            'started_at' => now(),
            'ended_at' => null,
        ])->save();

        return back()->with('success', "\"{$examination->title}\" is now ongoing.");
    }

    public function end(Request $request, Examination $examination): RedirectResponse
    {
        $this->authorizeExamination($request, $examination);

        if ($examination->status !== Examination::STATUS_ONGOING) {
            return back()->with('error', 'Only an ongoing examination can be ended.');
        }

        $examination->forceFill([
            'status' => Examination::STATUS_COMPLETED,
            'ended_at' => now(),
        ])->save();

        return back()->with('success', "\"{$examination->title}\" has been ended.");
    }
}
