<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ImapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class EmailController extends Controller
{
    public function __construct(private ImapService $imapService) {}

    /**
     * Display the paginated inbox.
     */
    public function index(Request $request)
    {
        // Clear unread count cache on inbox load so it consistently fetches fresh data
        Cache::forget('unread_emails_count');
        
        $page    = (int) $request->query('page', 1);
        $perPage = 15;
        $folder  = $request->query('folder', 'INBOX');

        $result = $this->imapService->getFolderMessages($folder, $page, $perPage);

        return Inertia::render('Emails/Index', [
            'emails'       => $result['emails'] ?? [],
            'current_page' => $result['current_page'] ?? 1,
            'last_page'    => $result['last_page'] ?? 1,
            'total'        => $result['total'] ?? 0,
            'per_page'     => $result['per_page'] ?? 15,
            'imapError'    => $result['error'] ?? null,
            'currentFolder'=> $folder,
        ]);
    }

    /**
     * Configure the generic SMTP driver dynamically.
     * Returns false if configuration is invalid or missing.
     */
    private function configureSmtp(): bool
    {
        $settings = \App\Models\Setting::first();
        if (!$settings || empty($settings->smtp_host) || empty($settings->smtp_username) || empty($settings->smtp_password) || empty($settings->smtp_port)) {
            return false;
        }

        try {
            $password = \Illuminate\Support\Facades\Crypt::decryptString($settings->smtp_password);
        } catch (\Throwable $e) {
            return false;
        }

        config([
            'mail.mailers.smtp.host' => $settings->smtp_host,
            'mail.mailers.smtp.port' => $settings->smtp_port,
            'mail.mailers.smtp.username' => $settings->smtp_username,
            'mail.mailers.smtp.password' => $password,
            'mail.from.address' => $settings->smtp_username,
            'mail.from.name' => $settings->email_sender_name ?: config('app.name'),
        ]);

        return true;
    }

    /**
     * Show the form for composing a new email.
     */
    public function create()
    {
        $settings = \App\Models\Setting::first();
        // [DEBUG] Verify signature is loaded from DB — remove after fix confirmed
        Log::info('[EmailController@create] email_signature', [
            'value' => $settings?->email_signature ?? '(NULL/EMPTY)',
            'settings_id' => $settings?->id,
        ]);
        return Inertia::render('Emails/Create', [
            'email_signature' => $settings?->email_signature ?? '',
        ]);
    }

    /**
     * Send a new email via SMTP.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'to'      => ['required', 'string'], // Comma-separated
            'cc'      => ['nullable', 'string'], // Comma-separated
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string', 'max:20000'], // HTML body from Quill
            'attachments.*' => ['nullable', 'file', 'max:10240'], // 10MB max per file
        ]);

        if (!$this->configureSmtp()) {
            Log::error('[EmailController@store] SMTP configuration failed');
            return back()->withInput()->with('error', __('email.errors.smtp_not_configured'));
        }

        Log::info('[EmailController@store] Validated data', ['body_size' => strlen($validated['body'])]);

        // Parse recipients
        $toArray = array_filter(array_map('trim', explode(',', $validated['to'])));
        $ccArray = empty($validated['cc']) ? [] : array_filter(array_map('trim', explode(',', $validated['cc'])));

        // Basic sanity check for valid emails
        foreach (array_merge($toArray, $ccArray) as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->withInput()->withErrors(['to' => __('email.errors.invalid_format', ['email' => $email])]);
            }
        }

        if (empty($toArray)) {
            return back()->withInput()->withErrors(['to' => __('email.errors.to_required')]);
        }



        try {
            Mail::send([], [], function ($message) use ($request, $validated, $toArray, $ccArray) {
                $message->to($toArray)
                    ->subject($validated['subject'])
                    ->html($validated['body']);

                if (!empty($ccArray)) {
                    $message->cc($ccArray);
                }

                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        $message->attach($file->getRealPath(), [
                            'as' => $file->getClientOriginalName(),
                            'mime' => $file->getClientMimeType(),
                        ]);
                    }
                }
            });

            session()->flash('success', __('email.messages.sent_success'));
            return redirect()->route('emails.index');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('SMTP send failed', [
                'to'    => $validated['to'],
                'error' => $e->getMessage(),
            ]);
            
            return back()->withInput()->with('error', __('email.errors.send_failed'));
        }
    }

    /**
     * Show a single email and mark it as read.
     * Clears the unread cache and immediately updates the shared badge count
     * so the response the user receives already contains the updated badge.
     */
    public function show(Request $request, string $uid)
    {
        $folder = $request->query('folder', 'INBOX');
        $result = $this->imapService->getMessage($uid, $folder);

        if (isset($result['error']) && $result['error'] !== null) {
            session()->flash('error', $result['error']);

            return redirect()->route('emails.index');
        }

        // Flush cached unread count so it's recalculated fresh.
        Cache::forget('unread_emails_count');

        // Immediately recalculate and override the shared Inertia prop for
        // this response – the middleware already ran with the old cached value.
        $freshCount = $this->imapService->getUnreadCount();
        Cache::put('unread_emails_count', $freshCount, 300);
        Inertia::share('unread_emails_count', $freshCount);

        $settings = \App\Models\Setting::first();
        // [DEBUG] Verify signature is loaded from DB — remove after fix confirmed
        Log::info('[EmailController@show] email_signature', [
            'value' => $settings?->email_signature ?? '(NULL/EMPTY)',
            'settings_id' => $settings?->id,
        ]);

        return Inertia::render('Emails/Show', [
            'email' => $result,
            'email_signature' => $settings?->email_signature ?? '',
        ]);
    }

    /**
     * Send an email reply via SMTP.
     * Uses the original Message-ID for correct conversation threading.
     */
    public function reply(Request $request, string $uid)
    {
        $validated = $request->validate([
            'to'      => ['required', 'string'], // Comma-separated
            'cc'      => ['nullable', 'string'], // Comma-separated
            'subject' => ['required', 'string', 'max:500'],
            'message_id' => ['nullable', 'string', 'max:500'],
            'body'    => ['required', 'string', 'max:20000'], // HTML body from Quill
            'attachments.*' => ['nullable', 'file', 'max:10240'], // 10MB max per file
        ]);

        $replySubject   = str_starts_with(strtolower($validated['subject']), 're:')
            ? $validated['subject']
            : 'Re: ' . $validated['subject'];

        $originalMsgId  = $validated['message_id'] ?? '';

        if (!$this->configureSmtp()) {
            Log::error('[EmailController@reply] SMTP configuration failed');
            return back()->withInput()->with('error', __('email.errors.smtp_not_configured'));
        }

        Log::info('[EmailController@reply] Validated data', [
            'to' => $validated['to'],
            'body_size' => strlen($validated['body']),
        ]);

        // Parse recipients
        $toArray = array_filter(array_map('trim', explode(',', $validated['to'])));
        $ccArray = empty($validated['cc']) ? [] : array_filter(array_map('trim', explode(',', $validated['cc'])));

        // Basic sanity check for valid emails
        foreach (array_merge($toArray, $ccArray) as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->withInput()->withErrors(['to' => __('email.errors.invalid_format', ['email' => $email])]);
            }
        }

        if (empty($toArray)) {
            return back()->withInput()->withErrors(['to' => __('email.errors.to_required')]);
        }



        try {
            Mail::send([], [], function ($message) use ($request, $validated, $toArray, $ccArray, $replySubject, $originalMsgId) {
                Log::info('[EmailController@reply] Sending mail closure start');
                $message->to($toArray)
                    ->subject($replySubject)
                    ->html($validated['body']);

                if (!empty($ccArray)) {
                    $message->cc($ccArray);
                }

                if ($originalMsgId) {
                    $message->getHeaders()
                        ->addTextHeader('In-Reply-To', $originalMsgId)
                        ->addTextHeader('References', $originalMsgId);
                }

                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        $message->attach($file->getRealPath(), [
                            'as' => $file->getClientOriginalName(),
                            'mime' => $file->getClientMimeType(),
                        ]);
                    }
                }
            });

            session()->flash('success', __('email.messages.reply_sent'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('SMTP reply failed', [
                'uid'   => $uid,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', __('email.errors.reply_send_failed'));
        }

        return redirect()->route('emails.show', $uid);
    }

    /**
     * Delete an email via IMAP.
     */
    public function destroy(Request $request, string $uid)
    {
        $folder = $request->query('folder', 'INBOX');
        $success = $this->imapService->deleteMessage($uid, $folder);
        
        if ($success) {
            Cache::forget('unread_emails_count');
            session()->flash('success', __('email.messages.delete_success'));
        } else {
            session()->flash('error', __('email.errors.delete_failed'));
        }

        return redirect()->route('emails.index');
    }
}
