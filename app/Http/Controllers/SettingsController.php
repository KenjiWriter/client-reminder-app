<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return redirect()->route('settings.account');
    }

    public function editAccount()
    {
        return Inertia::render('Settings/Account');
    }

    public function editSms()
    {
        $settings = Setting::first();
        
        $envToken = config('sms.smsapi.token');
        $envSender = config('sms.from');

        return Inertia::render('Settings/Sms', [
            'settings' => $settings ? [
                'sms_api_token' => $settings->sms_api_token,
                'sms_sender_name' => $settings->sms_sender_name,
                'sms_send_time' => $settings->sms_send_time,
            ] : null,
            'has_env_token' => !empty($envToken),
            'env_sender_name' => $envSender,
        ]);
    }

    public function updateSms(Request $request)
    {
        $validated = $request->validate([
            'sms_api_token' => ['nullable', 'string', 'max:255'],
            'sms_sender_name' => ['nullable', 'string', 'max:255'],
            'sms_send_time' => ['nullable', 'date_format:H:i'],
        ]);

        $settings = Setting::first();

        if (!$settings) {
            // Create a default row if it doesn't exist. 
            // We use a dummy key to satisfy the unique constraint if it's still enforced and not nullable.
            // But looking at migration, key is unique string.
            // Let's assume we use a specific key for this global config row if we were using key-value.
            // But here we are using columns.
            // To avoid Unique violation on 'key', we give it a reserved key.
            $settings = Setting::create([
                'key' => 'sms_config', // specific key for this row
                'value' => null,
            ]);
        }

        $settings->update($validated);

        return back()->with('success', 'SMS settings updated successfully.');
    }

    public function editAppearance()
    {
        return Inertia::render('Settings/Appearance');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully!');
    }

    public function editGeneral()
    {
        $settings = Setting::first();

        return Inertia::render('Settings/General', [
            'settings' => $settings ? [
                'app_name' => $settings->app_name,
                'app_logo' => $settings->app_logo ? Storage::url($settings->app_logo) : null,
            ] : [
                'app_name' => config('app.name'),
                'app_logo' => null,
            ],
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'app_name' => ['nullable', 'string', 'max:255'],
            'app_logo' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,svg'],
        ]);

        // Use the first settings record (there should only be one)
        $settings = Setting::first();
        
        if (!$settings) {
            $settings = Setting::create([
                'key' => 'app_settings',
                'value' => null,
            ]);
        }

        // Handle logo upload
        if ($request->hasFile('app_logo')) {
            // Delete old logo if exists
            if ($settings->app_logo && Storage::disk('public')->exists($settings->app_logo)) {
                Storage::disk('public')->delete($settings->app_logo);
            }

            // Store new logo
            $path = $request->file('app_logo')->store('logos', 'public');
            $settings->app_logo = $path;
        }

        // Update app name
        if ($request->has('app_name')) {
            $settings->app_name = $validated['app_name'];
        }

        $settings->save();

        return back()->with('success', 'General settings updated successfully.');
    }

    public function editEmail()
    {
        $settings = Setting::first();

        return Inertia::render('Settings/Email', [
            'settings' => $settings ? [
                'email_sender_name' => $settings->email_sender_name,
                'imap_host' => $settings->imap_host,
                'imap_port' => $settings->imap_port,
                'imap_username' => $settings->imap_username,
                'imap_sent_folder' => $settings->imap_sent_folder,
                'email_signature' => $settings->email_signature,
                // Do not send password to frontend
                'has_imap_password' => !empty($settings->imap_password),
                'smtp_host' => $settings->smtp_host,
                'smtp_port' => $settings->smtp_port,
                'smtp_username' => $settings->smtp_username,
                'has_smtp_password' => !empty($settings->smtp_password),
            ] : null,
        ]);
    }

    public function updateEmail(Request $request)
    {
        $validated = $request->validate([
            'email_sender_name' => ['nullable', 'string', 'max:255'],
            
            'imap_host' => ['nullable', 'string', 'max:255'],
            'imap_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'imap_username' => ['nullable', 'string', 'max:255'],
            'imap_password' => ['nullable', 'string', 'max:255'],
            'imap_sent_folder' => ['nullable', 'string', 'max:255'],
            
            'email_signature' => ['nullable', 'string', 'max:5000'],

            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = Setting::first();
        if (!$settings) {
            $settings = Setting::create([
                'key' => 'email_settings',
                'value' => null,
            ]);
        }

        $updateData = collect($validated)->except(['imap_password', 'smtp_password'])->toArray();

        if (!empty($validated['imap_password'])) {
            $updateData['imap_password'] = \Illuminate\Support\Facades\Crypt::encryptString($validated['imap_password']);
        }
        
        if (!empty($validated['smtp_password'])) {
            $updateData['smtp_password'] = \Illuminate\Support\Facades\Crypt::encryptString($validated['smtp_password']);
        }

        $settings->update($updateData);

        // Immediately forget the cache so the badge syncs with the newly configured inbox
        \Illuminate\Support\Facades\Cache::forget('unread_emails_count');

        return back()->with('success', 'Ustawienia poczty zostały zapisane.');
    }
}
