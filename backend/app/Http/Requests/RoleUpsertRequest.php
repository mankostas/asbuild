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
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ];
    }
}
