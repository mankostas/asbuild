<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\AbilityNormalizer;

class TenantUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $featureSlugs = config('features');

        $rules = [
            'name' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string'],
            'quota_storage_mb' => ['integer'],
            'features' => ['array'],
            'features.*' => ['string', Rule::in($featureSlugs)],
            'feature_abilities' => ['array'],
            'feature_abilities.*' => ['array'],
            'feature_abilities.*.*' => ['string'],
            'notify_owner' => array_filter([
                $this->isMethod('POST') ? null : 'sometimes',
                'boolean',
            ]),
            'phone' => array_filter([
                $this->isMethod('POST') ? null : 'sometimes',
                'nullable',
                'string',
            ]),
            'address' => array_filter([
                $this->isMethod('POST') ? null : 'sometimes',
                'nullable',
                'string',
            ]),
        ];

        if ($this->isMethod('POST')) {
            $rules['user_name'] = ['required', 'string'];
            $rules['user_email'] = ['required', 'email'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $notifyOwner = $this->input('notify_owner');

        if ($notifyOwner === null && $this->isMethod('POST')) {
            $this->merge(['notify_owner' => true]);

            return;
        }

        if ($notifyOwner !== null) {
            $this->merge([
                'notify_owner' => filter_var($notifyOwner, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $notifyOwner,
            ]);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $features = $this->input('features', []);
            $featureAbilities = $this->input('feature_abilities', []);
            $featureMap = config('feature_map');

            foreach ($featureAbilities as $feature => $abilities) {
                if (! in_array($feature, $features, true)) {
                    $validator->errors()->add("feature_abilities.$feature", 'Abilities provided for unselected feature.');
                    continue;
                }

                $allowed = $featureMap[$feature]['abilities'] ?? [];
                foreach ((array) $abilities as $ability) {
                    $normalized = AbilityNormalizer::normalize((string) $ability);
                    if (! in_array($normalized, $allowed, true)) {
                        $validator->errors()->add("feature_abilities.$feature", "The ability $ability is not allowed.");
                    }
                }
            }
        });
    }

    protected function passedValidation(): void
    {
        $features = $this->input('features', []);
        $featureMap = config('feature_map');
        $sanitizedFeatures = array_values(array_intersect($features, config('features')));

        $sanitizedAbilities = [];
        $featureAbilities = $this->input('feature_abilities', []);
        foreach ($featureAbilities as $feature => $abilities) {
            if (! in_array($feature, $sanitizedFeatures, true)) {
                continue;
            }
            $allowed = $featureMap[$feature]['abilities'] ?? [];
            $normalizedAbilities = AbilityNormalizer::normalizeList((array) $abilities);
            $sanitized = array_values(array_intersect($normalizedAbilities, $allowed));
            if ($sanitized) {
                $sanitizedAbilities[$feature] = $sanitized;
            }
        }

        $this->merge([
            'features' => $sanitizedFeatures,
            'feature_abilities' => $sanitizedAbilities,
        ]);
    }
}

