<?php

namespace App\Http\Requests\Proctor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExaminationRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'subject' => ['required', 'string', 'min:2', 'max:120'],
            'section' => ['required', 'string', 'min:1', 'max:80'],
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Enter an examination title.',
            'subject.required' => 'Enter the course or subject.',
            'section.required' => 'Enter the section that will take this examination.',
            'scheduled_date.required' => 'Choose the examination date.',
            'scheduled_date.date_format' => 'Choose a valid examination date.',
            'start_time.required' => 'Enter the start time.',
            'start_time.date_format' => 'Enter the start time as HH:MM.',
            'end_time.required' => 'Enter the end time.',
            'end_time.date_format' => 'Enter the end time as HH:MM.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['start_time', 'end_time'])) {
                    return;
                }

                if (strcmp((string) $this->input('end_time'), (string) $this->input('start_time')) <= 0) {
                    $validator->errors()->add('end_time', 'The end time must be later than the start time.');
                }
            },
        ];
    }
}
