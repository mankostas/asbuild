<?php

namespace App\Http\Resources\Concerns;

use DateTimeInterface;

trait FormatsDateTimes
{
    protected function formatDates(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if ($value instanceof DateTimeInterface) {
                $value = $value->toIso8601String();
            }
        });

        return $data;
    }
}
