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
        ];
    }
}
