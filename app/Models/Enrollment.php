<?php

namespace App\Models;

use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

/**
 * A student's enrollment in a subject/section handled by a proctor.
 *
 * The project has no enrollment UI yet, so records are seeded (or created by
 * an administrator) and every proctor-facing query is scoped to the proctor
 * that owns the enrollment.
 */
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    protected $connection = 'mongodb';

    protected $table = 'enrollments';

    protected $fillable = [
        'proctor_id',
        'student_id',
        'student_name',
        'student_id_number',
        'subject',
        'section',
        'year_level',
    ];

    /**
     * @return array<int, string>
     */
    public static function sectionsForProctor(string $proctorId): array
    {
        return self::where('proctor_id', $proctorId)
            ->pluck('section')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
