<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClientFilter
{
    public static function resolve(Request $request, ?array $permittedIds = null): ?array
    {
        $hasClientId = $request->has('client_id');
        $hasClientIds = $request->has('client_ids');
        $ids = [];

        if ($hasClientId) {
            $value = $request->query('client_id');

            if (is_array($value)) {
                foreach ($value as $id) {
                    if ($id === null || $id === '') {
                        continue;
                    }

                    $ids[] = (int) $id;
                }
            } elseif ($value !== null && $value !== '') {
                $ids[] = (int) $value;
            }
        }

        if ($hasClientIds) {
            $value = $request->query('client_ids');

            if (is_string($value)) {
                $value = preg_split('/[,\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
            }

            if (is_array($value)) {
                foreach ($value as $id) {
                    if ($id === null || $id === '') {
                        continue;
                    }

                    $ids[] = (int) $id;
                }
            } elseif ($value !== null && $value !== '') {
                $ids[] = (int) $value;
            }
        }

        $ids = array_values(array_unique($ids));
        $hasFilterParams = $hasClientId || $hasClientIds;

        if ($permittedIds !== null) {
            $permittedIds = array_values(array_unique(array_map('intval', $permittedIds)));

            if ($hasFilterParams) {
                $ids = array_values(array_intersect($ids, $permittedIds));
            } else {
                $ids = $permittedIds;
            }
        }

        if (! $hasFilterParams && $permittedIds === null) {
            return null;
        }

        return $ids;
    }

    public static function apply(Builder $query, ?array $clientIds, string $column = 'client_id'): Builder
    {
        if ($clientIds === null) {
            return $query;
        }

        if ($clientIds === []) {
            return $query->whereRaw('1 = 0');
        }

        if (count($clientIds) === 1) {
            return $query->where($column, $clientIds[0]);
        }

        return $query->whereIn($column, $clientIds);
    }
}
