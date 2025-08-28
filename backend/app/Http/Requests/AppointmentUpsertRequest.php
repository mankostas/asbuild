<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Models\AppointmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentUpsertRequest extends FormRequest
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
            'appointment_type_id' => ['nullable', 'exists:appointment_types,id'],
            'form_data' => ['nullable', 'array'],
            'assignee' => ['nullable', 'array'],
            'assignee.kind' => ['required_with:assignee', 'in:team,employee'],
            'assignee.id' => ['required_with:assignee', 'integer'],
        ];

        if ($appointment = $this->route('appointment')) {
            $typeId = $this->input('appointment_type_id', $appointment->appointment_type_id);
            $type = $typeId ? AppointmentType::find($typeId) : null;
            $allowed = collect($type->statuses ?? Appointment::$transitions)
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
            'appointment_type_id' => 'appointment type',
            'form_data' => 'form data',
            'assignee.kind' => 'assignee type',
            'assignee.id' => 'assignee',
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
}
