<?php

namespace App\Services;

use App\Support\ClientFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TaskQueryFilters
{
    public function apply(Builder $query, Request $request, ?array $permittedClientIds = null): Builder
    {
        $clientFilter = ClientFilter::resolve($request, $permittedClientIds);
        ClientFilter::apply($query, $clientFilter);

        if ($request->boolean('mine')) {
            $query->where('assigned_user_id', $request->user()->id);
        }

        if ($assignee = $request->query('assignee_id')) {
            $query->where('assigned_user_id', $assignee);
        }

        if ($typeIds = $request->query('type_ids')) {
            $typeIds = is_array($typeIds) ? $typeIds : [$typeIds];
            $query->whereIn('task_type_id', $typeIds);
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        if ($q = $request->query('q')) {
            $query->where(function (Builder $b) use ($q) {
                $b->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($sla = $request->query('sla')) {
            $query->whereHas('slaEvents', function (Builder $b) use ($sla) {
                $b->where('kind', $sla);
            });
        }

        if ($createdFrom = $request->query('created_from')) {
            $query->whereDate('created_at', '>=', $createdFrom);
        }

        if ($createdTo = $request->query('created_to')) {
            $query->whereDate('created_at', '<=', $createdTo);
        }

        if ($dueFrom = $request->query('due_from')) {
            $query->whereDate('due_at', '>=', $dueFrom);
        }

        if ($dueTo = $request->query('due_to')) {
            $query->whereDate('due_at', '<=', $dueTo);
        }

        if ($request->boolean('breached_only')) {
            $query->whereNotNull('sla_end_at')->where('sla_end_at', '<', now());
        }

        if ($request->boolean('due_today')) {
            $query->whereDate('due_at', now()->toDateString());
        }

        if ($request->boolean('has_photos')) {
            $query->whereHas('attachments');
        }

        return $query;
    }
}

