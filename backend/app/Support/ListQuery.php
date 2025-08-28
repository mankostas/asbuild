<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

trait ListQuery
{
    protected function listQuery(Builder|Relation $query, Request $request, array $searchable = [], array $sortable = []): array
    {
        if ($query instanceof Relation) {
            $query = $query->getQuery();
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($searchable, $search) {
                foreach ($searchable as $column) {
                    $q->orWhere($column, 'like', '%' . $search . '%');
                }
            });
        }

        $sort = $request->query('sort');
        $dir = $request->query('dir', 'asc');
        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $dir === 'desc' ? 'desc' : 'asc');
        }

        $perPage = (int) $request->query('per_page', 15);
        $page = (int) $request->query('page', 1);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->items(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
