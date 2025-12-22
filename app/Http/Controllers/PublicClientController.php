<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Client;
use App\Services\AppointmentWorkflow;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicClientController extends Controller
{
    public function __construct(
        protected AppointmentWorkflow $workflow,
        protected AvailabilityService $availability
    ) {}

    public function show(string $publicUid)
    {
        $client = Client::where('public_uid', $publicUid)->firstOrFail();

        $upcomingAppointments = $client->appointments()
            ->where('starts_at', '>', Carbon::now()->subHours(24))
            ->orderBy('starts_at', 'asc')
            ->get()
            ->map(fn ($appointment) => [
                'id' => $appointment->id,
                'starts_at' => $appointment->starts_at->timezone(config('app.timezone', 'UTC')),
                'duration_minutes' => $appointment->duration_minutes,
                'note' => $appointment->note,
                'status' => $appointment->status,
                'requested_starts_at' => $appointment->requested_starts_at?->timezone(config('app.timezone', 'UTC')),
                'suggested_starts_at' => $appointment->suggested_starts_at?->timezone(config('app.timezone', 'UTC')),
                'can_reschedule' => $appointment->status === Appointment::STATUS_CONFIRMED && now()->lt($appointment->starts_at->subHours(24)),
            ]);

        return Inertia::render('Public/Client/Show', [
            'client' => [
                'full_name' => $client->full_name,
                'public_uid' => $client->public_uid,
                'sms_opt_out' => $client->sms_opt_out,
            ],
            'appointments' => $upcomingAppointments,
        ]);
    }

    public function toggleOptOut(Request $request, string $publicUid)
    {
        $client = Client::where('public_uid', $publicUid)->firstOrFail();

        $newState = $request->has('opt_out') 
            ? $request->boolean('opt_out') 
            : !$client->sms_opt_out;

        $client->update([
            'sms_opt_out' => $newState,
        ]);

        return redirect()->back()->with('message', $client->sms_opt_out 
            ? 'SMS reminders have been disabled.' 
            : 'SMS reminders have been enabled.');
    }

    public function availability(Request $request, string $publicUid)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date',
            'duration' => 'required|integer|min:5',
        ]);

        $slots = $this->availability->getAvailableSlots(
            Carbon::parse($request->from),
            Carbon::parse($request->to),
            (int) $request->duration
        );

        return response()->json($slots);
    }

    public function requestReschedule(Request $request, string $publicUid, Appointment $appointment)
    {
        $client = Client::where('public_uid', $publicUid)->firstOrFail();
        
        if ($appointment->client_id !== $client->id) {
            abort(403);
        }

        if ($appointment->starts_at->subHours(24)->isPast()) {
            return back()->withErrors(['message' => 'Rescheduling is only allowed at least 24 hours before the appointment.']);
        }

        $request->validate([
            'new_start' => 'required|date|after:now',
        ]);

        $newStart = Carbon::parse($request->new_start);

        if (!$this->availability->isSlotAvailable($newStart, $appointment->duration_minutes)) {
            return back()->withErrors(['new_start' => 'This slot is no longer available.']);
        }

        $this->workflow->requestReschedule($appointment, $newStart);

        return redirect()->back()->with('message', 'Your reschedule request has been submitted for approval.');
    }

    public function acceptSuggestion(Request $request, string $publicUid, Appointment $appointment)
    {
        $client = Client::where('public_uid', $publicUid)->firstOrFail();
        
        if ($appointment->client_id !== $client->id) {
            abort(403);
        }

        if (!$appointment->suggested_starts_at) {
            abort(400, 'No suggested time to accept.');
        }

        // Re-check availability last second
        if (!$this->availability->isSlotAvailable($appointment->suggested_starts_at, $appointment->suggested_duration_minutes ?? $appointment->duration_minutes)) {
            return back()->withErrors(['message' => 'The proposed slot is no longer available. Please request a new time.']);
        }

        $this->workflow->clientAcceptSuggestion($appointment);

        return redirect()->back()->with('message', 'Appointment confirmed at the new time.');
    }

    public function rejectSuggestion(Request $request, string $publicUid, Appointment $appointment)
    {
        $client = Client::where('public_uid', $publicUid)->firstOrFail();
        
        if ($appointment->client_id !== $client->id) {
            abort(403);
        }

        $this->workflow->clientRejectSuggestion($appointment);

        return redirect()->back()->with('message', 'Suggestion rejected. You can request another time.');
    }

    public function cancelAppointment(Request $request, string $publicUid, Appointment $appointment)
    {
        $client = Client::where('public_uid', $publicUid)->firstOrFail();

        if ($appointment->client_id !== $client->id) {
            abort(403);
        }

        if ($appointment->starts_at->subHours(24)->isPast()) {
            return back()->withErrors(['message' => 'Cancellation is only allowed at least 24 hours before the appointment.']);
        }

        $this->workflow->cancelAppointment($appointment);

        return redirect()->back()->with('message', 'Appointment canceled successfully.');
    }
}
