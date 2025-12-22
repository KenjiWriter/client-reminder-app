<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string'], // Raw input
        ];
    }

    protected function prepareForValidation()
    {
        // Simple normalization for validation step (more robust logic in controller/service later)
        if ($this->phone) {
            $this->merge([
                'phone_e164' => $this->normalizePhone($this->phone),
            ]);
        }
    }

    private function normalizePhone($phone)
    {
        // Remove non-digit characters
        $digits = preg_replace('/\D/', '', $phone);
        // Default to +48 (PL) if no country code (simple heuristic: 9 digits = PL)
        if (strlen($digits) === 9) {
            return '+48' . $digits;
        }
        return '+' . $digits;
    }
}
