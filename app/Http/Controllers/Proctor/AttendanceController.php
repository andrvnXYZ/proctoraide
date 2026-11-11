<?php

namespace App\Http\Controllers\Proctor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Proctor\Concerns\AuthorizesExamination;
use App\Http\Requests\Proctor\UpdateAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\Examination;
use App\Support\AttendanceSummary;
use App\Support\ExaminationPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    use AuthorizesExamination;

    public function index(Request $request): Response
    {
        $proctorId = (string) $request->user()->getKey();

        $examinations = Examination::where('proctor_id', $proctorId)
            ->orderByDesc('scheduled_date')
            ->orderByDesc('start_time')
            ->get();

        $selected = null;

        if ($examinations->isNotEmpty()) {
            $requestedId = $request->query('examination');

            $selected = $requestedId
                ? $examinations->first(fn (Examination $examination) => (string) $examination->getKey() === $requestedId)
                : null;

            $selected ??= $examinations->firstWhere('status', Examination::STATUS_ONGOING)
                ?? $examinations->first();
        }

        return Inertia::render('proctor/attendance/index', [
            'examinations' => $examinations
                ->map(fn (Examination $examination) => ExaminationPresenter::toArray($examination))
                ->values(),
            'selectedExamination' => $selected ? ExaminationPresenter::toArray($selected) : null,
            'attendance' => $selected ? AttendanceSummary::forExamination($selected, $proctorId) : null,
            'statusOptions' => [
                ['value' => AttendanceRecord::STATUS_PRESENT, 'label' => 'Present'],
                ['value' => AttendanceRecord::STATUS_LATE, 'label' => 'Late'],
                ['value' => AttendanceRecord::STATUS_ABSENT, 'label' => 'Absent'],
                ['value' => AttendanceRecord::STATUS_EXCUSED, 'label' => 'Excused'],
                ['value' => 'not_checked', 'label' => 'Not yet checked'],
            ],
        ]);
    }

    public function update(UpdateAttendanceRequest $request, Examination $examination): RedirectResponse
    {
        $this->authorizeExamination($request, $examination);

        $proctorId = (string) $request->user()->getKey();

        $enrollments = Enrollment::where('proctor_id', $proctorId)
            ->where('section', $examination->section)
            ->get()
            ->keyBy(fn (Enrollment $enrollment) => (string) $enrollment->getKey());

        $statuses = $request->validated('statuses');

        $unknown = array_diff(array_keys($statuses), $enrollments->keys()->all());

        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'statuses' => 'That student does not belong to this examination section.',
            ]);
        }

        foreach ($statuses as $enrollmentId => $status) {
            $enrollment = $enrollments->get($enrollmentId);

            if ($status === 'not_checked') {
                // Clearing a status removes the record instead of storing a
                // meaningful value, keeping "not yet checked" the default.
                AttendanceRecord::where('examination_id', (string) $examination->getKey())
                    ->where('enrollment_id', $enrollmentId)
                    ->delete();

                continue;
            }

            AttendanceRecord::updateOrCreate(
                [
                    'examination_id' => (string) $examination->getKey(),
                    'enrollment_id' => $enrollmentId,
                ],
                [
                    'proctor_id' => $proctorId,
                    'student_id' => (string) $enrollment->student_id,
                    'student_name' => $enrollment->student_name,
                    'student_id_number' => $enrollment->student_id_number,
                    'status' => $status,
                    'recorded_by' => $proctorId,
                    'recorded_at' => now(),
                ],
            );
        }

        return redirect()
            ->route('proctor.attendance.index', ['examination' => (string) $examination->getKey()])
            ->with('success', 'Attendance saved.');
    }
}
