<?php

namespace App\Http\Controllers\Api;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post');
        $tenantRules = [];

        if ($this->user()?->isSuperAdmin()) {
            $tenantRules = $isCreate ? ['required', 'integer', 'exists:tenants,id'] : ['sometimes', 'integer', 'exists:tenants,id'];
        } else {
            $tenantRules = ['prohibited'];
        }

        return [
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'tenant_id' => $tenantRules,
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }

    public function attributes(): array
    {
        return [
            'owner_id' => 'owner',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $ownerId = $this->input('owner_id');
            $tenantId = $this->determineTargetTenant();

            if ($ownerId) {
                $owner = User::query()->find($ownerId);
                if (! $owner || ($tenantId !== null && (int) $owner->tenant_id !== (int) $tenantId)) {
                    $validator->errors()->add('owner_id', 'The selected owner is invalid.');
                }
            }

            if ($this->isMethod('post') && $tenantId === null) {
                $validator->errors()->add('tenant_id', 'The tenant field is required.');
            }

            if ($this->route('client') instanceof Client && ! $this->user()->isSuperAdmin()) {
                if ($this->input('tenant_id')) {
                    $validator->errors()->add('tenant_id', 'The tenant field cannot be updated.');
                }
            }
        });
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (! $this->user()->isSuperAdmin()) {
            unset($data['tenant_id']);
        }

        return $data;
    }

    public function determineTargetTenant(): ?int
    {
        if ($this->user()?->isSuperAdmin()) {
            if ($this->has('tenant_id')) {
                return (int) $this->input('tenant_id');
            }

            if ($this->route('client') instanceof Client) {
                return (int) $this->route('client')->tenant_id;
            }

            $attributeTenant = $this->attributes->get('tenant_id');

            return $attributeTenant !== null ? (int) $attributeTenant : null;
        }

        return $this->user()?->tenant_id;
    }
}
