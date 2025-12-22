<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class AvailabilityService
{
    /**
     * Get available slots for a given date range and duration.
     *
     * @param Carbon $from
     * @param Carbon $to
     * @param int $durationMinutes
     * @return array
     */
    public function getAvailableSlots(Carbon $from, Carbon $to, int $durationMinutes): array
    {
        $timezone = Setting::get('timezone', config('app.timezone', 'UTC'));
        
        $driver = DB::getDriverName();
        $endCalc = $driver === 'sqlite' 
            ? "datetime(starts_at, '+' || duration_minutes || ' minutes')"
            : "DATE_ADD(starts_at, INTERVAL duration_minutes MINUTE)";

        // Fetch appointments in range
        $appointments = Appointment::where('status', '!=', Appointment::STATUS_CANCELED)
            ->where(function ($query) use ($from, $to, $endCalc) {
                $query->whereBetween('starts_at', [$from, $to])
                    ->orWhereRaw("$endCalc BETWEEN ? AND ?", [$from, $to]);
            })
            ->get();

        // Business hours
        $startHour = 9;
        $endHour = 17;
        $stepMinutes = 30;

        $availableSlots = [];
        $period = CarbonPeriod::create($from->startOfDay(), '1 day', $to->endOfDay());

        foreach ($period as $date) {
            if ($date->isWeekend()) continue;

            $currentSlot = $date->copy()->timezone($timezone)->setTime($startHour, 0);
            $dayEnd = $date->copy()->timezone($timezone)->setTime($endHour, 0);

            while ($currentSlot->copy()->addMinutes($durationMinutes)->lte($dayEnd)) {
                $slotStart = $currentSlot->copy()->utc();
                $slotEnd = $currentSlot->copy()->addMinutes($durationMinutes)->utc();

                $isOverlapping = $appointments->contains(function ($appointment) use ($slotStart, $slotEnd) {
                    $apptStart = $appointment->starts_at;
                    $apptEnd = $appointment->starts_at->copy()->addMinutes($appointment->duration_minutes);
                    return ($slotStart->lt($apptEnd) && $slotEnd->gt($apptStart));
                });

                if (!$isOverlapping) {
                    $availableSlots[] = [
                        'start' => $slotStart->toIso8601String(),
                        'display' => $currentSlot->format('H:i'),
                        'date' => $currentSlot->format('Y-m-d'),
                    ];
                }

                $currentSlot->addMinutes($stepMinutes);
            }
        }

        return $availableSlots;
    }

    /**
     * Check if a specific slot is available.
     */
    public function isSlotAvailable(Carbon $startsAt, int $durationMinutes): bool
    {
        $endsAt = $startsAt->copy()->addMinutes($durationMinutes);
        
        $driver = DB::getDriverName();
        $endCalc = $driver === 'sqlite' 
            ? "datetime(starts_at, '+' || duration_minutes || ' minutes')"
            : "DATE_ADD(starts_at, INTERVAL duration_minutes MINUTE)";

        return !Appointment::where('status', '!=', Appointment::STATUS_CANCELED)
            ->where(function ($query) use ($startsAt, $endsAt, $endCalc) {
                $query->where('starts_at', '<', $endsAt)
                    ->whereRaw("$endCalc > ?", [$startsAt]);
            })
            ->exists();
    }
}
