<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    /** @use HasFactory<\Database\Factories\AppointmentFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_CANCELED = 'canceled';

    protected $casts = [
        'starts_at' => 'datetime',
        'send_reminder' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'first_rescheduled_at' => 'datetime',
        'last_rescheduled_at' => 'datetime',
        'requested_starts_at' => 'datetime',
        'requested_at' => 'datetime',
        'suggested_starts_at' => 'datetime',
        'suggestion_created_at' => 'datetime',
        'is_paid' => 'boolean',
        'payment_date' => 'datetime',
        'price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(function (Appointment $appointment) {
            if ($appointment->isDirty('starts_at')) {
                $appointment->rescheduled_count++;
                
                if (is_null($appointment->first_rescheduled_at)) {
                    $appointment->first_rescheduled_at = now();
                }
                
                $appointment->last_rescheduled_at = now();
                $appointment->reminder_sent_at = null;
            }
        });

        static::created(fn() => \App\Services\DashboardCache::clear());
        static::updated(fn() => \App\Services\DashboardCache::clear());
        static::deleted(fn() => \App\Services\DashboardCache::clear());
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function getEndsAtAttribute()
    {
        if (!$this->starts_at) {
            return null;
        }
        
        // Default to 60 minutes if no service or duration
        $duration = $this->service?->duration ?? 60; 
        
        return $this->starts_at->copy()->addMinutes($duration);
    }
}
