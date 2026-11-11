<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\ValidEmailAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the password reset link request page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * The form accepts either the account email or the student ID number.
     * Both are resolved to the registered email before sending the link.
     * The response is always the same so the form cannot be used to discover
     * whether an account exists.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $identifier = $request->input('identifier', $request->input('email'));
        $identifier = is_string($identifier) ? trim($identifier) : $identifier;

        if (is_string($identifier) && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $identifier = Str::lower($identifier);
        }

        $request->merge(['identifier' => $identifier]);

        $rules = [
            'identifier' => ['required', 'string', 'max:255'],
        ];

        // Only enforce an email shape when the input is clearly an email attempt,
        // so student ID numbers remain accepted.
        if (is_string($identifier) && str_contains($identifier, '@')) {
            $rules['identifier'][] = 'email';
            $rules['identifier'][] = new ValidEmailAddress;
        }

        $request->validate($rules, [
            'identifier.required' => 'Enter your student ID number or email address.',
            'identifier.email' => 'Enter a valid email address or student ID number.',
        ]);

        $email = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? $identifier
            : User::query()->where('student_id_number', $identifier)->value('email');

        if (is_string($email) && $email !== '') {
            Password::sendResetLink(['email' => Str::lower(trim($email))]);
        }

        return back()->with('status', __('If an account matches that information, a reset link has been sent.'));
    }
}
