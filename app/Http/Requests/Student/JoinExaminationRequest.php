<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class JoinExaminationRequest extends FormRequest
{
    /**
     * The student role is enforced by the route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('access_code'))) {
            $this->merge([
                'access_code' => strtoupper(trim($this->input('access_code'))),
            ]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'access_code' => ['required', 'string', 'size:6', 'regex:/^[A-Z0-9]{6}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'access_code.required' => 'Enter the access code provided by your proctor.',
            'access_code.size' => 'The access code must be exactly 6 characters.',
            'access_code.regex' => 'The access code must be 6 letters or numbers.',
        ];
    }
}
