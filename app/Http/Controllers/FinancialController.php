<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinancialController extends Controller
{
    /**
     * Display a listing of unpaid past appointments.
     */
    public function index()
    {
        $unpaidAppointments = Appointment::with(['client', 'service'])
            ->where('is_paid', false)
            ->where('starts_at', '<', now())
            ->whereIn('status', [Appointment::STATUS_CONFIRMED, Appointment::STATUS_PENDING_APPROVAL]) // Exclude cancelled
            ->orderBy('starts_at', 'desc')
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'client_name' => $appointment->client->full_name,
                    'service_name' => $appointment->service ? $appointment->service->name : 'N/A',
                    'date' => $appointment->starts_at->format('Y-m-d H:i'),
                    'price' => $appointment->price ?? ($appointment->service ? $appointment->service->price : 0),
                    'duration_minutes' => $appointment->duration_minutes,
                ];
            });

        $totalOutstanding = $unpaidAppointments->sum('price');

        return Inertia::render('Financial/Index', [
            'unpaidAppointments' => $unpaidAppointments,
            'totalOutstanding' => $totalOutstanding,
        ]);
    }
}
