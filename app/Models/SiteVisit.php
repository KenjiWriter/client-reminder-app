<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisit extends Model
{
    protected $fillable = [
        'ip_address',
        'user_id',
        'url',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
