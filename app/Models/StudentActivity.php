<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * An event reported by the mobile app while a student is in exam mode.
 *
 * These are the only events Expo/React Native can reliably observe: whether
 * the app is in the foreground or background, and examination start/finish.
 * The app does not claim to block other applications or monitor the browser.
 */
class StudentActivity extends Model
{
    public const TYPE_EXAM_STARTED = 'exam_started';

    public const TYPE_EXAM_FINISHED = 'exam_finished';

    public const TYPE_APP_BACKGROUND = 'app_background';

    public const TYPE_APP_FOREGROUND = 'app_foreground';

    public const TYPE_EXIT_ATTEMPT = 'exit_attempt';

    public const TYPE_DEVICE_CHECK = 'device_check';

    public const TYPES = [
        self::TYPE_EXAM_STARTED,
        self::TYPE_EXAM_FINISHED,
        self::TYPE_APP_BACKGROUND,
        self::TYPE_APP_FOREGROUND,
        self::TYPE_EXIT_ATTEMPT,
        self::TYPE_DEVICE_CHECK,
    ];

    protected $connection = 'mongodb';

    protected $table = 'student_activities';

    protected $fillable = [
        'examination_id',
        'examination_title',
        'proctor_id',
        'student_id',
        'student_name',
        'student_id_number',
        'type',
        'detail',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_EXAM_STARTED => 'Started examination',
            self::TYPE_EXAM_FINISHED => 'Finished examination',
            self::TYPE_APP_BACKGROUND => 'App moved to background',
            self::TYPE_APP_FOREGROUND => 'Returned to app',
            self::TYPE_EXIT_ATTEMPT => 'Attempted to leave exam mode',
            self::TYPE_DEVICE_CHECK => 'Device readiness check',
            default => ucfirst(str_replace('_', ' ', (string) $this->type)),
        };
    }
}
