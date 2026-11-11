<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * A student's successful join of an examination session.
 *
 * Joining is recorded separately from proctor-confirmed attendance
 * (AttendanceRecord) and from the session's start/end state (Examination),
 * as required by FR-011.
 */
class ExaminationParticipant extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'examination_participants';

    protected $fillable = [
        'examination_id',
        'proctor_id',
        'student_id',
        'student_name',
        'student_id_number',
        'examination_title',
        'subject',
        'section',
        'joined_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public static function hasJoined(string $examinationId, string $studentId): bool
    {
        return self::where('examination_id', $examinationId)
            ->where('student_id', $studentId)
            ->exists();
    }
}
