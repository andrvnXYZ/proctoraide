<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\JoinExaminationRequest;
use App\Models\Enrollment;
use App\Models\Examination;
use App\Models\ExaminationParticipant;
use App\Support\ExaminationPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Records a student's join of an ongoing examination session.
 *
 * Joining never marks attendance and never starts or ends the session; those
 * remain proctor-only actions (FR-011, FR-016 to FR-021).
 */
class JoinExaminationController extends Controller
{
    private const MAX_CONSECUTIVE_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 60;

    public function __invoke(JoinExaminationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $userId = (string) $user->getKey();

        $lockedFor = $this->lockoutSeconds($userId);

        if ($lockedFor > 0) {
            return $this->fail("Too many incorrect attempts. Please try again in {$lockedFor} seconds.");
        }

        $code = (string) $request->validated('access_code');

        $examination = Examination::where('access_code', $code)->first();

        if ($examination === null) {
            return $this->registerFailedAttempt($userId);
        }

        // A correct code ends the current run of incorrect attempts.
        Cache::forget($this->attemptsKey($userId));

        // The student id always comes from the authenticated user, never the request.
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
            return $this->fail('This examination has not started yet.');
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

        return redirect()->back()
            ->with('success', 'Successfully joined the examination!')
            ->with('joined_examination', [
                'title' => $examination->title,
                'subject' => $examination->subject,
                'section' => $examination->section,
                'schedule' => ExaminationPresenter::toArray($examination)['schedule'],
                'status' => $examination->status,
                'status_label' => $examination->statusLabel(),
                'joined_at' => now()->toIso8601String(),
            ]);
    }

    private function registerFailedAttempt(string $userId): RedirectResponse
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

    private function fail(string $message): RedirectResponse
    {
        return redirect()->back()->withErrors(['access_code' => $message]);
    }

    private function attemptsKey(string $userId): string
    {
        return 'exam-join:attempts:'.$userId;
    }

    private function lockoutKey(string $userId): string
    {
        return 'exam-join:lockout:'.$userId;
    }
}
