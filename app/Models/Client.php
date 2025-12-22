<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'sms_opt_out' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($client) {
            $client->public_uid = $client->public_uid ?? (string) Str::ulid();
        });
    }
}
