<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublicBookingController extends Controller
{
    public function __construct(
        protected AvailabilityService $availability
    ) {}

    public function availability(Request $request)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'required|integer|min:15',
        ]);

        $date = Carbon::parse($request->input('date'));
        $duration = (int) $request->input('duration');

        // Reuse availability service
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();
        
        $slots = $this->availability->getAvailableSlots($from, $to, $duration);

        return response()->json([
            'slots' => $slots,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            
            // Client details
            'first_visit' => 'required|boolean',
            'goals' => 'nullable|string',
            
            'full_name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            
            'terms_accepted' => 'required|accepted',
        ]);

        return DB::transaction(function () use ($validated) {
            $service = Service::find($validated['service_id']);
            
            // 1. Find or Create Client
            $client = Client::where('phone_e164', $validated['phone'])
                ->orWhere('email', $validated['email'])
                ->first();

            if (!$client) {
                $client = Client::create([
                    'full_name' => $validated['full_name'],
                    'phone_e164' => $validated['phone'],
                    'email' => $validated['email'],
                    'notes' => $validated['first_visit'] ? 'Wizyta pierwszorazowa.' : null,
                ]);
            } else {
                // Append notes if client exists and marked as first visit (unlikely but possible)
                if ($validated['first_visit']) {
                    $newNote = "\n" . date('Y-m-d') . ": Zgłoszono jako wizyta pierwszorazowa.";
                    $client->notes = $client->notes ? $client->notes . $newNote : $newNote;
                    $client->save();
                }
            }

            // 2. Create Appointment
            $startsAt = Carbon::parse($validated['date'] . ' ' . $validated['time']);
            
            $appointment = Appointment::create([
                'client_id' => $client->id,
                'service_id' => $service->id,
                'starts_at' => $startsAt,
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
                'status' => Appointment::STATUS_PENDING_APPROVAL, // Needs migration/model update if this status doesn't exist yet, but assuming it matches 'pending_approval' or similar
                'note' => $validated['goals'],
                'send_reminder' => true, 
            ]);

            // TODO: Trigger Notification (Email/SMS)

            return response()->json([
                'success' => true,
                'message' => 'Booking request received. We will confirm shortly.',
                'appointment_id' => $appointment->id
            ]);
        });
    }
}
