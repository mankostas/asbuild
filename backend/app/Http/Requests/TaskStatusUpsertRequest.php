<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesPublicIds;
use App\Models\TaskStatus;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaskStatusUpsertRequest extends FormRequest
{
    use ResolvesPublicIds;

    protected ?int $tenantContextId = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusId = $this->route('task_status')?->id;

        return [
            'name' => ['required', 'string'],
            'slug' => ['sometimes', 'string', Rule::unique('task_statuses')->ignore($statusId)],
            'color' => ['sometimes', 'nullable', 'string', 'max:7'],
            'position' => ['sometimes', 'integer'],
            'tenant_id' => ['sometimes', 'nullable', 'string', 'ulid', Rule::exists('tenants', 'public_id')],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'slug' => 'slug',
            'color' => 'color',
            'position' => 'position',
            'tenant_id' => 'tenant',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Please provide a :attribute.',
            'string' => 'The :attribute must be a string.',
            'ulid' => 'The :attribute must be a valid identifier.',
            'max' => 'The :attribute may not be greater than :max characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $tenantId = $this->user()->hasRole('SuperAdmin')
            ? $this->resolvePublicId(Tenant::class, $this->input('tenant_id'))
            : $this->user()->tenant_id;

        if ($tenantId === null) {
            $tenantId = $this->route('task_status')?->tenant_id;
        }

        $this->tenantContextId = $tenantId;

        $slug = $this->input('slug');
        if ($slug === null) {
            $current = $this->route('task_status');
            $slug = $current ? TaskStatus::stripPrefix($current->slug) : Str::snake($this->input('name', ''));
        }

        $this->merge([
            'slug' => TaskStatus::prefixSlug($slug, $tenantId),
        ]);
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (array_key_exists('tenant_id', $data)) {
            $data['tenant_id'] = $this->resolvePublicId(Tenant::class, $data['tenant_id'])
                ?? $this->tenantContextId;
        } elseif ($this->tenantContextId !== null) {
            $data['tenant_id'] = $this->tenantContextId;
        }

        return $data;
    }
}
