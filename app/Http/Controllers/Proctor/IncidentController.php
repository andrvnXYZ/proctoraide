<?php

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Proctor\StoreIncidentRequest;
use App\Http\Requests\Proctor\UpdateIncidentStatusRequest;
use App\Models\Enrollment;
use App\Models\Examination;
use App\Models\Incident;
use App\Support\ExaminationPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    public function index(Request $request): Response
    {
        $proctorId = (string) $request->user()->getKey();

        $enrollmentsBySection = Enrollment::where('proctor_id', $proctorId)
            ->orderBy('student_name')
            ->get()
            ->groupBy('section');

        $examinations = Examination::where('proctor_id', $proctorId)
            ->orderByDesc('scheduled_date')
            ->orderByDesc('start_time')
            ->get()
            ->map(fn (Examination $examination) => [
                ...ExaminationPresenter::toArray($examination),
                'students' => ($enrollmentsBySection->get($examination->section) ?? collect())
                    ->map(fn (Enrollment $enrollment) => [
                        'id' => (string) $enrollment->getKey(),
                        'name' => $enrollment->student_name,
                        'student_id_number' => $enrollment->student_id_number,
                    ])
                    ->values(),
            ])
            ->values();

        $incidents = Incident::where('proctor_id', $proctorId)
            ->orderByDesc('reported_at')
            ->get()
            ->map(fn (Incident $incident) => [
                'id' => (string) $incident->getKey(),
                'examination_id' => (string) $incident->examination_id,
                'examination_title' => $incident->examination_title,
                'student_name' => $incident->student_name,
                'student_id_number' => $incident->student_id_number,
                'description' => $incident->description,
                'status' => $incident->status,
                'status_label' => $incident->statusLabel(),
                'reported_at' => $incident->reported_at?->toIso8601String(),
                'reviewed_at' => $incident->reviewed_at?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('proctor/incidents/index', [
            'incidents' => $incidents,
            'examinations' => $examinations,
            'selectedExaminationId' => $request->query('examination'),
            'counts' => [
                'pending' => $incidents->where('status', Incident::STATUS_PENDING)->count(),
                'reviewed' => $incidents->where('status', Incident::STATUS_REVIEWED)->count(),
                'dismissed' => $incidents->where('status', Incident::STATUS_DISMISSED)->count(),
            ],
        ]);
    }

    public function store(StoreIncidentRequest $request): RedirectResponse
    {
        $proctorId = (string) $request->user()->getKey();

        $examination = Examination::where('proctor_id', $proctorId)
            ->get()
            ->first(fn (Examination $examination) => (string) $examination->getKey() === $request->validated('examination_id'));

        if ($examination === null) {
            throw ValidationException::withMessages([
                'examination_id' => 'Choose one of your examination sessions.',
            ]);
        }

        $enrollment = null;

        if ($request->filled('student_id')) {
            $enrollment = Enrollment::where('proctor_id', $proctorId)
                ->where('section', $examination->section)
                ->get()
                ->first(fn (Enrollment $enrollment) => (string) $enrollment->getKey() === $request->validated('student_id'));

            if ($enrollment === null) {
                throw ValidationException::withMessages([
                    'student_id' => 'That student is not enrolled in this examination section.',
                ]);
            }
        }

        Incident::create([
            'proctor_id' => $proctorId,
            'examination_id' => (string) $examination->getKey(),
            'examination_title' => $examination->title,
            'student_id' => $enrollment?->student_id ? (string) $enrollment->student_id : null,
            'student_name' => $enrollment?->student_name,
            'student_id_number' => $enrollment?->student_id_number,
            'description' => $request->validated('description'),
            'status' => Incident::STATUS_PENDING,
            'reported_by' => $proctorId,
            'reported_at' => now(),
        ]);

        return redirect()
            ->route('proctor.incidents.index')
            ->with('success', 'Incident report submitted for review.');
    }

    public function updateStatus(UpdateIncidentStatusRequest $request, Incident $incident): RedirectResponse
    {
        abort_unless(
            (string) $incident->proctor_id === (string) $request->user()?->getKey(),
            403,
            'You are not authorized to update this incident.',
        );

        $status = $request->validated('status');

        $incident->forceFill([
            'status' => $status,
            'reviewed_by' => $status === Incident::STATUS_PENDING ? null : (string) $request->user()->getKey(),
            'reviewed_at' => $status === Incident::STATUS_PENDING ? null : now(),
        ])->save();

        return back()->with('success', 'Incident marked as '.strtolower($incident->statusLabel()).'.');
    }
}
