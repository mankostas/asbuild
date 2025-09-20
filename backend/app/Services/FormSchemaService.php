<?php

namespace App\Services;

use App\Http\Requests\Concerns\ResolvesPublicIds;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FormSchemaService
{
    use ResolvesPublicIds;

    protected function isI18nString($val): bool
    {
        return is_string($val) || (is_array($val) && (isset($val['en']) || isset($val['el'])));
    }

    protected function normalizeI18n($val): array
    {
        if (is_array($val)) {
            $en = $val['en'] ?? ($val['el'] ?? '');
            $el = $val['el'] ?? $en;
            return ['en' => $en, 'el' => $el];
        }
        return ['en' => (string) $val, 'el' => (string) $val];
    }

    public function normalizeSchema(array $schema): array
    {
        if (isset($schema['sections']) && is_array($schema['sections'])) {
            foreach ($schema['sections'] as &$section) {
                if (isset($section['label'])) {
                    $section['label'] = $this->normalizeI18n($section['label']);
                }
                if (isset($section['fields']) && is_array($section['fields'])) {
                    foreach ($section['fields'] as &$field) {
                        if (isset($field['label'])) {
                            $field['label'] = $this->normalizeI18n($field['label']);
                        }
                        if (isset($field['placeholder'])) {
                            $field['placeholder'] = $this->normalizeI18n($field['placeholder']);
                        }
                        if (isset($field['help'])) {
                            $field['help'] = $this->normalizeI18n($field['help']);
                        }
                    }
                }
                if (isset($section['photos']) && is_array($section['photos'])) {
                    foreach ($section['photos'] as &$photo) {
                        if (isset($photo['label'])) {
                            $photo['label'] = $this->normalizeI18n($photo['label']);
                        }
                        if (isset($photo['help'])) {
                            $photo['help'] = $this->normalizeI18n($photo['help']);
                        }
                    }
                }
            }
        }
        return $schema;
    }

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
            if (! isset($section['key'], $section['label']) || ! $this->isI18nString($section['label'])) {
                throw ValidationException::withMessages([
                    'schema_json' => 'section key/label required',
                ]);
            }
            foreach ($section['fields'] ?? [] as $field) {
                if (isset($field['key'])) {
                    if (isset($allKeys[$field['key']])) {
                        throw ValidationException::withMessages([
                            'schema_json' => 'duplicate field key',
                        ]);
                    }
                    $allKeys[$field['key']] = true;
                }
            }
            foreach ($section['photos'] ?? [] as $photo) {
                if (isset($photo['key'])) {
                    if (isset($allKeys[$photo['key']])) {
                        throw ValidationException::withMessages([
                            'schema_json' => 'duplicate field key',
                        ]);
                    }
                    $allKeys[$photo['key']] = true;
                }
            }
        }
        $keys = array_keys($allKeys);
        foreach ($schema['sections'] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $this->validateField($field, $keys);
            }
            foreach ($section['photos'] ?? [] as $photo) {
                $this->validatePhoto($photo, $keys);
            }
        }
    }

    protected function validateField(array $field, array $allKeys): void
    {
        $allowed = ['text','textarea','number','date','time','datetime','duration','email','phone','url','boolean','select','multiselect','radio','checkbox','chips','assignee','reviewer','file','photo_single','photo_repeater','repeater','richtext','markdown','divider','headline','lookup','computed','signature','location','rating','priority','status'];
        if (! isset($field['key'], $field['label']) || ! $this->isI18nString($field['label']) || ! in_array($field['type'] ?? '', $allowed, true)) {
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

        foreach (['placeholder', 'help'] as $attr) {
            if (isset($field[$attr]) && ! $this->isI18nString($field[$attr])) {
                throw ValidationException::withMessages([
                    'schema_json' => "$attr must be string or i18n object",
                ]);
            }
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

    protected function validatePhoto(array $photo, array $allKeys): void
    {
        if (! isset($photo['key'], $photo['label']) || ! $this->isI18nString($photo['label'])) {
            throw ValidationException::withMessages([
                'schema_json' => 'invalid photo field',
            ]);
        }
        if (! in_array($photo['type'] ?? '', ['photo_single', 'photo_repeater'], true)) {
            throw ValidationException::withMessages([
                'schema_json' => 'invalid photo field',
            ]);
        }
        if ($photo['type'] === 'photo_repeater') {
            if (! isset($photo['maxCount']) || ! is_int($photo['maxCount']) || $photo['maxCount'] < 1) {
                throw ValidationException::withMessages([
                    'schema_json' => 'maxCount must be >=1',
                ]);
            }
        }
        if (isset($photo['validations']) && ! is_array($photo['validations'])) {
            throw ValidationException::withMessages([
                'schema_json' => 'validations must be object',
            ]);
        }
        if (isset($photo['help']) && ! $this->isI18nString($photo['help'])) {
            throw ValidationException::withMessages([
                'schema_json' => 'help must be string or i18n object',
            ]);
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
            ->flatMap(fn ($s) => array_merge($s['fields'] ?? [], $s['photos'] ?? []));

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
            $type = $field['type'] ?? null;

            $hasShow = $showTargets->contains($key);
            $isVisible = ! $hasShow || $visible->contains($key);
            if (! $isVisible) {
                continue;
            }

            $present = array_key_exists($key, $data);
            $val = $present ? $data[$key] : null;

            $isRequired = ($rules['required'] ?? false) || $requiredOverride->contains($key);

            if ($type === 'photo_repeater') {
                if ($isRequired && (! $present || ! is_array($val) || count($val) === 0)) {
                    throw ValidationException::withMessages([
                        "form_data.$key" => 'required',
                    ]);
                }
                if (! $present) {
                    continue;
                }
                if (! is_array($val)) {
                    throw ValidationException::withMessages([
                        "form_data.$key" => 'invalid',
                    ]);
                }
                if (isset($field['maxCount']) && count($val) > $field['maxCount']) {
                    throw ValidationException::withMessages([
                        "form_data.$key" => 'max_count',
                    ]);
                }
                foreach ($val as $item) {
                    if (! is_array($item)) {
                        throw ValidationException::withMessages([
                            "form_data.$key" => 'invalid',
                        ]);
                    }
                    if (isset($rules['mime']) && is_array($rules['mime'])) {
                        if (! isset($item['mime']) || ! in_array($item['mime'], $rules['mime'], true)) {
                            throw ValidationException::withMessages([
                                "form_data.$key" => 'mime',
                            ]);
                        }
                    }
                    if (isset($rules['size'])) {
                        if (! isset($item['size']) || $item['size'] > $rules['size']) {
                            throw ValidationException::withMessages([
                                "form_data.$key" => 'size',
                            ]);
                        }
                    }
                }
                continue;
            }

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
        if ($d && $d->format('Y-m-d') === $value) {
            return true;
        }

        try {
            $d = Carbon::parse($value);
            return $d->toDateString() === $value;
        } catch (\Exception) {
            return false;
        }
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
     * Map assignee data from payload to assigned_user_id.
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
        [$value, $source] = $this->pullSchemaValue($payload, $key);

        if ($source === null) {
            return;
        }

        $id = is_array($value) ? ($value['id'] ?? null) : $value;
        $errorKey = $source === 'form_data' ? "form_data.$key" : 'assignee';

        if ($id === null || $id === '') {
            throw ValidationException::withMessages([
                is_array($value) ? "$errorKey.id" : $errorKey => 'required',
            ]);
        }

        $resolvedId = $this->resolvePublicId(User::class, $id);

        if ($resolvedId === null) {
            throw ValidationException::withMessages([
                is_array($value) ? "$errorKey.id" : $errorKey => 'invalid',
            ]);
        }

        $payload['assigned_user_id'] = $resolvedId;
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
        [$value, $source] = $this->pullSchemaValue($payload, $key);

        if ($source === null) {
            return;
        }

        $kind = is_array($value) ? ($value['kind'] ?? null) : null;
        $identifier = is_array($value) ? ($value['id'] ?? null) : $value;
        $errorBase = $source === 'form_data' ? "form_data.$key" : 'reviewer';

        $modelClass = match ($kind) {
            'team' => Team::class,
            'employee' => User::class,
            default => null,
        };

        if ($modelClass === null) {
            throw ValidationException::withMessages([
                is_array($value) ? "$errorBase.kind" : $errorBase => 'invalid',
            ]);
        }

        if ($identifier === null || $identifier === '') {
            throw ValidationException::withMessages([
                is_array($value) ? "$errorBase.id" : $errorBase => 'required',
            ]);
        }

        $resolvedId = $this->resolvePublicId($modelClass, $identifier);

        if ($resolvedId === null) {
            throw ValidationException::withMessages([
                is_array($value) ? "$errorBase.id" : $errorBase => 'invalid',
            ]);
        }

        $payload['reviewer_type'] = $modelClass;
        $payload['reviewer_id'] = $resolvedId;
    }

    /**
     * Extract a schema field value from the payload.
     *
     * @return array{0:mixed,1:string|null}
     */
    protected function pullSchemaValue(array &$payload, string $fieldKey): array
    {
        if (isset($payload[$fieldKey])) {
            $value = $payload[$fieldKey];
            unset($payload[$fieldKey]);

            return [$value, 'root'];
        }

        if (isset($payload['form_data']) && is_array($payload['form_data']) && isset($payload['form_data'][$fieldKey])) {
            $value = $payload['form_data'][$fieldKey];
            unset($payload['form_data'][$fieldKey]);

            return [$value, 'form_data'];
        }

        return [null, null];
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

            $photos = [];
            foreach ($section['photos'] ?? [] as $photo) {
                $access = $this->resolveAccess($photo['roles'] ?? [], $defaults, $roles);
                if ($access === 'hidden') {
                    continue;
                }
                if ($access === 'read') {
                    $photo['readOnly'] = true;
                }
                $photos[] = $photo;
            }

            if ($fields || $photos) {
                if ($fields) {
                    $section['fields'] = $fields;
                } else {
                    unset($section['fields']);
                }
                if ($photos) {
                    $section['photos'] = $photos;
                } else {
                    unset($section['photos']);
                }
                $sections[] = $section;
            }
        }

        $schema['sections'] = $sections;
        return $this->normalizeSchema($schema);
    }

    /**
     * Filter data payload removing hidden fields.
     */
    public function filterDataForRoles(array $schema, array $data, User $user): array
    {
        $roles = $this->userRoles($user);
        $defaults = $schema['roles'] ?? [];

        foreach ($schema['sections'] ?? [] as $section) {
            foreach (array_merge($section['fields'] ?? [], $section['photos'] ?? []) as $field) {
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
            foreach (array_merge($section['fields'] ?? [], $section['photos'] ?? []) as $field) {
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
