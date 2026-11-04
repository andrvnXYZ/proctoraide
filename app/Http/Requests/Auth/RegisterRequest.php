<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the incoming registration data before validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        foreach ([
            'first_name',
            'middle_name',
            'last_name',
            'student_id_number',
            'college',
            'department',
            'year_level',
        ] as $field) {
            if (is_string($this->input($field))) {
                $data[$field] = trim($this->input($field));
            }
        }

        if (is_string($this->input('email'))) {
            $data['email'] = Str::lower(trim($this->input('email')));
        }

        if (is_string($this->input('role'))) {
            $data['role'] = Str::lower(trim($this->input('role')));
        }

        // Accept either "confirm_password" or Laravel's "password_confirmation".
        if (! $this->filled('password_confirmation') && $this->filled('confirm_password')) {
            $data['password_confirmation'] = $this->input('confirm_password');
        }

        // Terms can be accepted with a checkbox; always store a server timestamp.
        if ($this->boolean('terms_accepted') && ! $this->filled('terms_accepted_at')) {
            $data['terms_accepted_at'] = now();
        }

        $this->merge($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birthday' => ['required', 'date', 'before:today'],
            'student_id_number' => [
                'required_if:role,'.User::ROLE_STUDENT,
                'nullable',
                'string',
                'max:50',
                Rule::unique(User::class, 'student_id_number'),
            ],
            'college' => ['required', 'string', 'max:150'],
            'department' => ['required', 'string', 'max:150'],
            'year_level' => [
                'required_if:role,'.User::ROLE_STUDENT,
                'nullable',
                'string',
                'max:50',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email'),
            ],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'role' => [
                'required',
                'string',
                Rule::in(User::PUBLIC_ROLES),
            ],
            'terms_accepted' => ['sometimes', 'accepted'],
            'terms_accepted_at' => ['required', 'date'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email address already exists.',
            'student_id_number.unique' => 'An account with this student ID number already exists.',
            'role.in' => 'Only student and proctor accounts can be registered.',
            'terms_accepted.accepted' => 'You must accept the terms and conditions.',
            'terms_accepted_at.required' => 'You must accept the terms and conditions.',
        ];
    }
}
