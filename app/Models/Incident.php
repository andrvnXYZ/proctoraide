<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Incident extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_DISMISSED = 'dismissed';

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REVIEWED,
        self::STATUS_DISMISSED,
    ];

    protected $connection = 'mongodb';

    protected $table = 'incidents';

    protected $fillable = [
        'proctor_id',
        'examination_id',
        'examination_title',
        'student_id',
        'student_name',
        'student_id_number',
        'description',
        'status',
        'reported_by',
        'reported_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status ?? self::STATUS_PENDING);
    }
}
