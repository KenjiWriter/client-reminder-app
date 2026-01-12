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
    public function search(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json([]);
        }

        $appointments = Appointment::with('client')
            ->whereHas('client', function ($q) use ($query) {
                $q->where('full_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone_e164', 'like', "%{$query}%");
            })
            ->orderBy('starts_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'start_time' => $appointment->starts_at->toIso8601String(),
                    'starts_at_formatted' => $appointment->starts_at->format('Y-m-d H:i'),
                    'client' => [
                        'name' => $appointment->client->full_name,
                        'email' => $appointment->client->email,
                        'phone' => $appointment->client->phone_e164,
                    ],
                ];
            });

        return response()->json($appointments);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get date range from request, or default to current month ± 2 weeks for better coverage
        if ($request->has('start') && $request->has('end')) {
            $start = Carbon::parse($request->input('start'));
            $end = Carbon::parse($request->input('end'));
        } else {
            // Default: show current month + padding
            $start = Carbon::now()->startOfMonth()->subWeeks(1);
            $end = Carbon::now()->endOfMonth()->addWeeks(1);
        }

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
                    'service_id' => $appointment->service_id,
                    'duration_minutes' => $appointment->duration_minutes,
                    'note' => $appointment->note,
                    'send_reminder' => $appointment->send_reminder,
                    'is_paid' => $appointment->is_paid,
                    'payment_method' => $appointment->payment_method,
                    'price' => $appointment->price,
                    'payment_date' => $appointment->payment_date,
                ];
            });

        return Inertia::render('Calendar/Index', [
            'events' => $events,
            // Pass clients for the "Add Appointment" modal/quick-add
            'clients' => Client::select('id', 'full_name', 'phone_e164')->orderBy('full_name')->get(),
            // Pass active services for service selection
            'allServices' => \App\Models\Service::where('is_active', true)->orderBy('name')->get(),
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

        // Auto-fill price from service if not provided but service is selected
        if (!isset($validated['price']) && !empty($validated['service_id'])) {
             $service = \App\Models\Service::find($validated['service_id']);
             if ($service) {
                 $validated['price'] = $service->price;
             }
        }
        
        // Auto-fill payment date if paid but not set
        if (!empty($validated['is_paid']) && empty($validated['payment_date'])) {
            $validated['payment_date'] = now();
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
            if (!$this->availability->isSlotAvailable($startsAt, $validated['duration_minutes'], $appointment->id)) {
                return back()->withErrors(['starts_at' => 'This slot is already booked.']);
            }
        }

        $appointment->update($validated);
        
        // Post-update logic: if paid just now and no date/price, fill them? 
        // Actually, better to do it before update to save clean data.
        
        // Auto-fill price from service if not provided/null but service is selected AND (we are marking as paid OR just creating consistency)
        // Let's rely on frontend sending price, but fallback if needed.
        if (empty($validated['price']) && !empty($validated['service_id']) && isset($validated['is_paid']) && $validated['is_paid']) {
             // Only auto-fill if we are marking as paid and price is missing.
             // But if user explicitly cleared price, we might not want this? 
             // Let's stick to: if price is missing in request but service is there, take from service.
             
             // Check if price was NOT in request (meaning null)
             if (!array_key_exists('price', $validated) || $validated['price'] === null) {
                  $service = \App\Models\Service::find($validated['service_id']);
                  if ($service) { // && !$appointment->price -- overwrite or not? Let's overwrite so it matches service.
                      $appointment->price = $service->price;
                      $appointment->save();
                  }
             }
        }
        
        // Auto-fill payment date
        if ($appointment->is_paid && !$appointment->payment_date) {
            $appointment->payment_date = now();
            $appointment->save();
        }

        return redirect()->back()
            ->with('success', 'Appointment updated.');
    }

    /**
     * Update payment status.
     */
    public function updatePayment(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'is_paid' => 'required|boolean',
            'payment_method' => 'nullable|string|in:cash,card,transfer',
            'payment_date' => 'nullable|date',
            'price' => 'nullable|numeric|min:0',
        ]);

        $appointment->update($validated);

        return back()->with('success', 'Payment status updated.');
    }

    /**
     * Quick update for Drag & Drop / Resize interactions.
     * 
     * @param Request $request
     * @param Appointment $appointment
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickUpdate(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'starts_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15',
        ]);

        $startsAt = Carbon::parse($validated['starts_at']);
        $duration = (int) $validated['duration_minutes'];

        // Check availability
        // We must exclude the current appointment from the overlap check
        if (!$this->availability->isSlotAvailable($startsAt, $duration, $appointment->id)) {
            return response()->json([
                'message' => 'This slot is already booked.',
                'code' => 'OVERLAP_DETECTED'
            ], 422);
        }

        $appointment->update([
            'starts_at' => $startsAt,
            'duration_minutes' => $duration
        ]);

        return response()->json([
            'success' => true,
            'appointment' => [
                'id' => $appointment->id,
                'start' => $appointment->starts_at->toIso8601String(),
                'end' => $appointment->starts_at->addMinutes($appointment->duration_minutes)->toIso8601String(),
                'duration_minutes' => $appointment->duration_minutes,
            ]
        ]);
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
