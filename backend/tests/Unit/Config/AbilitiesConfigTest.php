<?php

namespace Tests\Unit\Config;

use Tests\TestCase;

class AbilitiesConfigTest extends TestCase
{
    public function test_feature_map_drives_abilities_list(): void
    {
        $featureMap = config('feature_map');
        $expected = [];

        foreach ($featureMap as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $featureAbilities = $definition['abilities'] ?? [];

            if (! is_array($featureAbilities)) {
                continue;
            }

            foreach ($featureAbilities as $ability) {
                $expected[$ability] = true;
            }
        }

        $expected = array_keys($expected);
        sort($expected);

        $configured = array_values(array_unique(config('abilities')));
        sort($configured);

        $this->assertSame(
            $expected,
            $configured,
            'config/abilities.php should match the abilities declared in config/feature_map.php.'
        );
    }
}
