<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event' => ['required', 'string'],
            'conditions_json' => ['nullable', 'array'],
            'actions_json' => ['required', 'array'],
            'enabled' => ['boolean'],
        ];
    }
}
