<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class FormSchemaService
{
    /**
     * Validate the form schema structure.
     */
    public function validate(array $schema): void
    {
        if (($schema['type'] ?? null) !== 'object' || ! is_array($schema['properties'] ?? null)) {
            throw ValidationException::withMessages([
                'form_schema' => 'Schema must be an object with properties',
            ]);
        }

        if (isset($schema['required'])) {
            foreach ($schema['required'] as $field) {
                if (! array_key_exists($field, $schema['properties'])) {
                    throw ValidationException::withMessages([
                        'form_schema' => "Required field {$field} missing from properties",
                    ]);
                }
            }
        }
    }

    /**
     * Map assignee data from payload to assignee_type and assignee_id.
     */
    public function mapAssignee(array $schema, array &$payload): void
    {
        $hasAssignee = collect($schema['properties'] ?? [])
            ->contains(fn ($field) => ($field['kind'] ?? null) === 'assignee');

        if (! $hasAssignee || ! isset($payload['assignee'])) {
            return;
        }

        $kind = $payload['assignee']['kind'] ?? null;
        $id = $payload['assignee']['id'] ?? null;

        if ($kind === 'team') {
            $payload['assignee_type'] = \App\Models\Team::class;
        } elseif ($kind === 'employee') {
            $payload['assignee_type'] = \App\Models\User::class;
        } else {
            throw ValidationException::withMessages([
                'assignee.kind' => 'invalid',
            ]);
        }

        if (! $id) {
            throw ValidationException::withMessages([
                'assignee.id' => 'required',
            ]);
        }

        $payload['assignee_id'] = $id;
        unset($payload['assignee']);
    }
}
