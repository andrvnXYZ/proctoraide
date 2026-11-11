<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AttendanceRecord extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_LATE = 'late';

    public const STATUS_EXCUSED = 'excused';

    /**
     * Every status a proctor may assign. There is deliberately no default
     * record: a student stays "not yet checked" until the proctor saves one.
     *
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_PRESENT,
        self::STATUS_ABSENT,
        self::STATUS_LATE,
        self::STATUS_EXCUSED,
    ];

    protected $connection = 'mongodb';

    protected $table = 'attendance_records';

    protected $fillable = [
        'proctor_id',
        'examination_id',
        'enrollment_id',
        'student_id',
        'student_name',
        'student_id_number',
        'status',
        'recorded_by',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PRESENT => 'Present',
            self::STATUS_ABSENT => 'Absent',
            self::STATUS_LATE => 'Late',
            self::STATUS_EXCUSED => 'Excused',
            default => 'Not yet checked',
        };
    }
}
