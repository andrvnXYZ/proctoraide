<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use App\Support\UserPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    /** Seconds before a locked-out identifier may try again. */
    private const DECAY_SECONDS = 300;

    /**
     * Issue a device token for a student or proctor account.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'role' => ['required', 'string', 'in:student,proctor'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $identifier = trim((string) $data['identifier']);
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

        if ($isEmail) {
            $identifier = Str::lower($identifier);
        }

        $throttleKey = 'api-login:'.Str::lower($identifier).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'identifier' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $user = User::query()
            ->where($isEmail ? 'email' : 'student_id_number', $identifier)
            ->first();

        $passwordIsValid = $user !== null
            && Hash::check((string) $data['password'], (string) $user->getAuthPassword());

        $roleIsValid = $user !== null
            && hash_equals(Str::lower((string) $user->role), (string) $data['role']);

        if (! $user || ! $passwordIsValid || ! $roleIsValid) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'identifier' => 'These credentials do not match our records for the selected role.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $plainToken = Str::random(64);

        ApiToken::create([
            'user_id' => (string) $user->getKey(),
            'name' => $data['device_name'] ?? 'ProctorAide mobile',
            'token' => ApiToken::hashToken($plainToken),
            'last_used_at' => now(),
        ]);

        return response()->json([
            'token' => $plainToken,
            'user' => UserPresenter::toArray($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => UserPresenter::toArray($request->user())]);
    }

    /**
     * Revoke the token used for this request.
     */
    public function logout(Request $request): JsonResponse
    {
        $plainToken = $request->bearerToken();

        if (is_string($plainToken) && $plainToken !== '') {
            ApiToken::where('token', ApiToken::hashToken($plainToken))->delete();
        }

        return response()->json(['message' => 'Signed out.']);
    }
}
