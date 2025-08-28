<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TypeUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = (bool) $this->route('appointmentType');
        $required = $isUpdate ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'form_schema' => ['nullable', 'json'],
            'fields_summary' => ['nullable', 'json'],
            'statuses' => [$required, 'json'],
            'tenant_id' => ['sometimes', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'form_schema' => 'form schema',
            'fields_summary' => 'fields summary',
            'statuses' => 'statuses',
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
        foreach (['form_schema', 'fields_summary', 'statuses'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = json_decode($data[$field], true);
            }
        }
        return $data;
    }
}
