<?php

namespace App\Support;

use App\Models\User;

final class UserPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(User $user): array
    {
        return [
            'id' => (string) $user->getKey(),
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'student_id_number' => $user->student_id_number,
            'college' => $user->college,
            'department' => $user->department,
            'year_level' => $user->year_level,
            'role' => $user->role,
        ];
    }
}
