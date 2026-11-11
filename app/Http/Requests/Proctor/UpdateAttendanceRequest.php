<?php

namespace App\Http\Requests\Proctor;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRequest extends FormRequest
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
            'statuses' => ['required', 'array'],
            'statuses.*' => ['required', Rule::in([...AttendanceRecord::STATUSES, 'not_checked'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'statuses.required' => 'Choose an attendance status for at least one student.',
            'statuses.*.in' => 'Choose a valid attendance status.',
        ];
    }
}
