<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = (bool) $this->route('taskType');
        $required = $isUpdate ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'schema_json' => ['nullable', 'json'],
            'statuses' => ['sometimes', 'json'],
            'status_flow_json' => ['sometimes', 'json'],
            'tenant_id' => ['sometimes', 'integer'],
            'abilities_json' => ['nullable', 'json'],
            'require_subtasks_complete' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'schema_json' => 'schema',
            'statuses' => 'statuses',
            'status_flow_json' => 'status flow',
            'tenant_id' => 'tenant',
            'abilities_json' => 'abilities',
            'require_subtasks_complete' => 'require subtasks complete',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Please provide a :attribute.',
            'json' => 'The :attribute must be valid JSON.',
            'integer' => 'The :attribute must be an integer.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'string' => 'The :attribute must be a string.',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        foreach (['schema_json', 'statuses', 'status_flow_json'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = json_decode($data[$field], true);
            }
        }
        if (isset($data['abilities_json'])) {
            $data['abilities_json'] = json_decode($data['abilities_json'], true);
        }
        return $data;
    }
}
