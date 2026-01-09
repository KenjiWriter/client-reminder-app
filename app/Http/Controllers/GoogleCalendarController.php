<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GoogleCalendarController extends Controller
{
    protected GoogleCalendarService $googleService;

    public function __construct(GoogleCalendarService $googleService)
    {
        $this->googleService = $googleService;
    }

    public function connect()
    {
        try {
            return Inertia::location($this->googleService->getAuthUrl());
        } catch (\Exception $e) {
            return redirect()->route('settings.integrations')->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            return redirect()->route('settings.integrations')->with('error', 'Google authorization failed.');
        }

        try {
            $this->googleService->fetchAccessTokenWithAuthCode($request->code, Auth::user());
            return redirect()->route('settings.integrations')->with('success', 'Google Calendar connected successfully.');
        } catch (\Exception $e) {
            return redirect()->route('settings.integrations')->with('error', 'Failed to connect Google Calendar: ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        $user = Auth::user();
        $user->update([
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
            'google_calendar_email' => null,
        ]);

        return back()->with('success', 'Google Calendar disconnected.');
    }

    public function sync()
    {
        // 1. Push local changes to Google
        \App\Jobs\SyncAllFutureAppointments::dispatch();
        
        // 2. Pull changes from Google
        // We can chain this or just dispatch separately
        \App\Jobs\ImportFromGoogleCalendar::dispatch();

        return back()->with('success', __('common.success'));
    }
}
