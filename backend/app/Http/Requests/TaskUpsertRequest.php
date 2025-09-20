<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesPublicIds;
use App\Models\Client;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ComputeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskUpsertRequest extends FormRequest
{
    use ResolvesPublicIds;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'scheduled_at' => ['nullable', 'date'],
            'sla_start_at' => ['nullable', 'date'],
            'sla_end_at' => ['nullable', 'date'],
            'priority' => ['nullable', 'string'],
            'kau_notes' => ['nullable', 'string'],
            'task_type_id' => ['nullable', 'string', 'ulid', Rule::exists('task_types', 'public_id')],
            'form_data' => ['nullable', 'array'],
            'assignee' => ['nullable', 'array'],
            'assignee.id' => ['required_with:assignee', 'string', 'ulid', Rule::exists('users', 'public_id')],
            'assigned_user_id' => ['nullable', 'string', 'ulid', Rule::exists('users', 'public_id')],
            'reviewer' => ['nullable', 'array'],
            'reviewer.kind' => ['required_with:reviewer', 'in:team,employee'],
            'reviewer.id' => [
                'required_with:reviewer',
                'string',
                'ulid',
                function ($attribute, $value, $fail) {
                    $kind = $this->input('reviewer.kind');
                    $modelClass = match ($kind) {
                        'team' => Team::class,
                        'employee' => User::class,
                        default => null,
                    };

                    if ($modelClass === null) {
                        return;
                    }

                    if (! $modelClass::query()->where('public_id', $value)->exists()) {
                        $fail('The selected reviewer is invalid.');
                    }
                },
            ],
            'client_id' => [
                'nullable',
                'string',
                'ulid',
                Rule::exists('clients', 'public_id')->whereNull('deleted_at'),
            ],
        ];

        if ($task = $this->route('task')) {
            $typeId = $this->input('task_type_id', $task->task_type_id);
            $type = $typeId ? TaskType::find($typeId) : null;
            $transitions = property_exists(Task::class, 'transitions') ? Task::$transitions : [];
            $allowed = collect($type->statuses ?? $transitions)
                ->flatMap(fn ($next, $current) => array_merge([$current], $next))
                ->unique()
                ->all();
            $rules['status'] = ['nullable', Rule::in($allowed)];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $assignee = $this->input('assignee');

        if (is_array($assignee)) {
            $id = $assignee['id'] ?? null;
            $publicId = $assignee['public_id'] ?? null;

            if ($publicId && ($id === null || is_numeric($id))) {
                $assignee['id'] = $publicId;
                $this->merge(['assignee' => $assignee]);
            }
        }

        if ($this->has('assigned_user_id') && is_numeric($this->input('assigned_user_id'))) {
            $this->merge(['assigned_user_id' => (string) $this->input('assigned_user_id')]);
        }

        if ($this->has('client_id') && is_numeric($this->input('client_id'))) {
            $this->merge(['client_id' => (string) $this->input('client_id')]);
        }

        if ($this->has('task_type_id') && is_numeric($this->input('task_type_id'))) {
            $this->merge(['task_type_id' => (string) $this->input('task_type_id')]);
        }
    }

    public function attributes(): array
    {
        return [
            'scheduled_at' => 'scheduled date',
            'sla_start_at' => 'SLA start',
            'sla_end_at' => 'SLA end',
            'priority' => 'priority',
            'kau_notes' => 'Kau notes',
            'task_type_id' => 'task type',
            'form_data' => 'form data',
            'assignee.id' => 'assignee',
            'assigned_user_id' => 'assignee',
            'reviewer.kind' => 'reviewer type',
            'reviewer.id' => 'reviewer',
            'status' => 'status',
            'client_id' => 'client',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Please provide a :attribute.',
            'date' => 'The :attribute must be a valid date.',
            'in' => 'The selected :attribute is invalid.',
            'exists' => 'The selected :attribute is invalid.',
            'ulid' => 'The :attribute must be a valid identifier.',
            'array' => 'The :attribute must be an array.',
            'string' => 'The :attribute must be a string.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clientId = $this->input('client_id');
            if (! $clientId) {
                return;
            }

            $client = Client::query()->withTrashed()->where('public_id', $clientId)->first();
            if (! $client || $client->trashed()) {
                $validator->errors()->add('client_id', 'The selected client is invalid.');

                return;
            }

            $targetTenant = null;

            if ($this->user()->isSuperAdmin()) {
                $attributeTenant = $this->attributes->get('tenant_id');
                if (is_string($attributeTenant) && ! is_numeric($attributeTenant)) {
                    $attributeTenant = $this->resolvePublicId(Tenant::class, $attributeTenant);
                }

                if ($attributeTenant !== null) {
                    $targetTenant = (int) $attributeTenant;
                }

                if ($targetTenant === null) {
                    $targetTenant = $this->resolvePublicId(Tenant::class, $this->input('tenant_id'));
                }

                if ($targetTenant === null && $task = $this->route('task')) {
                    $targetTenant = $task->tenant_id;
                }
            } else {
                $targetTenant = $this->user()->tenant_id;
            }

            if ($targetTenant === null || (int) $client->tenant_id !== (int) $targetTenant) {
                $validator->errors()->add('client_id', 'The selected client is invalid.');
            }
        });
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (array_key_exists('task_type_id', $data)) {
            $data['task_type_id'] = $this->resolvePublicId(TaskType::class, $data['task_type_id']);
        }

        if (array_key_exists('assigned_user_id', $data)) {
            $data['assigned_user_id'] = $this->resolvePublicId(User::class, $data['assigned_user_id']);
        }

        if (isset($data['assignee']['id'])) {
            $data['assignee']['id'] = $this->resolvePublicId(User::class, $data['assignee']['id']);
            if ($data['assignee']['id']) {
                $data['assigned_user_id'] = $data['assignee']['id'];
            }
        }

        if (isset($data['reviewer']['id'])) {
            $modelClass = match ($data['reviewer']['kind'] ?? null) {
                'team' => Team::class,
                'employee' => User::class,
                default => null,
            };

            if ($modelClass !== null) {
                $data['reviewer']['id'] = $this->resolvePublicId($modelClass, $data['reviewer']['id']);
            }
        }

        if (array_key_exists('client_id', $data)) {
            $data['client_id'] = $this->resolvePublicId(Client::class, $data['client_id']);
        }

        $typeId = $data['task_type_id'] ?? $this->route('task')?->task_type_id;
        if (! $typeId || ! isset($data['form_data'])) {
            return $data;
        }
        $type = TaskType::find($typeId);
        if (! $type || ! $type->schema_json) {
            return $data;
        }
        $fields = collect($type->schema_json['sections'] ?? [])
            ->flatMap(fn ($s) => $s['fields'] ?? []);
        $compute = app(ComputeService::class);
        foreach ($fields as $field) {
            if (($field['type'] ?? '') === 'computed' && isset($field['expr'], $field['key'])) {
                $data['form_data'][$field['key']] = $compute->evaluate($field['expr'], $data['form_data']);
            }
        }
        return $data;
    }
}
