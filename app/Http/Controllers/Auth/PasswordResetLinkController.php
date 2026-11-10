<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
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
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required_without:email', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
        ], [
            'identifier.required_without' => 'Enter your student ID number or email address.',
        ]);

        $identifier = trim((string) ($validated['identifier'] ?? $request->input('email', '')));

        $email = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? $identifier
            : User::query()->where('student_id_number', $identifier)->value('email');

        if (is_string($email) && $email !== '') {
            Password::sendResetLink(['email' => $email]);
        }

        return back()->with('status', __('A reset link will be sent if the account exists.'));
    }
}
