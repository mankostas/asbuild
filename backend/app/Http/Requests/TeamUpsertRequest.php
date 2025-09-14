<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teamId = $this->route('team')?->id;
        $tenantId = $this->user() && $this->user()->hasRole('SuperAdmin')
            ? ($this->input('tenant_id') ?? app('tenant_id'))
            : $this->user()->tenant_id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('teams')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($teamId),
            ],
            'description' => ['nullable', 'string'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'lead_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'description' => 'description',
            'tenant_id' => 'tenant',
            'lead_id' => 'team lead',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Please provide a :attribute.',
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'exists' => 'The selected :attribute is invalid.',
            'unique' => 'The :attribute has already been taken.',
        ];
    }
}
