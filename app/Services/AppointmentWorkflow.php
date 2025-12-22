<?php

namespace App\Services;
use App\Models\Appointment;
use App\Services\AppointmentReminderSender;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentWorkflow
{
    public function __construct(
        protected AppointmentReminderSender $smsService
    ) {}

    /**
     * Client requests a reschedule.
     */
    public function requestReschedule(Appointment $appointment, Carbon $requestedStartsAt): void
    {
        $appointment->update([
            'status' => Appointment::STATUS_PENDING_APPROVAL,
            'requested_starts_at' => $requestedStartsAt,
            'requested_at' => now(),
        ]);
    }

    /**
     * Mother (Admin) approves the client's requested change.
     */
    public function approveRequestedChange(Appointment $appointment): void
    {
        if (!$appointment->requested_starts_at) {
            throw new \RuntimeException('No requested time to approve.');
        }

        $appointment->update([
            'status' => Appointment::STATUS_CONFIRMED,
            'starts_at' => $appointment->requested_starts_at,
            'requested_starts_at' => null,
            'requested_at' => null,
            'reminder_sent_at' => null, // Reset reminder
        ]);

        $this->smsService->sendApproval($appointment);
    }

    /**
     * Mother (Admin) rejects the client's request and proposes an alternative.
     */
    public function rejectWithSuggestion(Appointment $appointment, Carbon $suggestedStartsAt, ?string $note = null): void
    {
        $appointment->update([
            'status' => Appointment::STATUS_PENDING_APPROVAL,
            'requested_starts_at' => null, // Clear client's request if proposing ours
            'requested_at' => null,
            'suggested_starts_at' => $suggestedStartsAt,
            'suggested_note' => $note,
            'suggestion_created_at' => now(),
        ]);

        $this->smsService->sendSuggestion($appointment);
    }

    /**
     * Client accepts the mother's suggested time.
     */
    public function clientAcceptSuggestion(Appointment $appointment): void
    {
        if (!$appointment->suggested_starts_at) {
            throw new \RuntimeException('No suggested time to accept.');
        }

        $appointment->update([
            'status' => Appointment::STATUS_CONFIRMED,
            'starts_at' => $appointment->suggested_starts_at,
            'suggested_starts_at' => null,
            'suggested_duration_minutes' => null,
            'suggested_note' => null,
            'suggestion_created_at' => null,
            'reminder_sent_at' => null, // Reset reminder
        ]);
    }

    /**
     * Client rejects the mother's suggested time.
     */
    public function clientRejectSuggestion(Appointment $appointment): void
    {
        $appointment->update([
            'status' => Appointment::STATUS_PENDING_APPROVAL,
            'suggested_starts_at' => null,
            'suggested_duration_minutes' => null,
            'suggested_note' => null,
            'suggestion_created_at' => null,
        ]);
    }

    /**
     * Client cancels the appointment.
     */
    public function cancelAppointment(Appointment $appointment): void
    {
        $appointment->update([
            'status' => Appointment::STATUS_CANCELED,
            'requested_starts_at' => null,
            'suggested_starts_at' => null,
            'reminder_sent_at' => null,
        ]);
        
        // Optional: Send cancellation confirmation SMS?
        // $this->smsService->sendCancellation($appointment);
    }
}
