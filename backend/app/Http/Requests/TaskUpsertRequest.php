<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Models\TaskType;
use App\Services\ComputeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskUpsertRequest extends FormRequest
{
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
            'kau_notes' => ['nullable', 'string'],
            'task_type_id' => ['nullable', 'exists:task_types,id'],
            'form_data' => ['nullable', 'array'],
            'assignee' => ['nullable', 'array'],
            'assignee.kind' => ['required_with:assignee', 'in:team,employee'],
            'assignee.id' => ['required_with:assignee', 'integer'],
            'reviewer' => ['nullable', 'array'],
            'reviewer.kind' => ['required_with:reviewer', 'in:team,employee'],
            'reviewer.id' => ['required_with:reviewer', 'integer'],
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

    public function attributes(): array
    {
        return [
            'scheduled_at' => 'scheduled date',
            'sla_start_at' => 'SLA start',
            'sla_end_at' => 'SLA end',
            'kau_notes' => 'Kau notes',
            'task_type_id' => 'task type',
            'form_data' => 'form data',
            'assignee.kind' => 'assignee type',
            'assignee.id' => 'assignee',
            'reviewer.kind' => 'reviewer type',
            'reviewer.id' => 'reviewer',
            'status' => 'status',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Please provide a :attribute.',
            'date' => 'The :attribute must be a valid date.',
            'in' => 'The selected :attribute is invalid.',
            'exists' => 'The selected :attribute is invalid.',
            'integer' => 'The :attribute must be an integer.',
            'array' => 'The :attribute must be an array.',
            'string' => 'The :attribute must be a string.',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
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
