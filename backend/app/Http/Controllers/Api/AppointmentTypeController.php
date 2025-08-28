<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppointmentType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentTypeController extends Controller
{
    protected function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('ClientAdmin') && ! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    public function index()
    {
        return response()->json(AppointmentType::all());
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);
        $data = $this->validateSchema($request);
        $type = AppointmentType::create($data);
        return response()->json($type, 201);
    }

    public function show(AppointmentType $appointmentType)
    {
        return response()->json($appointmentType);
    }

    public function update(Request $request, AppointmentType $appointmentType)
    {
        $this->ensureAdmin($request);
        $data = $this->validateSchema($request, false);
        $appointmentType->update($data);
        return response()->json($appointmentType);
    }

    public function destroy(Request $request, AppointmentType $appointmentType)
    {
        $this->ensureAdmin($request);
        $appointmentType->delete();
        return response()->json(['message' => 'deleted']);
    }

    protected function validateSchema(Request $request, bool $nameRequired = true): array
    {
        $rules = [
            'name' => ($nameRequired ? 'required' : 'sometimes') . '|string|max:255',
            'form_schema' => 'nullable|json',
            'fields_summary' => 'nullable|json',
            'statuses' => ($nameRequired ? 'required' : 'sometimes') . '|json',
        ];
        $validated = $request->validate($rules);

        foreach (['form_schema', 'fields_summary', 'statuses'] as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = json_decode($validated[$field], true);
            }
        }

        if (isset($validated['form_schema'])) {
            $schema = $validated['form_schema'];
            if (($schema['type'] ?? null) !== 'object' || ! is_array($schema['properties'] ?? null)) {
                throw ValidationException::withMessages([
                    'form_schema' => 'Schema must be an object with properties',
                ]);
            }
            if (isset($schema['required'])) {
                foreach ($schema['required'] as $field) {
                    if (! array_key_exists($field, $schema['properties'])) {
                        throw ValidationException::withMessages([
                            'form_schema' => "Required field {$field} missing from properties",
                        ]);
                    }
                }
            }
        }

        return $validated;
    }
}
