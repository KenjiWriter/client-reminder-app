<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    /** @use HasFactory<\Database\Factories\AppointmentFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'datetime',
        'send_reminder' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'first_rescheduled_at' => 'datetime',
        'last_rescheduled_at' => 'datetime',
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
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
