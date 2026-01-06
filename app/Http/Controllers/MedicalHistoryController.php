<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\MedicalHistory;
use Illuminate\Http\Request;

class MedicalHistoryController extends Controller
{
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'is_pregnant' => 'boolean',
            'has_epilepsy' => 'boolean',
            'has_thyroid_issues' => 'boolean',
            'has_cancer' => 'boolean',
            'has_herpes' => 'boolean',
            'has_botox' => 'boolean',
            'botox_last_date' => 'nullable|date',
            'has_fillers' => 'boolean',
            'fillers_last_date' => 'nullable|date',
            'has_threads' => 'boolean',
            'allergies' => 'nullable|string',
            'medications' => 'nullable|string',
            'additional_notes' => 'nullable|string',
        ]);

        // Clear date fields if corresponding boolean is false
        if (!$validated['has_botox']) {
            $validated['botox_last_date'] = null;
        }
        if (!$validated['has_fillers']) {
            $validated['fillers_last_date'] = null;
        }

        $client->medicalHistory()->updateOrCreate(
            ['client_id' => $client->id],
            $validated
        );

        return redirect()->back()
            ->with('success', 'Karta medyczna została zaktualizowana.');
    }
}
