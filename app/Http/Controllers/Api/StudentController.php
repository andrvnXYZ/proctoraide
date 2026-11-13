<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\JoinExaminationRequest;
use App\Models\Enrollment;
use App\Models\Examination;
use App\Models\ExaminationParticipant;
use App\Models\StudentActivity;
use App\Models\StudentExamSession;
use App\Support\ExaminationPresenter;
use App\Support\UserPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    private const MAX_CONSECUTIVE_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 60;

    /**
     * Dashboard: active session, upcoming examinations, and history.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $student = $request->user();
        $studentId = (string) $student->getKey();
        $examinations = $this->enrolledExaminations($studentId);

        $joined = ExaminationParticipant::where('student_id', $studentId)
            ->get()
            ->keyBy(fn (ExaminationParticipant $participant) => (string) $participant->examination_id);

        $sessions = StudentExamSession::where('student_id', $studentId)
            ->get()
            ->keyBy(fn (StudentExamSession $session) => (string) $session->examination_id);

        $present = fn (Examination $examination) => $this->presentExamination($examination, $joined, $sessions);

        $upcoming = $examinations
            ->filter(fn (Examination $examination) => $examination->status === Examination::STATUS_SCHEDULED)
            ->sortBy(fn (Examination $examination) => (string) $examination->scheduled_date.' '.(string) $examination->start_time)
            ->values()
            ->map($present);

        // Prefer the examination the student is currently inside, then the most
        // recently started ongoing session, so the active card stays relevant
        // when several sessions are running.
        $active = $examinations
            ->filter(fn (Examination $examination) => $examination->status === Examination::STATUS_ONGOING)
            ->sortByDesc(function (Examination $examination) use ($sessions) {
                $session = $sessions->get((string) $examination->getKey());

                return [
                    $session?->isInProgress() ? 1 : 0,
                    $examination->started_at?->getTimestamp() ?? 0,
                ];
            })
            ->first();

        $history = $examinations
            ->filter(fn (Examination $examination) => $examination->status === Examination::STATUS_COMPLETED
                || $joined->has((string) $examination->getKey()))
            ->sortByDesc(fn (Examination $examination) => (string) $examination->scheduled_date.' '.(string) $examination->start_time)
            ->values()
            ->map($present);

        return response()->json([
            'user' => UserPresenter::toArray($student),
            'stats' => [
                'upcoming' => $upcoming->count(),
                'active' => $active !== null ? 1 : 0,
                'completed' => $examinations->where('status', Examination::STATUS_COMPLETED)->count(),
            ],
            'active_examination' => $active ? $present($active) : null,
            'upcoming' => $upcoming,
            'history' => $history,
        ]);
    }

    /**
     * All examinations the student has joined or finished.
     */
    public function history(Request $request): JsonResponse
    {
        $studentId = (string) $request->user()->getKey();
        $examinations = $this->enrolledExaminations($studentId);

        $joined = ExaminationParticipant::where('student_id', $studentId)->get()
            ->keyBy(fn (ExaminationParticipant $participant) => (string) $participant->examination_id);
        $sessions = StudentExamSession::where('student_id', $studentId)->get()
            ->keyBy(fn (StudentExamSession $session) => (string) $session->examination_id);

        $history = $examinations
            ->filter(fn (Examination $examination) => $joined->has((string) $examination->getKey()))
            ->sortByDesc(fn (Examination $examination) => (string) $examination->scheduled_date.' '.(string) $examination->start_time)
            ->values()
            ->map(fn (Examination $examination) => $this->presentExamination($examination, $joined, $sessions));

        return response()->json(['history' => $history]);
    }

    /**
     * Join an ongoing examination using its access code.
     */
    public function join(JoinExaminationRequest $request): JsonResponse
    {
        $user = $request->user();
        $userId = (string) $user->getKey();

        $lockedFor = $this->lockoutSeconds($userId);

        if ($lockedFor > 0) {
            return $this->fail("Too many incorrect attempts. Please try again in {$lockedFor} seconds.");
        }

        $examination = Examination::where('access_code', (string) $request->validated('access_code'))->first();

        if ($examination === null) {
            return $this->registerFailedAttempt($userId);
        }

        Cache::forget($this->attemptsKey($userId));

        $enrolled = Enrollment::where('student_id', $userId)
            ->where('subject', $examination->subject)
            ->where('section', $examination->section)
            ->exists();

        if (! $enrolled) {
            return $this->fail('You are not enrolled in this examination.');
        }

        if ($examination->isCompleted()) {
            return $this->fail('This examination has already ended.');
        }

        if (! $examination->isOngoing()) {
            return $this->fail('This examination has not started yet. Please wait for your proctor.');
        }

        $examinationId = (string) $examination->getKey();

        if (ExaminationParticipant::hasJoined($examinationId, $userId)) {
            return $this->fail('You have already joined this examination.');
        }

        ExaminationParticipant::create([
            'examination_id' => $examinationId,
            'proctor_id' => (string) $examination->proctor_id,
            'student_id' => $userId,
            'student_name' => $user->name,
            'student_id_number' => $user->student_id_number,
            'examination_title' => $examination->title,
            'subject' => $examination->subject,
            'section' => $examination->section,
            'joined_at' => now(),
        ]);

        return response()->json([
            'message' => 'Successfully joined the examination!',
            'examination' => $this->presentForStudent($examination, $userId),
        ]);
    }

    public function show(Request $request, string $examination): JsonResponse
    {
        $model = $this->findEnrolledExamination((string) $request->user()->getKey(), $examination);

        if ($model === null) {
            return response()->json(['message' => 'Examination not found.'], 404);
        }

        return response()->json([
            'examination' => $this->presentForStudent($model, (string) $request->user()->getKey()),
            'activities' => $this->activitiesFor($model, (string) $request->user()->getKey()),
        ]);
    }

    /**
     * Enter exam mode: mark the student's session as started.
     */
    public function start(Request $request, string $examination): JsonResponse
    {
        $user = $request->user();
        $userId = (string) $user->getKey();
        $model = $this->findEnrolledExamination($userId, $examination);

        if ($model === null) {
            return response()->json(['message' => 'Examination not found.'], 404);
        }

        if (! ExaminationParticipant::hasJoined((string) $model->getKey(), $userId)) {
            return response()->json(['message' => 'Join the examination with its access code first.'], 422);
        }

        if (! $model->isOngoing()) {
            return response()->json(['message' => $model->isCompleted()
                ? 'This examination has already ended.'
                : 'This examination has not started yet.'], 422);
        }

        $session = StudentExamSession::where('examination_id', (string) $model->getKey())
            ->where('student_id', $userId)
            ->first();

        if ($session?->isInProgress() === false) {
            return response()->json(['message' => 'You have already finished this examination.'], 422);
        }

        $session = StudentExamSession::updateOrCreate(
            [
                'examination_id' => (string) $model->getKey(),
                'student_id' => $userId,
            ],
            [
                'proctor_id' => (string) $model->proctor_id,
                'student_name' => $user->name,
                'student_id_number' => $user->student_id_number,
                'status' => StudentExamSession::STATUS_IN_PROGRESS,
                'started_at' => $session?->started_at ?? now(),
                'last_seen_at' => now(),
            ],
        );

        $this->recordActivity($model, $user, StudentActivity::TYPE_EXAM_STARTED, 'Exam mode started on mobile.');

        return response()->json([
            'message' => 'Exam started. Stay in the app until you finish.',
            'session' => $this->sessionPayload($session),
            'examination' => $this->presentForStudent($model, $userId),
        ]);
    }

    public function finish(Request $request, string $examination): JsonResponse
    {
        $user = $request->user();
        $userId = (string) $user->getKey();
        $model = $this->findEnrolledExamination($userId, $examination);

        if ($model === null) {
            return response()->json(['message' => 'Examination not found.'], 404);
        }

        $session = StudentExamSession::where('examination_id', (string) $model->getKey())
            ->where('student_id', $userId)
            ->first();

        if ($session === null || ! $session->isInProgress()) {
            return response()->json(['message' => 'You have not started this examination.'], 422);
        }

        $session->forceFill([
            'status' => StudentExamSession::STATUS_COMPLETED,
            'finished_at' => now(),
            'last_seen_at' => now(),
        ])->save();

        $this->recordActivity($model, $user, StudentActivity::TYPE_EXAM_FINISHED, 'Examination finished on mobile.');

        return response()->json([
            'message' => 'Examination finished. Your proctor has been notified.',
            'session' => $this->sessionPayload($session->fresh()),
        ]);
    }

    /**
     * Record a supported monitoring event reported by the app.
     */
    public function activity(Request $request, string $examination): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', StudentActivity::TYPES)],
            'detail' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $userId = (string) $user->getKey();
        $model = $this->findEnrolledExamination($userId, $examination);

        if ($model === null) {
            return response()->json(['message' => 'Examination not found.'], 404);
        }

        if (! ExaminationParticipant::hasJoined((string) $model->getKey(), $userId)) {
            return response()->json(['message' => 'You have not joined this examination.'], 422);
        }

        $event = $this->recordActivity($model, $user, $data['type'], $data['detail'] ?? null);

        StudentExamSession::where('examination_id', (string) $model->getKey())
            ->where('student_id', $userId)
            ->update(['last_seen_at' => now()]);

        return response()->json([
            'message' => 'Activity recorded.',
            'event' => [
                'id' => (string) $event->getKey(),
                'type' => $event->type,
                'detail' => $event->detail,
                'occurred_at' => $event->occurred_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * @return Collection<int, Examination>
     */
    private function enrolledExaminations(string $studentId): Collection
    {
        $pairs = Enrollment::where('student_id', $studentId)
            ->get(['subject', 'section'])
            ->map(fn (Enrollment $enrollment) => $enrollment->subject.'|'.$enrollment->section)
            ->unique()
            ->values();

        return Examination::all()
            ->filter(fn (Examination $examination) => $pairs->contains($examination->subject.'|'.$examination->section))
            ->values();
    }

    private function findEnrolledExamination(string $studentId, string $examinationId): ?Examination
    {
        return $this->enrolledExaminations($studentId)
            ->first(fn (Examination $examination) => (string) $examination->getKey() === $examinationId);
    }

    private function presentForStudent(Examination $examination, string $studentId): array
    {
        $joined = ExaminationParticipant::where('student_id', $studentId)->get()
            ->keyBy(fn (ExaminationParticipant $participant) => (string) $participant->examination_id);
        $sessions = StudentExamSession::where('student_id', $studentId)->get()
            ->keyBy(fn (StudentExamSession $session) => (string) $session->examination_id);

        return $this->presentExamination($examination, $joined, $sessions);
    }

    /**
     * @param  Collection<string, ExaminationParticipant>  $joined
     * @param  Collection<string, StudentExamSession>  $sessions
     * @return array<string, mixed>
     */
    private function presentExamination(Examination $examination, Collection $joined, Collection $sessions): array
    {
        $id = (string) $examination->getKey();
        $participant = $joined->get($id);
        $session = $sessions->get($id);

        return [
            ...ExaminationPresenter::toArray($examination),
            'joined' => $participant !== null,
            'joined_at' => $participant?->joined_at?->toIso8601String(),
            'session' => $this->sessionPayload($session),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sessionPayload(?StudentExamSession $session): ?array
    {
        if ($session === null) {
            return null;
        }

        return [
            'id' => (string) $session->getKey(),
            'status' => $session->status,
            'started_at' => $session->started_at?->toIso8601String(),
            'finished_at' => $session->finished_at?->toIso8601String(),
            'last_seen_at' => $session->last_seen_at?->toIso8601String(),
        ];
    }

    private function recordActivity(Examination $examination, $user, string $type, ?string $detail): StudentActivity
    {
        return StudentActivity::create([
            'examination_id' => (string) $examination->getKey(),
            'examination_title' => $examination->title,
            'proctor_id' => (string) $examination->proctor_id,
            'student_id' => (string) $user->getKey(),
            'student_name' => $user->name,
            'student_id_number' => $user->student_id_number,
            'type' => $type,
            'detail' => $detail,
            'occurred_at' => now(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function activitiesFor(Examination $examination, string $studentId): array
    {
        return StudentActivity::where('examination_id', (string) $examination->getKey())
            ->where('student_id', $studentId)
            ->orderByDesc('occurred_at')
            ->limit(40)
            ->get()
            ->map(fn (StudentActivity $activity) => [
                'id' => (string) $activity->getKey(),
                'type' => $activity->type,
                'type_label' => $activity->typeLabel(),
                'detail' => $activity->detail,
                'occurred_at' => $activity->occurred_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function registerFailedAttempt(string $userId): JsonResponse
    {
        $attempts = (int) Cache::get($this->attemptsKey($userId), 0) + 1;

        if ($attempts >= self::MAX_CONSECUTIVE_ATTEMPTS) {
            Cache::forget($this->attemptsKey($userId));
            Cache::put(
                $this->lockoutKey($userId),
                now()->addSeconds(self::LOCKOUT_SECONDS)->timestamp,
                self::LOCKOUT_SECONDS,
            );

            return $this->fail('Too many incorrect attempts. Please try again in '.self::LOCKOUT_SECONDS.' seconds.');
        }

        Cache::put($this->attemptsKey($userId), $attempts, now()->addMinutes(10));

        return $this->fail('Invalid examination access code.');
    }

    private function lockoutSeconds(string $userId): int
    {
        $expiresAt = Cache::get($this->lockoutKey($userId));

        if ($expiresAt === null) {
            return 0;
        }

        return max(1, (int) $expiresAt - now()->timestamp);
    }

    private function fail(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => ['access_code' => [$message]],
        ], 422);
    }

    private function attemptsKey(string $userId): string
    {
        return 'api-exam-join:attempts:'.$userId;
    }

    private function lockoutKey(string $userId): string
    {
        return 'api-exam-join:lockout:'.$userId;
    }
}
