<?php

namespace App\Http\Requests\Proctor;

use Illuminate\Foundation\Http\FormRequest;

class AddStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isProctor() === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'string', 'max:64'],
            'subject' => ['required', 'string', 'max:120'],
            'section' => ['required', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Search for and select a student first.',
            'subject.required' => 'Choose the subject to enroll the student in.',
            'section.required' => 'Choose the section to enroll the student in.',
        ];
    }
}
