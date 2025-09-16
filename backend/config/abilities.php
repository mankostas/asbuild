<?php

// Central registry of ability codes used across the application.
// The SuperAdmin role is granted the "*" wildcard so new entries
// automatically apply to those users without additional changes.
//
// This file derives its values from config/feature_map.php so the
// feature map remains the single source of truth. When config is
// cached, the generated list is stored alongside the rest of the
// configuration for fast lookups.
$featureMap = require __DIR__.'/feature_map.php';

$abilities = [];

foreach ($featureMap as $definition) {
    if (! is_array($definition)) {
        continue;
    }

    $featureAbilities = $definition['abilities'] ?? [];

    if (! is_array($featureAbilities)) {
        continue;
    }

    $abilities = array_merge($abilities, $featureAbilities);
}

return array_values(array_unique($abilities));
