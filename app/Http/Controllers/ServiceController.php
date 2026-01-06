<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource (Settings page).
     */
    public function index(): Response
    {
        $services = Service::where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Settings/Services', [
            'services' => $services,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceRequest $request)
    {
        $service = Service::create($request->validated());

        // Return JSON for Quick Add feature
        if ($request->expectsJson()) {
            return response()->json($service, 201);
        }

        return redirect()->back()->with('success', 'Usługa została dodana pomyślnie.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceRequest $request, Service $service)
    {
        $service->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json($service);
        }

        return redirect()->back()->with('success', 'Usługa została zaktualizowana pomyślnie.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Service $service): RedirectResponse
    {
        // Soft delete - set is_active to false
        $service->update(['is_active' => false]);

        return redirect()->back()->with('success', 'Usługa została usunięta.');
    }
}
