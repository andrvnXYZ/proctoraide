<?php

namespace App\Models;

use Database\Factories\ExaminationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

class Examination extends Model
{
    /** @use HasFactory<ExaminationFactory> */
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_COMPLETED = 'completed';

    /**
     * Characters used for access codes. Ambiguous characters (0/O, 1/I) are
     * left out so a code can be read aloud to students reliably.
     */
    private const ACCESS_CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    protected $connection = 'mongodb';

    protected $table = 'examinations';

    protected $fillable = [
        'proctor_id',
        'title',
        'subject',
        'section',
        'scheduled_date',
        'start_time',
        'end_time',
        'access_code',
        'status',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Generate an access code that is not used by any existing examination.
     */
    public static function generateAccessCode(): string
    {
        do {
            $code = '';

            for ($i = 0; $i < 6; $i++) {
                $code .= self::ACCESS_CODE_ALPHABET[random_int(0, strlen(self::ACCESS_CODE_ALPHABET) - 1)];
            }
        } while (self::where('access_code', $code)->exists());

        return $code;
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'examination_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'examination_id');
    }

    public function isOngoing(): bool
    {
        return $this->status === self::STATUS_ONGOING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ONGOING => 'Ongoing',
            self::STATUS_COMPLETED => 'Completed',
            default => 'Upcoming',
        };
    }
}
