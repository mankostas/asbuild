<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class FormSchemaService
{
    /**
     * Validate the task type schema structure.
     *
     * Schema keys:
     *  sections[]: { key, label, fields[], photos[], allow_subtasks }
     *  fields[]: { key, label, type: text|textarea|number|date|time|datetime|boolean|select|multiselect|assignee|file, required, enum?, x-cols? }
     *  photos[]: { key, label, type: photo_single|photo_repeater }
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
            foreach ($section['photos'] ?? [] as $photo) {
                $this->validatePhoto($photo);
            }
        }
    }

    protected function validateField(array $field): void
    {
        $allowed = ['text','textarea','number','date','time','datetime','boolean','select','multiselect','assignee','file'];
        if (! isset($field['key'], $field['label']) || ! in_array($field['type'] ?? '', $allowed, true)) {
            throw ValidationException::withMessages([
                'schema_json' => 'invalid field',
            ]);
        }
        if (in_array($field['type'], ['select','multiselect'], true) && ! is_array($field['enum'] ?? null)) {
            throw ValidationException::withMessages([
                'schema_json' => 'enum required for select types',
            ]);
        }
    }

    protected function validatePhoto(array $photo): void
    {
        $allowed = ['photo_single','photo_repeater'];
        if (! isset($photo['key'], $photo['label']) || ! in_array($photo['type'] ?? '', $allowed, true)) {
            throw ValidationException::withMessages([
                'schema_json' => 'invalid photo',
            ]);
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
