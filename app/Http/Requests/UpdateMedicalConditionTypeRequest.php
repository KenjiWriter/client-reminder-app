<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicalConditionTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add proper authorization if needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('medical_condition_types')->ignore($this->medical_condition_type)],
            'category' => ['required', Rule::in(['contraindication', 'esthetic'])],
            'severity' => ['required', Rule::in(['high', 'medium', 'info'])],
            'requires_date' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nazwa',
            'category' => 'kategoria',
            'severity' => 'poziom ważności',
            'requires_date' => 'wymaga daty',
            'is_active' => 'aktywny',
        ];
    }
}
