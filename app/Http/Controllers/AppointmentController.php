<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Services\AvailabilityService;

class AppointmentController extends Controller
{
    public function __construct(
        protected AvailabilityService $availability
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Simple range filtering for calendar view
        $start = $request->input('start') ? Carbon::parse($request->input('start')) : Carbon::today()->startOfWeek();
        $end = $request->input('end') ? Carbon::parse($request->input('end')) : Carbon::today()->endOfWeek();

        $events = Appointment::with('client')
            ->whereBetween('starts_at', [$start, $end])
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'title' => $appointment->client->full_name,
                    'start' => $appointment->starts_at->toIso8601String(),
                    'end' => $appointment->starts_at->addMinutes($appointment->duration_minutes)->toIso8601String(),
                    'client_id' => $appointment->client_id,
                    'duration_minutes' => $appointment->duration_minutes,
                    'note' => $appointment->note,
                    'send_reminder' => $appointment->send_reminder,
                ];
            });

        return Inertia::render('Calendar/Index', [
            'events' => $events,
            // Pass clients for the "Add Appointment" modal/quick-add
            'clients' => Client::select('id', 'full_name', 'phone_e164')->orderBy('full_name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAppointmentRequest $request)
    {
        $validated = $request->validated();
        $startsAt = Carbon::parse($validated['starts_at']);

        if (!$this->availability->isSlotAvailable($startsAt, $validated['duration_minutes'])) {
            return back()->withErrors(['starts_at' => 'This slot is already booked.']);
        }

        Appointment::create($validated);

        return redirect()->back()
            ->with('success', 'Appointment scheduled.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $validated = $request->validated();
        $startsAt = Carbon::parse($validated['starts_at']);

        // Only check availability if time or duration changed
        if ($appointment->starts_at->ne($startsAt) || $appointment->duration_minutes != $validated['duration_minutes']) {
            if (!$this->availability->isSlotAvailable($startsAt, $validated['duration_minutes'])) {
                return back()->withErrors(['starts_at' => 'This slot is already booked.']);
            }
        }

        $appointment->update($validated);

        return redirect()->back()
            ->with('success', 'Appointment updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return redirect()->back()
            ->with('success', 'Appointment cancelled.');
    }
}
