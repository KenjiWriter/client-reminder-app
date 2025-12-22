<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicClientController extends Controller
{
    public function show(string $publicUid)
    {
        $client = Client::where('public_uid', $publicUid)->firstOrFail();

        $upcomingAppointments = $client->appointments()
            ->where('starts_at', '>', Carbon::now())
            ->orderBy('starts_at', 'asc')
            ->get()
            ->map(fn ($appointment) => [
                'id' => $appointment->id,
                'starts_at' => $appointment->starts_at->timezone(config('app.timezone', 'UTC')),
                'duration_minutes' => $appointment->duration_minutes,
                'note' => $appointment->note,
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

        $client->update([
            'sms_opt_out' => !$client->sms_opt_out,
        ]);

        return redirect()->back()->with('message', $client->sms_opt_out 
            ? 'SMS reminders have been disabled.' 
            : 'SMS reminders have been enabled.');
    }
}
