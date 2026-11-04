<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_STUDENT = 'student';

    public const ROLE_PROCTOR = 'proctor';

    public const ROLE_ADMIN = 'admin';

    /**
     * Roles that may be chosen through public registration.
     *
     * @var list<string>
     */
    public const PUBLIC_ROLES = [
        self::ROLE_STUDENT,
        self::ROLE_PROCTOR,
    ];

    /**
     * The database connection used by the model.
     *
     * @var string
     */
    protected $connection = 'mongodb';

    /**
     * The MongoDB collection used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'birthday',
        'student_id_number',
        'college',
        'department',
        'year_level',
        'email',
        'password',
        'role',
        'terms_accepted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'terms_accepted_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isProctor(): bool
    {
        return $this->role === self::ROLE_PROCTOR;
    }

    /**
     * Get the route name of the dashboard that belongs to this user's role.
     */
    public function dashboardRouteName(): ?string
    {
        return match ($this->role) {
            self::ROLE_STUDENT => 'student.dashboard',
            self::ROLE_PROCTOR => 'proctor.dashboard',
            default => null,
        };
    }
}
