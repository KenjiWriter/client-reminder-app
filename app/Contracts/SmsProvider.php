<?php

namespace App\Contracts;

use App\ValueObjects\SmsResult;

interface SmsProvider
{
    /**
     * Send an SMS message to the specified phone number.
     *
     * @param string $toE164 Phone number in E.164 format (e.g., +48123456789)
     * @param string $message The SMS message content
     * @return SmsResult Result of the send operation
     */
    public function send(string $toE164, string $message): SmsResult;
}
