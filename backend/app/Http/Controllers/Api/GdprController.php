<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\DeleteUserData;
use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\Tenant;
use Illuminate\Http\Request;
use ZipArchive;

class GdprController extends Controller
{
    public function export(Request $request)
    {
        $user = $request->user();
        $zip = new ZipArchive();
        $temp = tempnam(sys_get_temp_dir(), 'gdpr');
        $zip->open($temp, ZipArchive::CREATE);
        $zip->addFromString('user.json', json_encode($user->toArray()));
        $tenant = Tenant::find($user->tenant_id);
        if ($tenant) {
            $zip->addFromString('tenant.json', json_encode($tenant->toArray()));
        }
        $zip->close();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'gdpr_export',
            'meta' => null,
        ]);

        return response()->streamDownload(function () use ($temp) {
            $stream = fopen($temp, 'rb');
            fpassthru($stream);
        }, 'export.zip')->deleteFileAfterSend(true);
    }

    public function consents(Request $request)
    {
        $user = $request->user();
        $consents = Consent::where('user_id', $user->id)->get()->map(function ($c) {
            return ['name' => $c->name, 'granted' => $c->granted_at !== null];
        });
        return response()->json($consents);
    }

    public function updateConsents(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            '*.name' => 'required|string',
            '*.granted' => 'boolean',
        ]);
        foreach ($data as $consent) {
            Consent::updateOrCreate(
                ['user_id' => $user->id, 'name' => $consent['name']],
                ['granted_at' => $consent['granted'] ? now() : null]
            );
        }
        return response()->json(['status' => 'ok']);
    }

    public function requestDelete(Request $request)
    {
        $user = $request->user();
        DeleteUserData::dispatch($user->id);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'gdpr_delete_request',
            'meta' => null,
        ]);
        return response()->json(['status' => 'queued']);
    }
}
