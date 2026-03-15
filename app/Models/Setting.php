<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key', 
        'value',
        'sms_api_token',
        'sms_sender_name',
        'sms_send_time',
        'app_name',
        'app_logo',
        'email_sender_name',
        'imap_host',
        'imap_port',
        'imap_username',
        'imap_password',
        'imap_sent_folder',
        'email_signature',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
    ];

    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("setting.{$key}");
    }
}
