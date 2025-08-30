<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class FormSchemaService
{
    /**
     * Validate the task type schema structure.
     *
     * Schema keys:
     *  sections[]: { key, label, fields[], allow_subtasks }
     *  fields[]: { key, label, type: text|textarea|number|date|time|datetime|email|phone|url|boolean|select|multiselect|radio|checkbox|chips|assignee|file|photo_single|photo_repeater|repeater, validations?, enum?, x-cols?, fields? }
     */
    public function validate(array $schema): void
    {
        if (! is_array($schema['sections'] ?? null)) {
            throw ValidationException::withMessages([
                'schema_json' => 'sections missing',
            ]);
        }
        $allKeys = [];
        foreach ($schema['sections'] as $section) {
            if (! isset($section['key'], $section['label'])) {
                throw ValidationException::withMessages([
                    'schema_json' => 'section key/label required',
                ]);
            }
            foreach ($section['fields'] ?? [] as $field) {
                if (isset($field['key'])) {
                    $allKeys[] = $field['key'];
                }
            }
        }
        foreach ($schema['sections'] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $this->validateField($field, $allKeys);
            }
        }
    }

    protected function validateField(array $field, array $allKeys): void
    {
        $allowed = ['text','textarea','number','date','time','datetime','duration','email','phone','url','boolean','select','multiselect','radio','checkbox','chips','assignee','reviewer','file','photo_single','photo_repeater','repeater','richtext','markdown','divider','headline','lookup','computed','signature','location','rating','priority','status'];
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
        if ($field['type'] === 'lookup' && ! (isset($field['endpoint']) || isset($field['view']))) {
            throw ValidationException::withMessages([
                'schema_json' => 'lookup config invalid',
            ]);
        }
        if ($field['type'] === 'computed') {
            if (! is_string($field['expr'] ?? null)) {
                throw ValidationException::withMessages([
                    'schema_json' => 'computed expression required',
                ]);
            }
            preg_match_all('/[A-Za-z_][A-Za-z0-9_.]*/', $field['expr'], $m);
            foreach ($m[0] as $ref) {
                if (! in_array($ref, $allKeys, true)) {
                    throw ValidationException::withMessages([
                        'schema_json' => "unknown field reference $ref",
                    ]);
                }
            }
        }
        if (isset($field['validations']) && ! is_array($field['validations'])) {
            throw ValidationException::withMessages([
                'schema_json' => 'validations must be object',
            ]);
        }

        if ($field['type'] === 'repeater') {
            if (! is_array($field['fields'] ?? null)) {
                throw ValidationException::withMessages([
                    'schema_json' => 'repeater fields invalid',
                ]);
            }
            foreach ($field['fields'] as $sub) {
                $this->validateField($sub, $allKeys);
            }
        }
    }

    /**
     * Evaluate conditional logic against form data.
     *
     * @return array{visible: array, required: array, showTargets: array}
     */
    public function evaluateLogic(array $schema, array $data): array
    {
        $visible = [];
        $required = [];
        $showTargets = [];

        foreach ($schema['logic'] ?? [] as $rule) {
            foreach ($rule['then'] ?? [] as $action) {
                if (isset($action['show'])) {
                    $showTargets[] = $action['show'];
                }
            }

            $condField = $rule['if']['field'] ?? null;
            $eq = $rule['if']['eq'] ?? null;

            if ($condField && ($data[$condField] ?? null) === $eq) {
                foreach ($rule['then'] ?? [] as $action) {
                    if (isset($action['show'])) {
                        $visible[] = $action['show'];
                    }
                    if (isset($action['require'])) {
                        $required[] = $action['require'];
                    }
                }
            }
        }

        return [
            'visible' => $visible,
            'required' => $required,
            'showTargets' => $showTargets,
        ];
    }

    /**
     * Validate data payload against schema types.
     */
    public function validateData(array $schema, array $data): void
    {
        $fields = collect($schema['sections'] ?? [])
            ->flatMap(fn ($s) => $s['fields'] ?? []);

        $logic = $this->evaluateLogic($schema, $data);
        $visible = collect($logic['visible']);
        $showTargets = collect($logic['showTargets']);
        $requiredOverride = collect($logic['required']);

        foreach ($fields as $field) {
            $key = $field['key'] ?? null;
            if (! $key) {
                continue;
            }
            $rules = $field['validations'] ?? [];

            $hasShow = $showTargets->contains($key);
            $isVisible = ! $hasShow || $visible->contains($key);
            if (! $isVisible) {
                continue;
            }

            $present = array_key_exists($key, $data);
            $val = $present ? $data[$key] : null;

            $isRequired = ($rules['required'] ?? false) || $requiredOverride->contains($key);
            if ($isRequired && ! $present) {
                throw ValidationException::withMessages([
                    "form_data.$key" => 'required',
                ]);
            }

            if (! $present) {
                continue;
            }

            if (($rules['regex'] ?? null) && is_string($val) && @preg_match('/' . $rules['regex'] . '/', '') !== false) {
                if (! preg_match('/' . $rules['regex'] . '/', $val)) {
                    throw ValidationException::withMessages([
                        "form_data.$key" => 'invalid',
                    ]);
                }
            }
            if (is_numeric($val)) {
                if (isset($rules['min']) && $val < $rules['min']) {
                    throw ValidationException::withMessages([
                        "form_data.$key" => 'min',
                    ]);
                }
                if (isset($rules['max']) && $val > $rules['max']) {
                    throw ValidationException::withMessages([
                        "form_data.$key" => 'max',
                    ]);
                }
            }
            if (is_string($val)) {
                if (isset($rules['lengthMin']) && mb_strlen($val) < $rules['lengthMin']) {
                    throw ValidationException::withMessages([
                        "form_data.$key" => 'min_length',
                    ]);
                }
                if (isset($rules['lengthMax']) && mb_strlen($val) > $rules['lengthMax']) {
                    throw ValidationException::withMessages([
                        "form_data.$key" => 'max_length',
                    ]);
                }
            }
            if (isset($rules['mime']) && is_array($rules['mime']) && is_array($val) && isset($val['mime'])) {
                if (! in_array($val['mime'], $rules['mime'], true)) {
                    throw ValidationException::withMessages([
                        "form_data.$key" => 'mime',
                    ]);
                }
            }
            if (isset($rules['size']) && is_array($val) && isset($val['size']) && $val['size'] > $rules['size']) {
                throw ValidationException::withMessages([
                    "form_data.$key" => 'size',
                ]);
            }

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
     * Filter schema fields based on user roles.
     */
    public function filterSchemaForRoles(array $schema, User $user): array
    {
        $roles = $this->userRoles($user);
        $defaults = $schema['roles'] ?? [];

        $sections = [];
        foreach ($schema['sections'] ?? [] as $section) {
            $fields = [];
            foreach ($section['fields'] ?? [] as $field) {
                $access = $this->resolveAccess($field['roles'] ?? [], $defaults, $roles);
                if ($access === 'hidden') {
                    continue;
                }
                if ($access === 'read') {
                    $field['readOnly'] = true;
                }
                $fields[] = $field;
            }
            if ($fields) {
                $section['fields'] = $fields;
                $sections[] = $section;
            }
        }

        $schema['sections'] = $sections;
        return $schema;
    }

    /**
     * Filter data payload removing hidden fields.
     */
    public function filterDataForRoles(array $schema, array $data, User $user): array
    {
        $roles = $this->userRoles($user);
        $defaults = $schema['roles'] ?? [];

        foreach ($schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $key = $field['key'] ?? null;
                if (! $key) {
                    continue;
                }
                $access = $this->resolveAccess($field['roles'] ?? [], $defaults, $roles);
                if ($access === 'hidden') {
                    unset($data[$key]);
                }
            }
        }

        return $data;
    }

    /**
     * Ensure user cannot edit read-only or hidden fields.
     */
    public function assertCanEdit(array $schema, array $data, User $user): void
    {
        $roles = $this->userRoles($user);
        $defaults = $schema['roles'] ?? [];

        foreach ($schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $key = $field['key'] ?? null;
                if (! $key || ! array_key_exists($key, $data)) {
                    continue;
                }
                $access = $this->resolveAccess($field['roles'] ?? [], $defaults, $roles);
                if ($access !== 'edit') {
                    throw ValidationException::withMessages([
                        "form_data.$key" => 'forbidden',
                    ]);
                }
            }
        }
    }

    protected function resolveAccess(array $fieldRoles, array $schemaRoles, array $userRoles): string
    {
        foreach ($userRoles as $role) {
            if (isset($fieldRoles[$role])) {
                return $fieldRoles[$role];
            }
            if (isset($schemaRoles[$role])) {
                return $schemaRoles[$role];
            }
        }
        return 'edit';
    }

    protected function userRoles(User $user): array
    {
        return $user->roles->pluck('slug')->toArray();
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
