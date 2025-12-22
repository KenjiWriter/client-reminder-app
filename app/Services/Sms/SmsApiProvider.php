<?php

namespace App\Services\Sms;

use App\Contracts\SmsProvider;
use App\ValueObjects\SmsResult;
use Illuminate\Support\Facades\Log;
use Smsapi\Client\Feature\Sms\Bag\SendSmsBag;

class SmsApiProvider implements SmsProvider
{
    private $service;

    public function __construct(
        private readonly string $token,
        private readonly ?string $from = null,
    ) {
        $client = new SmsapiHttpClientNoVerify();
        $this->service = $client->smsapiPlService($this->token);
    }

    public function send(string $toE164, string $message): SmsResult
    {
        try {
            $sms = SendSmsBag::withMessage($toE164, $message);

            // Don't set 'from' field - let SMS.API use default or account settings
            // Uncomment below only if you have a registered sender ID in SMS.API panel:
            // if ($this->from && strlen($this->from) >= 3) {
            //     $sms->from = $this->from;
            // }

            $result = $this->service->smsFeature()->sendSms($sms);

            Log::info('SMS sent via SMS.API', [
                'to' => $toE164,
                'message_id' => $result->id ?? null,
                'status' => $result->status ?? null,
            ]);

            return SmsResult::success($result->id ?? null);
        } catch (\Smsapi\Client\Exception\SmsapiException $e) {
            Log::error('SMS.API error', [
                'to' => $toE164,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return SmsResult::failure("SMS.API error: {$e->getMessage()}");
        } catch (\Exception $e) {
            Log::error('SMS exception', [
                'to' => $toE164,
                'exception' => $e->getMessage(),
            ]);

            return SmsResult::failure("Exception: {$e->getMessage()}");
        }
    }
}
