<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\AppointmentWorkflow;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminAppointmentReviewController extends Controller
{
    public function __construct(
        protected AppointmentWorkflow $workflow,
        protected AvailabilityService $availability
    ) {}

    public function index()
    {
        $pendingAppointments = Appointment::with('client')
            ->where('status', Appointment::STATUS_PENDING_APPROVAL)
            ->where(function ($query) {
                // Include reschedule requests (have requested_starts_at)
                // OR new bookings (have starts_at but status is pending, requested_starts_at is null)
                $query->whereNotNull('requested_starts_at')
                      ->orWhereNull('requested_starts_at');
            })
            ->whereNull('suggested_starts_at') // Exclude appointments where we already proposed a time (waiting for client)
            // Order by requested_at for reschedules, or created_at for new bookings
            ->orderByRaw('COALESCE(requested_at, created_at) ASC')
            ->get();

        return Inertia::render('Admin/Appointments/Review', [
            'appointments' => $pendingAppointments,
        ]);
    }

    public function approve(Appointment $appointment)
    {
        $this->workflow->approveRequestedChange($appointment);

        return redirect()->back()->with('message', 'Appointment reschedule approved.');
    }

    public function rejectWithSuggestion(Request $request, Appointment $appointment)
    {
        $request->validate([
            'suggested_starts_at' => 'required|date|after:now',
            'note' => 'nullable|string',
        ]);

        $suggestedStart = Carbon::parse($request->suggested_starts_at);

        if (!$this->availability->isSlotAvailable($suggestedStart, $appointment->duration_minutes)) {
            return back()->withErrors(['suggested_starts_at' => 'This slot is no longer available.']);
        }

        $this->workflow->rejectWithSuggestion($appointment, $suggestedStart, $request->note);

        return redirect()->back()->with('message', 'Request rejected and alternative time proposed.');
    }
    public function availability(Request $request)
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
}
