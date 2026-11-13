<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Examination;
use App\Models\ExaminationParticipant;
use App\Models\Incident;
use App\Models\StudentActivity;
use App\Models\StudentExamSession;
use App\Support\ExaminationPresenter;
use App\Support\UserPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProctorController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $proctor = $request->user();
        $proctorId = (string) $proctor->getKey();

        $examinations = Examination::where('proctor_id', $proctorId)->get();

        $upcoming = $examinations
            ->filter(fn (Examination $examination) => in_array($examination->status, [
                Examination::STATUS_ONGOING,
                Examination::STATUS_SCHEDULED,
            ], true))
            ->sortBy(fn (Examination $examination) => (string) $examination->scheduled_date.' '.(string) $examination->start_time)
            ->take(5)
            ->values()
            ->map(fn (Examination $examination) => $this->presentExamination($examination))
            ->all();

        $recentActivity = StudentActivity::where('proctor_id', $proctorId)
            ->orderByDesc('occurred_at')
            ->limit(8)
            ->get()
            ->map(fn (StudentActivity $activity) => $this->presentActivity($activity))
            ->values()
            ->all();

        return response()->json([
            'user' => UserPresenter::toArray($proctor),
            'stats' => [
                'active_examinations' => $examinations->where('status', Examination::STATUS_ONGOING)->count(),
                'reported_incidents' => Incident::where('proctor_id', $proctorId)->count(),
                'pending_incidents' => Incident::where('proctor_id', $proctorId)
                    ->where('status', Incident::STATUS_PENDING)
                    ->count(),
                'active_students' => Enrollment::where('proctor_id', $proctorId)
                    ->pluck('student_id')
                    ->unique()
                    ->count(),
            ],
            'upcoming_examinations' => $upcoming,
            'total_examinations' => $examinations->count(),
            'recent_activity' => $recentActivity,
        ]);
    }

    public function examinations(Request $request): JsonResponse
    {
        $proctorId = (string) $request->user()->getKey();

        $enrollmentCounts = Enrollment::where('proctor_id', $proctorId)
            ->get()
            ->groupBy('section')
            ->map(fn (Collection $enrollments) => $enrollments->pluck('student_id')->unique()->count());

        $examinations = Examination::where('proctor_id', $proctorId)
            ->orderByDesc('scheduled_date')
            ->orderByDesc('start_time')
            ->get();

        $participants = ExaminationParticipant::where('proctor_id', $proctorId)->get()->groupBy('examination_id');
        $sessions = StudentExamSession::where('proctor_id', $proctorId)->get()->groupBy('examination_id');

        $rows = $examinations->map(function (Examination $examination) use ($enrollmentCounts, $participants, $sessions) {
            $id = (string) $examination->getKey();
            $examSessions = $sessions->get($id, collect());

            return [
                ...$this->presentExamination($examination),
                'enrolled_students' => $enrollmentCounts->get($examination->section, 0),
                'joined_students' => $participants->get($id, collect())->count(),
                'in_progress_students' => $examSessions->where('status', StudentExamSession::STATUS_IN_PROGRESS)->count(),
                'finished_students' => $examSessions->where('status', StudentExamSession::STATUS_COMPLETED)->count(),
            ];
        })->values();

        return response()->json([
            'examinations' => $rows,
            'assignments' => $this->assignmentPairs($proctorId),
        ]);
    }

    public function storeExamination(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'subject' => ['required', 'string', 'min:2', 'max:120'],
            'section' => ['required', 'string', 'min:1', 'max:80'],
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ], [
            'title.required' => 'Enter an examination title.',
            'subject.required' => 'Enter the course or subject.',
            'section.required' => 'Enter the section that will take this examination.',
            'scheduled_date.required' => 'Choose the examination date.',
            'scheduled_date.date_format' => 'Choose a valid examination date (YYYY-MM-DD).',
            'start_time.date_format' => 'Enter the start time as HH:MM.',
            'end_time.date_format' => 'Enter the end time as HH:MM.',
        ]);

        if (strcmp((string) $data['end_time'], (string) $data['start_time']) <= 0) {
            throw ValidationException::withMessages([
                'end_time' => 'The end time must be later than the start time.',
            ]);
        }

        $examination = Examination::create([
            ...$data,
            'proctor_id' => (string) $request->user()->getKey(),
            'access_code' => Examination::generateAccessCode(),
            'status' => Examination::STATUS_SCHEDULED,
        ]);

        return response()->json([
            'message' => "Examination \"{$examination->title}\" created.",
            'examination' => $this->presentExamination($examination),
        ], 201);
    }

    /**
     * Examination detail with joined students, exam-mode status, and activity.
     */
    public function showExamination(Request $request, string $examination): JsonResponse
    {
        $model = $this->findOwnedExamination((string) $request->user()->getKey(), $examination);

        if ($model === null) {
            return response()->json(['message' => 'Examination not found.'], 404);
        }

        $id = (string) $model->getKey();

        $participants = ExaminationParticipant::where('examination_id', $id)
            ->orderByDesc('joined_at')
            ->get();

        $sessions = StudentExamSession::where('examination_id', $id)
            ->get()
            ->keyBy(fn (StudentExamSession $session) => (string) $session->student_id);

        $activityCounts = StudentActivity::where('examination_id', $id)
            ->get()
            ->groupBy('student_id')
            ->map(fn (Collection $events) => $events->count());

        $students = $participants->map(function (ExaminationParticipant $participant) use ($sessions) {
            $session = $sessions->get((string) $participant->student_id);

            return [
                'student_id' => (string) $participant->student_id,
                'student_name' => $participant->student_name,
                'student_id_number' => $participant->student_id_number,
                'joined_at' => $participant->joined_at?->toIso8601String(),
                'session_status' => $session?->status, // null = joined, not started
                'started_at' => $session?->started_at?->toIso8601String(),
                'finished_at' => $session?->finished_at?->toIso8601String(),
                'last_seen_at' => $session?->last_seen_at?->toIso8601String(),
            ];
        })->values();

        $activities = StudentActivity::where('examination_id', $id)
            ->orderByDesc('occurred_at')
            ->limit(60)
            ->get()
            ->map(fn (StudentActivity $activity) => $this->presentActivity($activity))
            ->values();

        return response()->json([
            'examination' => [
                ...$this->presentExamination($model),
                'joined_students' => $students->count(),
                'in_progress_students' => $sessions->where('status', StudentExamSession::STATUS_IN_PROGRESS)->count(),
                'finished_students' => $sessions->where('status', StudentExamSession::STATUS_COMPLETED)->count(),
            ],
            'students' => $students,
            'activity' => $activities,
            'activity_counts' => $activityCounts,
            'incidents' => Incident::where('examination_id', $id)
                ->orderByDesc('reported_at')
                ->get()
                ->map(fn (Incident $incident) => $this->presentIncident($incident))
                ->values(),
        ]);
    }

    public function startExamination(Request $request, string $examination): JsonResponse
    {
        $model = $this->findOwnedExamination((string) $request->user()->getKey(), $examination);

        if ($model === null) {
            return response()->json(['message' => 'Examination not found.'], 404);
        }

        if ($model->status !== Examination::STATUS_SCHEDULED) {
            return response()->json(['message' => 'Only a scheduled examination can be started.'], 422);
        }

        $model->forceFill([
            'status' => Examination::STATUS_ONGOING,
            'started_at' => now(),
            'ended_at' => null,
        ])->save();

        return response()->json([
            'message' => "\"{$model->title}\" is now ongoing. Students can join with code {$model->access_code}.",
            'examination' => $this->presentExamination($model->fresh()),
        ]);
    }

    public function endExamination(Request $request, string $examination): JsonResponse
    {
        $model = $this->findOwnedExamination((string) $request->user()->getKey(), $examination);

        if ($model === null) {
            return response()->json(['message' => 'Examination not found.'], 404);
        }

        if ($model->status !== Examination::STATUS_ONGOING) {
            return response()->json(['message' => 'Only an ongoing examination can be ended.'], 422);
        }

        $model->forceFill([
            'status' => Examination::STATUS_COMPLETED,
            'ended_at' => now(),
        ])->save();

        return response()->json([
            'message' => "\"{$model->title}\" has been ended.",
            'examination' => $this->presentExamination($model->fresh()),
        ]);
    }

    /**
     * Incidents plus recent mobile activity for the alerts screen.
     */
    public function alerts(Request $request): JsonResponse
    {
        $proctorId = (string) $request->user()->getKey();

        $incidents = Incident::where('proctor_id', $proctorId)
            ->orderByDesc('reported_at')
            ->get();

        $examinations = Examination::where('proctor_id', $proctorId)->get()
            ->keyBy(fn (Examination $examination) => (string) $examination->getKey());

        $activity = StudentActivity::where('proctor_id', $proctorId)
            ->orderByDesc('occurred_at')
            ->limit(50)
            ->get()
            ->map(fn (StudentActivity $activity) => $this->presentActivity($activity))
            ->values();

        return response()->json([
            'incidents' => $incidents->map(fn (Incident $incident) => $this->presentIncident($incident))->values(),
            'counts' => [
                'pending' => $incidents->where('status', Incident::STATUS_PENDING)->count(),
                'reviewed' => $incidents->where('status', Incident::STATUS_REVIEWED)->count(),
                'dismissed' => $incidents->where('status', Incident::STATUS_DISMISSED)->count(),
            ],
            'activity' => $activity,
            'examinations' => $examinations->map(fn (Examination $examination) => $this->presentExamination($examination))->values(),
        ]);
    }

    public function storeIncident(Request $request): JsonResponse
    {
        $data = $request->validate([
            'examination_id' => ['required', 'string'],
            'student_id' => ['nullable', 'string'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'examination_id.required' => 'Choose the examination session this incident happened in.',
            'description.required' => 'Describe what happened.',
            'description.min' => 'Please describe the incident in at least 10 characters.',
        ]);

        $proctorId = (string) $request->user()->getKey();
        $examination = $this->findOwnedExamination($proctorId, $data['examination_id']);

        if ($examination === null) {
            throw ValidationException::withMessages([
                'examination_id' => 'Choose one of your examination sessions.',
            ]);
        }

        $enrollment = null;

        if (! empty($data['student_id'])) {
            $enrollment = Enrollment::where('proctor_id', $proctorId)
                ->where('section', $examination->section)
                ->get()
                ->first(fn (Enrollment $row) => (string) $row->getKey() === $data['student_id']);

            if ($enrollment === null) {
                throw ValidationException::withMessages([
                    'student_id' => 'That student is not enrolled in this examination section.',
                ]);
            }
        }

        $incident = Incident::create([
            'proctor_id' => $proctorId,
            'examination_id' => (string) $examination->getKey(),
            'examination_title' => $examination->title,
            'student_id' => $enrollment?->student_id ? (string) $enrollment->student_id : null,
            'student_name' => $enrollment?->student_name,
            'student_id_number' => $enrollment?->student_id_number,
            'description' => $data['description'],
            'status' => Incident::STATUS_PENDING,
            'reported_by' => $proctorId,
            'reported_at' => now(),
        ]);

        return response()->json([
            'message' => 'Incident report submitted for review.',
            'incident' => $this->presentIncident($incident),
        ], 201);
    }

    public function updateIncidentStatus(Request $request, string $incident): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:pending,reviewed,dismissed'],
        ]);

        $model = Incident::find($incident);

        if ($model === null || (string) $model->proctor_id !== (string) $request->user()->getKey()) {
            return response()->json(['message' => 'Incident not found.'], 404);
        }

        $status = $data['status'];

        $model->forceFill([
            'status' => $status,
            'reviewed_by' => $status === Incident::STATUS_PENDING ? null : (string) $request->user()->getKey(),
            'reviewed_at' => $status === Incident::STATUS_PENDING ? null : now(),
        ])->save();

        return response()->json([
            'message' => 'Incident marked as '.strtolower($model->statusLabel()).'.',
            'incident' => $this->presentIncident($model->fresh()),
        ]);
    }

    private function findOwnedExamination(string $proctorId, string $examinationId): ?Examination
    {
        $model = Examination::find($examinationId);

        if ($model === null || (string) $model->proctor_id !== $proctorId) {
            return null;
        }

        return $model;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentExamination(Examination $examination): array
    {
        return ExaminationPresenter::toArray($examination);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentActivity(StudentActivity $activity): array
    {
        return [
            'id' => (string) $activity->getKey(),
            'examination_id' => (string) $activity->examination_id,
            'examination_title' => $activity->examination_title,
            'student_id' => (string) $activity->student_id,
            'student_name' => $activity->student_name,
            'student_id_number' => $activity->student_id_number,
            'type' => $activity->type,
            'type_label' => $activity->typeLabel(),
            'detail' => $activity->detail,
            'occurred_at' => $activity->occurred_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentIncident(Incident $incident): array
    {
        return [
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
        ];
    }

    /**
     * @return array<int, array{subject: string, section: string}>
     */
    private function assignmentPairs(string $proctorId): array
    {
        $enrollments = Enrollment::where('proctor_id', $proctorId)->get(['subject', 'section']);
        $examinations = Examination::where('proctor_id', $proctorId)->get(['subject', 'section']);

        return $enrollments
            ->concat($examinations)
            ->filter(fn ($row) => filled($row->subject) && filled($row->section))
            ->map(fn ($row) => ['subject' => (string) $row->subject, 'section' => (string) $row->section])
            ->unique(fn (array $pair) => $pair['subject'].'|'.$pair['section'])
            ->sortBy(fn (array $pair) => $pair['subject'].'|'.$pair['section'])
            ->values()
            ->all();
    }
}
