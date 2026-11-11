<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects email addresses that are technically RFC-valid but incomplete,
 * such as "student@gma" (no domain extension) or addresses with spaces.
 */
class ValidEmailAddress implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Let the "required"/"string" rules report missing or non-string values.
        if (! is_string($value) || $value === '') {
            return;
        }

        $pattern = '/^(?=.{1,254}$)(?=.{1,64}@)'
            .'[A-Za-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[A-Za-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*'
            .'@(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,63}$/';

        if (preg_match($pattern, $value) !== 1) {
            $fail('Enter a complete email address, for example name@example.com.');
        }
    }
}
