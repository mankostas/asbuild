<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Concerns\ResolvesPublicIds;
use App\Models\Client;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    use ResolvesPublicIds;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post');
        $tenantRules = [];

        if ($this->user()?->isSuperAdmin()) {
            $tenantRules = $isCreate
                ? ['required', 'string', 'ulid', Rule::exists('tenants', 'public_id')]
                : ['sometimes', 'string', 'ulid', Rule::exists('tenants', 'public_id')];
        } else {
            $tenantRules = ['prohibited'];
        }

        return [
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'notify_client' => ['sometimes', 'boolean'],
            'tenant_id' => $tenantRules,
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $tenantId = $this->determineTargetTenant();

            if ($this->isMethod('post') && $tenantId === null) {
                $validator->errors()->add('tenant_id', 'The tenant field is required.');
            }

            if ($this->boolean('notify_client') && ! $this->filled('email')) {
                $validator->errors()->add('email', 'An email address is required to notify the client.');
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

        if ($this->user()->isSuperAdmin() && array_key_exists('tenant_id', $data)) {
            $tenantId = $this->resolvePublicId(Tenant::class, $data['tenant_id']);

            if ($tenantId === null && $data['tenant_id'] !== null) {
                $data['tenant_id'] = null;
            } else {
                $data['tenant_id'] = $tenantId;
            }
        }

        if (! $this->user()->isSuperAdmin()) {
            unset($data['tenant_id']);
        }

        unset($data['notify_client']);

        return $data;
    }

    public function determineTargetTenant(): ?int
    {
        if ($this->user()?->isSuperAdmin()) {
            if ($this->has('tenant_id')) {
                return $this->resolvePublicId(Tenant::class, $this->input('tenant_id'));
            }

            if ($this->route('client') instanceof Client) {
                return (int) $this->route('client')->tenant_id;
            }

            $attributeTenant = $this->attributes->get('tenant_id');

            if ($attributeTenant instanceof Tenant) {
                return (int) $attributeTenant->getKey();
            }

            if (is_string($attributeTenant) || is_int($attributeTenant)) {
                return $this->resolvePublicId(Tenant::class, $attributeTenant);
            }

            return null;
        }

        return $this->user()?->tenant_id;
    }
}
