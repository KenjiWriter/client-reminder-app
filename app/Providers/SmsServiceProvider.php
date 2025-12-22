<?php

namespace App\Providers;

use App\Contracts\SmsProvider;
use App\Services\Sms\LogSmsProvider;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsProvider::class, function ($app) {
            $driver = config('sms.driver');

            return match ($driver) {
                'log' => new LogSmsProvider(),
                // 'smsapi' => new SmsApiProvider(),
                default => throw new \InvalidArgumentException("Unsupported SMS driver: {$driver}"),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
