<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * A bearer token issued to a signed-in mobile device.
 *
 * Only the SHA-256 hash of the token is stored; the plain value is returned to
 * the device once at login and never persisted in readable form.
 */
class ApiToken extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'api_tokens';

    protected $fillable = [
        'user_id',
        'name',
        'token',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
