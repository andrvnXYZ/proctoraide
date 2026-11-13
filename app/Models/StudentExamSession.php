<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * A student's exam-mode run for one examination session.
 *
 * Joining (ExaminationParticipant) records entry via an access code; this
 * model records when the student actually started and finished the exam in
 * the mobile app.
 */
class StudentExamSession extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    protected $connection = 'mongodb';

    protected $table = 'student_exam_sessions';

    protected $fillable = [
        'examination_id',
        'proctor_id',
        'student_id',
        'student_name',
        'student_id_number',
        'status',
        'started_at',
        'finished_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }
}
