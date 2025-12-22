<?php

namespace App\Services\Sms;

use App\Contracts\SmsProvider;
use App\ValueObjects\SmsResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogSmsProvider implements SmsProvider
{
    public function send(string $toE164, string $message): SmsResult
    {
        $fakeMessageId = 'log_'.Str::uuid();

        Log::info('SMS Send (Log Driver)', [
            'to' => $toE164,
            'message' => $message,
            'message_id' => $fakeMessageId,
        ]);

        return SmsResult::success($fakeMessageId);
    }
}
