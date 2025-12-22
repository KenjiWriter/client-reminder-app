<?php

namespace App\Console\Commands;

use App\Contracts\SmsProvider;
use Illuminate\Console\Command;

class TestSmsCommand extends Command
{
    protected $signature = 'sms:test {phone} {--message=}';

    protected $description = 'Send a test SMS to verify SMS provider configuration';

    public function handle(SmsProvider $smsProvider): int
    {
        $phone = $this->argument('phone');
        $message = $this->option('message') ?? 'This is a test message from Client Reminder App. Your SMS configuration is working correctly!';

        $this->info("Sending test SMS to: {$phone}");
        $this->info("Using driver: ".config('sms.driver'));
        $this->line('');

        $result = $smsProvider->send($phone, $message);

        if ($result->success) {
            $this->info('✓ SMS sent successfully!');
            if ($result->providerMessageId) {
                $this->line("  Message ID: {$result->providerMessageId}");
            }

            return self::SUCCESS;
        }

        $this->error('✗ Failed to send SMS');
        $this->error("  Error: {$result->error}");

        return self::FAILURE;
    }
}
