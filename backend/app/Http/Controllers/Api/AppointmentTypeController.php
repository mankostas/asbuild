<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppointmentType;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentTypeController extends Controller
{
    public function index()
    {
        return response()->json(AppointmentType::all());
    }

    public function store(Request $request)
    {
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
        $data = $this->validateSchema($request);
        $appointmentType->update($data);
        return response()->json($appointmentType);
    }

    public function destroy(AppointmentType $appointmentType)
    {
        $appointmentType->delete();
        return response()->json(['message' => 'deleted']);
    }

    protected function validateSchema(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'form_schema' => 'nullable|json',
            'fields_summary' => 'nullable|json',
        ]);

        foreach (['form_schema', 'fields_summary'] as $field) {
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
