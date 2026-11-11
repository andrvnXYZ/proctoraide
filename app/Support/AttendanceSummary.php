<?php

namespace App\Support;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\Examination;

/**
 * Builds the attendance roster and counts for a single examination.
 *
 * Students without an attendance document stay "not yet checked"; nothing is
 * written automatically, so an unsaved roster is never reported as absent.
 */
final class AttendanceSummary
{
    /**
     * @return array{rows: array<int, array<string, mixed>>, counts: array<string, int>, total: int, recorded: int}
     */
    public static function forExamination(Examination $examination, string $proctorId): array
    {
        $enrollments = Enrollment::where('proctor_id', $proctorId)
            ->where('section', $examination->section)
            ->orderBy('student_name')
            ->get();

        $records = AttendanceRecord::where('examination_id', (string) $examination->getKey())
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => (string) $record->enrollment_id);

        $counts = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
            'not_checked' => 0,
        ];

        $rows = $enrollments->map(function (Enrollment $enrollment) use ($records, &$counts) {
            $record = $records->get((string) $enrollment->getKey());
            $status = $record?->status ?? 'not_checked';

            if (! array_key_exists($status, $counts)) {
                $status = 'not_checked';
            }

            $counts[$status]++;

            return [
                'enrollment_id' => (string) $enrollment->getKey(),
                'student_id' => (string) $enrollment->student_id,
                'student_name' => $enrollment->student_name,
                'student_id_number' => $enrollment->student_id_number,
                'year_level' => $enrollment->year_level,
                'status' => $status,
                'status_label' => $record?->statusLabel() ?? 'Not yet checked',
                'recorded_at' => $record?->recorded_at?->toIso8601String(),
            ];
        })->values()->all();

        return [
            'rows' => $rows,
            'counts' => $counts,
            'total' => $enrollments->count(),
            'recorded' => $enrollments->count() - $counts['not_checked'],
        ];
    }
}
