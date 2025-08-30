<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class FormSchemaService
{
    /**
     * Validate the task type schema structure.
     *
     * Schema keys:
     *  sections[]: { key, label, fields[], allow_subtasks }
     *  fields[]: { key, label, type: text|textarea|number|date|time|datetime|email|phone|url|boolean|select|multiselect|radio|checkbox|chips|assignee|file|photo_single|photo_repeater|repeater, required, enum?, x-cols?, fields? }
     */
    public function validate(array $schema): void
    {
        if (! is_array($schema['sections'] ?? null)) {
            throw ValidationException::withMessages([
                'schema_json' => 'sections missing',
            ]);
        }

        foreach ($schema['sections'] as $section) {
            if (! isset($section['key'], $section['label'])) {
                throw ValidationException::withMessages([
                    'schema_json' => 'section key/label required',
                ]);
            }
            foreach ($section['fields'] ?? [] as $field) {
                $this->validateField($field);
            }
        }
    }

    protected function validateField(array $field): void
    {
        $allowed = ['text','textarea','number','date','time','datetime','email','phone','url','boolean','select','multiselect','radio','checkbox','chips','assignee','file','photo_single','photo_repeater','repeater'];
        if (! isset($field['key'], $field['label']) || ! in_array($field['type'] ?? '', $allowed, true)) {
            throw ValidationException::withMessages([
                'schema_json' => 'invalid field',
            ]);
        }
        if (in_array($field['type'], ['select','multiselect','radio','checkbox','chips'], true) && ! is_array($field['enum'] ?? null)) {
            throw ValidationException::withMessages([
                'schema_json' => 'enum required for choice types',
            ]);
        }
        if ($field['type'] === 'repeater') {
            if (! is_array($field['fields'] ?? null)) {
                throw ValidationException::withMessages([
                    'schema_json' => 'repeater fields invalid',
                ]);
            }
            foreach ($field['fields'] as $sub) {
                $this->validateField($sub);
            }
        }
    }

    /**
     * Map assignee data from payload to assignee_type and assignee_id.
     */
    public function mapAssignee(array $schema, array &$payload): void
    {
        $fields = collect($schema['sections'] ?? [])
            ->flatMap(fn ($s) => $s['fields'] ?? []);
        $assigneeField = $fields->first(fn ($field) => ($field['type'] ?? null) === 'assignee');

        if (! $assigneeField) {
            return;
        }

        $key = $assigneeField['key'];
        if (! isset($payload[$key])) {
            return;
        }

        $kind = $payload[$key]['kind'] ?? null;
        $id = $payload[$key]['id'] ?? null;

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
        unset($payload[$key]);
    }
}
