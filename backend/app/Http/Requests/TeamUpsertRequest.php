<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesPublicIds;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamUpsertRequest extends FormRequest
{
    use ResolvesPublicIds;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teamId = $this->route('team')?->id;
        $tenantId = null;

        if ($this->user() && $this->user()->hasRole('SuperAdmin')) {
            $tenantId = $this->resolvePublicId(Tenant::class, $this->input('tenant_id'));

            if ($tenantId === null && app()->bound('tenant_id')) {
                $appTenant = app('tenant_id');
                if (is_string($appTenant) && ! is_numeric($appTenant)) {
                    $tenantId = $this->resolvePublicId(Tenant::class, $appTenant);
                } else {
                    $tenantId = $appTenant;
                }
            }
        } else {
            $tenantId = $this->user()?->tenant_id;
        }

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('teams')->where(fn ($q) => $q->where('tenant_id', $tenantId))->ignore($teamId),
            ],
            'description' => ['nullable', 'string'],
            'tenant_id' => ['nullable', 'string', 'ulid', Rule::exists('tenants', 'public_id')],
            'lead_id' => ['nullable', 'string', 'ulid', Rule::exists('users', 'public_id')],
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
            'ulid' => 'The :attribute must be a valid identifier.',
            'unique' => 'The :attribute has already been taken.',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (array_key_exists('tenant_id', $data)) {
            $data['tenant_id'] = $this->resolvePublicId(Tenant::class, $data['tenant_id']);
        }

        if (array_key_exists('lead_id', $data)) {
            $data['lead_id'] = $this->resolvePublicId(User::class, $data['lead_id']);
        }

        return $data;
    }
}
