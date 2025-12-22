<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Index', [
            'settings' => [
                'timezone' => Setting::get('timezone', 'UTC'),
                'sms_footer_note' => Setting::get('sms_footer_note', ''),
                'reminder_hours' => (int) Setting::get('reminder_hours', 24),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'timezone' => 'required|string|timezone:all',
            'sms_footer_note' => 'nullable|string|max:500',
            'reminder_hours' => 'required|integer|min:1|max:72',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->back()->with('message', 'Settings saved successfully!');
    }
}
