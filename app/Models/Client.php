<?php

namespace App\Models;

use App\Services\DashboardCache;
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

        static::created(function () {
            DashboardCache::clear();
        });
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function siteVisits()
    {
        return $this->hasMany(SiteVisit::class);
    }

    public function medicalHistory()
    {
        return $this->hasOne(MedicalHistory::class);
    }

    /**
     * Get all medical conditions for this client.
     */
    public function conditions()
    {
        return $this->belongsToMany(MedicalConditionType::class, 'client_medical_condition')
            ->withPivot('occurred_at', 'notes', 'is_active')
            ->withTimestamps();
    }
}
