<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesPublicIds;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleUpsertRequest extends FormRequest
{
    use ResolvesPublicIds;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id;
        $tenantId = null;

        if ($this->user() && $this->user()->hasRole('SuperAdmin')) {
            $tenantId = $this->resolvePublicId(Tenant::class, $this->input('tenant_id'))
                ?? $this->route('role')?->tenant_id;
        } else {
            $tenantId = $this->user()?->tenant_id;
        }

        $allowedAbilities = $this->user() && $this->user()->hasRole('SuperAdmin')
            ? config('abilities')
            : (Tenant::find($tenantId)?->allowedAbilities() ?? []);

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($roleId),
            ],
            'abilities' => ['array'],
            'abilities.*' => ['string', Rule::in($allowedAbilities)],
            'level' => ['integer', 'min:0'],
            'tenant_id' => ['nullable', 'string', 'ulid', Rule::exists('tenants', 'public_id')],
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
            'abilities.*.in' => 'One or more abilities are not allowed for this tenant.',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (array_key_exists('tenant_id', $data)) {
            $data['tenant_id'] = $this->resolvePublicId(Tenant::class, $data['tenant_id']);
        }

        return $data;
    }
}
