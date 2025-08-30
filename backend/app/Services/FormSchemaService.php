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
        $allowed = ['text','textarea','number','date','time','datetime','duration','email','phone','url','boolean','select','multiselect','radio','checkbox','chips','assignee','reviewer','file','photo_single','photo_repeater','repeater','richtext','markdown','divider','headline'];
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
     * Validate data payload against schema types.
     */
    public function validateData(array $schema, array $data): void
    {
        $fields = collect($schema['sections'] ?? [])
            ->flatMap(fn ($s) => $s['fields'] ?? []);

        foreach ($fields as $field) {
            $key = $field['key'] ?? null;
            if (! $key || ! array_key_exists($key, $data)) {
                continue;
            }
            $val = $data[$key];
            switch ($field['type'] ?? null) {
                case 'date':
                    if (! $this->isValidDate($val)) {
                        throw ValidationException::withMessages([
                            "form_data.$key" => 'invalid date',
                        ]);
                    }
                    break;
                case 'time':
                    if (! $this->isValidTime($val)) {
                        throw ValidationException::withMessages([
                            "form_data.$key" => 'invalid time',
                        ]);
                    }
                    break;
                case 'datetime':
                    if (! $this->isValidDateTime($val)) {
                        throw ValidationException::withMessages([
                            "form_data.$key" => 'invalid datetime',
                        ]);
                    }
                    break;
                case 'duration':
                    if (! $this->isValidDuration($val)) {
                        throw ValidationException::withMessages([
                            "form_data.$key" => 'invalid duration',
                        ]);
                    }
                    break;
            }
        }
    }

    protected function isValidDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d && $d->format('Y-m-d') === $value;
    }

    protected function isValidTime(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }
        $t = \DateTime::createFromFormat('H:i', $value);
        return $t && $t->format('H:i') === $value;
    }

    protected function isValidDateTime(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }
        return (bool) strtotime($value);
    }

    protected function isValidDuration(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }
        return preg_match('/^PT\d+M$/', $value) === 1;
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

    /**
     * Map reviewer data from payload to reviewer_type and reviewer_id.
     */
    public function mapReviewer(array $schema, array &$payload): void
    {
        $fields = collect($schema['sections'] ?? [])
            ->flatMap(fn ($s) => $s['fields'] ?? []);
        $reviewerField = $fields->first(fn ($field) => ($field['type'] ?? null) === 'reviewer');

        if (! $reviewerField) {
            return;
        }

        $key = $reviewerField['key'];
        if (! isset($payload[$key])) {
            return;
        }

        $kind = $payload[$key]['kind'] ?? null;
        $id = $payload[$key]['id'] ?? null;

        if ($kind === 'team') {
            $payload['reviewer_type'] = \App\Models\Team::class;
        } elseif ($kind === 'employee') {
            $payload['reviewer_type'] = \App\Models\User::class;
        } else {
            throw ValidationException::withMessages([
                'reviewer.kind' => 'invalid',
            ]);
        }

        if (! $id) {
            throw ValidationException::withMessages([
                'reviewer.id' => 'required',
            ]);
        }

        $payload['reviewer_id'] = $id;
        unset($payload[$key]);
    }

    /**
     * Sanitize rich text fields in the given data array.
     */
    public function sanitizeRichText(array $schema, array &$data): void
    {
        $fields = collect($schema['sections'] ?? [])
            ->flatMap(fn ($s) => $s['fields'] ?? [])
            ->filter(fn ($f) => ($f['type'] ?? null) === 'richtext');

        foreach ($fields as $field) {
            $key = $field['key'];
            if (! isset($data[$key]) || ! is_string($data[$key])) {
                continue;
            }
            $data[$key] = strip_tags(
                $data[$key],
                '<p><br><b><i><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><a>'
            );
        }
    }
}
