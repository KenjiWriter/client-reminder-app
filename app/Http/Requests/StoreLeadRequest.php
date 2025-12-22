<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Use middleware for protection (e.g., throttle)
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->phone) {
            $this->merge([
                'phone_e164' => $this->normalizePhone($this->phone),
            ]);
        }
    }

    private function normalizePhone($phone)
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 9) {
            return '+48' . $digits;
        }
        return '+' . $digits;
    }
}
