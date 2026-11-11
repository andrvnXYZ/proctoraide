<?php

namespace App\Http\Requests\Proctor;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isProctor() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'examination_id' => ['required', 'string'],
            'student_id' => ['nullable', 'string'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'examination_id.required' => 'Choose the examination session this incident happened in.',
            'description.required' => 'Describe what happened.',
            'description.min' => 'Please describe the incident in at least 10 characters.',
            'description.max' => 'Keep the description under 2000 characters.',
        ];
    }
}
