<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id;
        $tenantId = $this->user() && $this->user()->hasRole('SuperAdmin')
            ? $this->input('tenant_id')
            : $this->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($roleId),
            ],
            'abilities' => ['array'],
            'abilities.*' => ['string'],
            'level' => ['integer', 'min:0'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'slug' => 'slug',
            'abilities' => 'abilities',
            'level' => 'level',
            'tenant_id' => 'tenant',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Please provide a :attribute.',
            'string' => 'The :attribute must be a string.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'array' => 'The :attribute must be an array.',
            'exists' => 'The selected :attribute is invalid.',
            'unique' => 'The :attribute has already been taken.',
        ];
    }
}
