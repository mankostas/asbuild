<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskSlaPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'priority' => ['required', 'string'],
            'response_within_mins' => ['nullable', 'integer'],
            'resolve_within_mins' => ['nullable', 'integer'],
            'calendar_json' => ['nullable', 'array'],
        ];
    }
}
