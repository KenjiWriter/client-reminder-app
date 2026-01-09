<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client();
        
        if (config('services.google.client_id')) {
            $this->client->setClientId(config('services.google.client_id'));
        }
        if (config('services.google.client_secret')) {
            $this->client->setClientSecret(config('services.google.client_secret'));
        }
        if (config('services.google.redirect_uri')) {
            $this->client->setRedirectUri(config('services.google.redirect_uri'));
        }

        $this->client->addScope(Calendar::CALENDAR);
        $this->client->addScope('email'); // Required to get user email
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    public function isConfigured(): bool
    {
        return config('services.google.client_id') && 
               config('services.google.client_secret') && 
               config('services.google.redirect_uri');
    }

    public function getAuthUrl(): string
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Google Calendar credentials are not configured.');
        }
        return $this->client->createAuthUrl();
    }

    public function fetchAccessTokenWithAuthCode(string $authCode, User $user): void
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($authCode);
        
        // Debugging logs
        Log::info('Google Token Fetch Result:', ['keys' => array_keys($token)]);

        if (array_key_exists('error', $token)) {
            throw new \Exception('Google Token Fetch Error: ' . json_encode($token));
        }

        $this->updateUserTokens($user, $token);
        $this->client->setAccessToken($token);
        
        // Try to get email from ID Token first (standard OpenID Connect)
        $email = null;
        if (isset($token['id_token'])) {
            $payload = $this->client->verifyIdToken($token['id_token']);
            if ($payload && isset($payload['email'])) {
                $email = $payload['email'];
            }
        }

        // Fallback to UserInfo API if ID Token didn't work
        if (!$email) {
            $oauth2 = new \Google\Service\Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();
            $email = $userInfo->email;
        }
        
        $user->update([
            'google_calendar_email' => $email,
        ]);
    }

    protected function updateUserTokens(User $user, array $token): void
    {
        $user->google_access_token = Crypt::encryptString($token['access_token']);
        
        if (isset($token['refresh_token'])) {
            $user->google_refresh_token = Crypt::encryptString($token['refresh_token']);
        }

        if (isset($token['expires_in'])) {
            $user->google_token_expires_at = now()->addSeconds($token['expires_in']);
        }

        $user->save();
    }

    protected function setAccessTokenForUser(User $user): bool
    {
        if (!$user->google_access_token) {
            return false;
        }

        $accessToken = Crypt::decryptString($user->google_access_token);
        $this->client->setAccessToken($accessToken);

        if ($this->client->isAccessTokenExpired()) {
            if ($user->google_refresh_token) {
                $refreshToken = Crypt::decryptString($user->google_refresh_token);
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                $this->updateUserTokens($user, $newToken);
                return true;
            }
            return false; // Token expired and no refresh token
        }

        return true;
    }

    public function createEvent(Appointment $appointment): ?string
    {
        // Admin or the user associated with appointment?
        // Assuming single-tenant/admin use case primarily, or we sync to the Admin's calendar?
        // Let's assume we sync to the currently authenticated user (Admin).
        // For background jobs, we need to know WHICH user to sync for.
        // Assuming we sync for the first admin or a specific user.
        // For now, let's assume we pass the user explicitly or use the first admin.
        
        $user = User::first(); // Simplification for MVP: Sync to main admin
        
        Log::info('GoogleCalendarService: Attempting sync for user', ['user_id' => $user?->id, 'email' => $user?->email]);

        if (!$user || !$this->setAccessTokenForUser($user)) {
             Log::warning('Google Calendar Sync: No valid user or token found.', ['user' => $user]);
             return null;
        }

        $service = new Calendar($this->client);

        $event = new Event([
            'summary' => $appointment->service ? $appointment->service->name : 'Appointment',
            'description' => "Client: {$appointment->client->full_name}\nPhone: {$appointment->client->phone_e164}\nNote: {$appointment->note}",
            'start' => [
                'dateTime' => $appointment->starts_at->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ],
            'end' => [
                'dateTime' => $appointment->ends_at->toRfc3339String(), // Ensure Appointment has ends_at accessor or calc
                'timeZone' => config('app.timezone'),
            ],
        ]);

        try {
            $createdEvent = $service->events->insert('primary', $event);
            return $createdEvent->id;
        } catch (\Exception $e) {
            Log::error('Google Calendar Create Error: ' . $e->getMessage());
            return null;
        }
    }

    public function updateEvent(Appointment $appointment): void
    {
        if (!$appointment->google_event_id) {
             // If no ID, maybe create it?
             $id = $this->createEvent($appointment);
             if ($id) {
                 $appointment->update(['google_event_id' => $id]);
             }
             return;
        }

        $user = User::first(); // MVP
        if (!$user || !$this->setAccessTokenForUser($user)) return;

        $service = new Calendar($this->client);
        
        try {
            $event = $service->events->get('primary', $appointment->google_event_id);
            
            $event->setSummary($appointment->service ? $appointment->service->name : 'Appointment');
            $event->setDescription("Client: {$appointment->client->full_name}\nPhone: {$appointment->client->phone_e164}\nNote: {$appointment->note}");
            $event->setStart(new EventDateTime([
                'dateTime' => $appointment->starts_at->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ]));
            $event->setEnd(new EventDateTime([
                'dateTime' => $appointment->ends_at->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ]));

            $service->events->update('primary', $appointment->google_event_id, $event);
        } catch (\Exception $e) {
            Log::error('Google Calendar Update Error: ' . $e->getMessage());
            // If 404, maybe clear the ID or recreate?
            if ($e->getCode() == 404) {
                $appointment->update(['google_event_id' => null]);
                $this->createEvent($appointment);
            }
        }
    }

    public function deleteEvent(string $googleEventId): void
    {
        $user = User::first();
        if (!$user || !$this->setAccessTokenForUser($user)) return;

        $service = new Calendar($this->client);

        try {
            $service->events->delete('primary', $googleEventId);
        } catch (\Exception $e) {
            Log::error('Google Calendar Delete Error: ' . $e->getMessage());
        }
    }

    public function listEvents(User $user, \DateTime $start, \DateTime $end): array
    {
        if (!$this->setAccessTokenForUser($user)) {
             Log::warning('Google Calendar List: No valid user or token found.', ['user' => $user]);
             return [];
        }

        $service = new Calendar($this->client);
        
        $params = [
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => $start->format(\DateTime::RFC3339),
            'timeMax' => $end->format(\DateTime::RFC3339),
        ];

        try {
            $events = $service->events->listEvents('primary', $params);
            return $events->getItems();
        } catch (\Exception $e) {
             Log::error('Google Calendar List Error: ' . $e->getMessage());
             return [];
        }
    }
}
