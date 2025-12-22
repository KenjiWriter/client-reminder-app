<?php

namespace App\Services\Sms;

use App\Contracts\SmsProvider;
use App\ValueObjects\SmsResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsApiProvider implements SmsProvider
{
    public function __construct(
        private readonly string $token,
        private readonly string $from,
    ) {
    }

    public function send(string $toE164, string $message): SmsResult
    {
        try {
            $response = Http::withToken($this->token)
                ->asJson()
                ->post('https://api.smsapi.pl/sms.do', [
                    'to' => $toE164,
                    'message' => $message,
                    'from' => $this->from,
                    'format' => 'json',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $messageId = $data['list'][0]['id'] ?? null;

                Log::info('SMS sent via SMS.API', [
                    'to' => $toE164,
                    'message_id' => $messageId,
                    'response' => $data,
                ]);

                return SmsResult::success($messageId);
            }

            $error = $response->json('message') ?? 'Unknown error from SMS.API';

            Log::error('SMS.API send failed', [
                'to' => $toE164,
                'status' => $response->status(),
                'error' => $error,
                'response' => $response->body(),
            ]);

            return SmsResult::failure("SMS.API error: {$error}");
        } catch (\Exception $e) {
            Log::error('SMS.API exception', [
                'to' => $toE164,
                'exception' => $e->getMessage(),
            ]);

            return SmsResult::failure("Exception: {$e->getMessage()}");
        }
    }
}
