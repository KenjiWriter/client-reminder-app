<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'starts_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'note' => ['nullable', 'string', 'max:1000'],
            'send_reminder' => ['boolean'],
            'is_paid' => ['boolean'],
            'payment_method' => ['nullable', 'string', 'in:cash,card,transfer'],
            'payment_date' => ['nullable', 'date'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
