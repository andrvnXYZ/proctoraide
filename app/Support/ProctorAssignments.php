<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\Examination;

/**
 * The subject/section pairs a proctor is responsible for.
 *
 * The project has no separate subject or section model: a proctor's teaching
 * assignments are the pairs that already appear on their enrollments and
 * examination sessions. Those are the only pairs a proctor may enroll into,
 * which keeps the ownership check aligned with existing data.
 */
final class ProctorAssignments
{
    /**
     * @return array<int, array{subject: string, section: string}>
     */
    public static function pairs(string $proctorId): array
    {
        $enrollments = Enrollment::where('proctor_id', $proctorId)->get(['subject', 'section']);
        $examinations = Examination::where('proctor_id', $proctorId)->get(['subject', 'section']);

        return $enrollments
            ->concat($examinations)
            ->filter(fn ($record) => filled($record->subject) && filled($record->section))
            ->map(fn ($record) => [
                'subject' => (string) $record->subject,
                'section' => (string) $record->section,
            ])
            ->unique(fn (array $pair) => $pair['subject'].'|'.$pair['section'])
            ->sortBy(fn (array $pair) => $pair['subject'].'|'.$pair['section'])
            ->values()
            ->all();
    }

    public static function owns(string $proctorId, string $subject, string $section): bool
    {
        foreach (self::pairs($proctorId) as $pair) {
            if ($pair['subject'] === $subject && $pair['section'] === $section) {
                return true;
            }
        }

        return false;
    }
}
