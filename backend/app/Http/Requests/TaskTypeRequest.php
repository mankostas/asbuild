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
            'statuses' => [$required, 'json'],
            'status_flow_json' => ['nullable', 'json'],
            'tenant_id' => ['sometimes', 'integer'],
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
        return $data;
    }
}
