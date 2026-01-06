<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicalConditionTypeRequest;
use App\Http\Requests\UpdateMedicalConditionTypeRequest;
use App\Models\MedicalConditionType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MedicalConditionTypeController extends Controller
{
    /**
     * Display a listing of the resource (Settings page).
     */
    public function index()
    {
        $conditionTypes = MedicalConditionType::orderBy('category')
            ->orderBy('name')
            ->get();

        return Inertia::render('Settings/Medical', [
            'conditionTypes' => $conditionTypes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * IMPORTANT: Returns JSON for Quick Add feature.
     */
    public function store(StoreMedicalConditionTypeRequest $request)
    {
        $conditionType = MedicalConditionType::create($request->validated());

        // For Quick Add feature: return JSON with the created object
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'conditionType' => $conditionType,
                'message' => 'Dodano nowy typ schorzenia',
            ], 201);
        }

        return redirect()->route('settings.medical')
            ->with('success', 'Typ schorzenia został dodany.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicalConditionTypeRequest $request, MedicalConditionType $medicalConditionType)
    {
        $medicalConditionType->update($request->validated());

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'conditionType' => $medicalConditionType,
                'message' => 'Zaktualizowano typ schorzenia',
            ]);
        }

        return redirect()->route('settings.medical')
            ->with('success', 'Typ schorzenia został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     * Soft delete via is_active flag.
     */
    public function destroy(MedicalConditionType $medicalConditionType)
    {
        // Soft delete by setting is_active to false
        $medicalConditionType->update(['is_active' => false]);

        return redirect()->route('settings.medical')
            ->with('success', 'Typ schorzenia został usunięty.');
    }
}
