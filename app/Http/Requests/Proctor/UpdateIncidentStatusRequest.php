<?php

namespace App\Http\Requests\Proctor;

use App\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in([Incident::STATUS_PENDING, Incident::STATUS_REVIEWED, Incident::STATUS_DISMISSED])],
        ];
    }
}
