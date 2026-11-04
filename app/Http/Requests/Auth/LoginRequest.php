<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * The request field that carried the login identifier.
     */
    private string $identifierField = 'identifier';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the login identifier and role before validation.
     */
    protected function prepareForValidation(): void
    {
        $identifier = null;

        if ($this->filled('identifier')) {
            $this->identifierField = 'identifier';
            $identifier = $this->input('identifier');
        } elseif ($this->filled('login')) {
            $this->identifierField = 'login';
            $identifier = $this->input('login');
        } elseif ($this->filled('email')) {
            $this->identifierField = 'email';
            $identifier = $this->input('email');
        } elseif ($this->filled('student_id_number')) {
            $this->identifierField = 'student_id_number';
            $identifier = $this->input('student_id_number');
        }

        if (is_string($identifier)) {
            $identifier = trim($identifier);

            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $identifier = Str::lower($identifier);
            }
        }

        $data = [
            'identifier' => $identifier,
        ];

        if (is_string($this->input('role'))) {
            $data['role'] = Str::lower(trim($this->input('role')));
        }

        $this->merge($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'role' => ['required', 'string', Rule::in(User::PUBLIC_ROLES)],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identifier = (string) $this->string('identifier');
        $role = (string) $this->string('role');

        $user = User::query()
            ->where(
                filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'student_id_number',
                $identifier,
            )
            ->first();

        $passwordIsValid = $user !== null
            && Hash::check((string) $this->input('password'), (string) $user->getAuthPassword());

        $roleIsValid = $user !== null
            && hash_equals(Str::lower((string) $user->role), $role);

        if (! $user || ! $passwordIsValid || ! $roleIsValid) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                $this->identifierField => __('auth.failed'),
            ]);
        }

        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            $this->identifierField => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower((string) $this->input('identifier')).'|'.$this->ip(),
        );
    }
}
