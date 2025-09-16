<?php

namespace App\Support;

class AbilityNormalizer
{
    public static function normalize(string $ability): string
    {
        $aliases = config('ability_aliases', []);

        return $aliases[$ability] ?? $ability;
    }

    public static function normalizeList(array $abilities): array
    {
        $normalized = array_map(
            fn ($ability) => is_string($ability) ? self::normalize($ability) : $ability,
            $abilities
        );

        $filtered = array_filter($normalized, fn ($ability) => is_string($ability) && $ability !== '');

        return array_values(array_unique($filtered));
    }

    public static function normalizeFeatureAbilityMap(array $featureAbilities): array
    {
        $normalized = [];

        foreach ($featureAbilities as $feature => $abilities) {
            $normalized[$feature] = self::normalizeList((array) $abilities);
        }

        return $normalized;
    }

    public static function expandRequestedAbilities(array $abilities): array
    {
        $expanded = [];

        foreach ($abilities as $ability) {
            if (! is_string($ability) || $ability === '') {
                continue;
            }

            $expanded[] = $ability;
            $normalized = self::normalize($ability);

            if ($normalized !== $ability) {
                $expanded[] = $normalized;
            }
        }

        return array_values(array_unique($expanded));
    }
}
