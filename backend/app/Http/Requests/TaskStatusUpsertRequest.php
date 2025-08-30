<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskStatusUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'tenant_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'tenant_id' => 'tenant',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Please provide a :attribute.',
            'string' => 'The :attribute must be a string.',
            'integer' => 'The :attribute must be an integer.',
        ];
    }
}
