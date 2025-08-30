<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskStatusUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusId = $this->route('task_status')?->id;

        return [
            'name' => ['required', 'string'],
            'slug' => ['sometimes', 'string', Rule::unique('task_statuses')->ignore($statusId)],
            'color' => ['sometimes', 'nullable', 'string', 'max:7'],
            'position' => ['sometimes', 'integer'],
            'tenant_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'slug' => 'slug',
            'color' => 'color',
            'position' => 'position',
            'tenant_id' => 'tenant',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Please provide a :attribute.',
            'string' => 'The :attribute must be a string.',
            'integer' => 'The :attribute must be an integer.',
            'max' => 'The :attribute may not be greater than :max characters.',
        ];
    }
}
