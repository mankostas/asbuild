<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Manual;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    protected function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('ClientAdmin') && ! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    protected function dateRange(Request $request): array
    {
        $range = $request->query('range');
        $from = $request->query('from');
        $to = $request->query('to');

        if ($range === 'today') {
            $from = Carbon::today();
            $to = Carbon::today()->endOfDay();
        } elseif ($range === '7') {
            $from = Carbon::today()->subDays(6);
            $to = Carbon::today()->endOfDay();
        } elseif ($range === '30') {
            $from = Carbon::today()->subDays(29);
            $to = Carbon::today()->endOfDay();
        } else {
            $from = $from ? Carbon::parse($from) : Carbon::today();
            $to = $to ? Carbon::parse($to) : Carbon::today();
            $from = $from->startOfDay();
            $to = $to->endOfDay();
        }

        return ['from' => $from, 'to' => $to];
    }

    public function overview(Request $request)
    {
        $this->ensureAdmin($request);
        $range = $this->dateRange($request);
        $tenantId = $request->user()->tenant_id;

        $base = Appointment::where('tenant_id', $tenantId)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$range['from'], $range['to']]);

        $completed = (clone $base)->count();
        $onTime = (clone $base)
            ->whereNotNull('sla_end_at')
            ->whereColumn('completed_at', '<=', 'sla_end_at')
            ->count();
        $onTimePercentage = $completed > 0 ? ($onTime / $completed) * 100 : 0;

        $avgDurationSeconds = (clone $base)
            ->whereNotNull('started_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg'))
            ->value('avg') ?? 0;
        $avgDurationMinutes = $avgDurationSeconds / 60;

        $failedUploads = DB::table('upload_chunks')->count();

        $kpis = [
            ['label' => 'Completed', 'value' => $completed],
            ['label' => 'On Time %', 'value' => round($onTimePercentage, 2)],
            ['label' => 'Avg Duration (min)', 'value' => round($avgDurationMinutes, 2)],
            ['label' => 'Failed Uploads', 'value' => $failedUploads],
        ];

        $chartData = Appointment::where('tenant_id', $tenantId)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$range['from'], $range['to']])
            ->select(DB::raw('DATE(completed_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['x' => $row->date, 'y' => $row->count]);

        return response()->json([
            'kpis' => $kpis,
            'chart' => [
                'title' => 'Completed Appointments',
                'type' => 'line',
                'series' => [
                    [
                        'label' => 'Appointments',
                        'data' => $chartData,
                    ],
                ],
            ],
        ]);
    }

    public function kpis(Request $request)
    {
        $this->ensureAdmin($request);
        $range = $this->dateRange($request);
        $tenantId = $request->user()->tenant_id;

        $base = Appointment::where('tenant_id', $tenantId)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$range['from'], $range['to']]);

        $completed = (clone $base)->count();
        $onTime = (clone $base)
            ->whereNotNull('sla_end_at')
            ->whereColumn('completed_at', '<=', 'sla_end_at')
            ->count();
        $onTimePercentage = $completed > 0 ? ($onTime / $completed) * 100 : 0;

        $avgDurationSeconds = (clone $base)
            ->whereNotNull('started_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg'))
            ->value('avg') ?? 0;
        $avgDurationMinutes = $avgDurationSeconds / 60;

        $failedUploads = DB::table('upload_chunks')->count();

        return response()->json([
            'completed' => $completed,
            'on_time_percentage' => round($onTimePercentage, 2),
            'avg_duration_minutes' => round($avgDurationMinutes, 2),
            'failed_uploads' => $failedUploads,
        ]);
    }

    public function materials(Request $request)
    {
        $this->ensureAdmin($request);
        $range = $this->dateRange($request);
        $tenantId = $request->user()->tenant_id;

        $materials = Manual::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$range['from'], $range['to']])
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get();

        return response()->json($materials);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureAdmin($request);
        $range = $this->dateRange($request);
        $tenantId = $request->user()->tenant_id;

        $appointments = Appointment::where('tenant_id', $tenantId)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$range['from'], $range['to']])
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="report.csv"',
        ];

        $callback = function () use ($appointments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Started At', 'Completed At', 'SLA End', 'On Time', 'Duration Minutes']);
            foreach ($appointments as $a) {
                $duration = ($a->started_at && $a->completed_at)
                    ? $a->completed_at->diffInMinutes($a->started_at)
                    : null;
                $onTime = ($a->sla_end_at && $a->completed_at)
                    ? ($a->completed_at->lte($a->sla_end_at) ? 'yes' : 'no')
                    : '';
                fputcsv($handle, [
                    $a->id,
                    optional($a->started_at)->toDateTimeString(),
                    optional($a->completed_at)->toDateTimeString(),
                    optional($a->sla_end_at)->toDateTimeString(),
                    $onTime,
                    $duration,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
