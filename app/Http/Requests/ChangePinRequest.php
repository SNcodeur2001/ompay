<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePinRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'old_pin' => 'required|string|regex:/^[0-9]{4}$/',
            'new_pin' => 'required|string|regex:/^[0-9]{4}$/|different:old_pin',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'old_pin.required' => 'L\'ancien code PIN est obligatoire',
            'old_pin.regex' => 'L\'ancien code PIN doit contenir 4 chiffres',
            'new_pin.required' => 'Le nouveau code PIN est obligatoire',
            'new_pin.regex' => 'Le nouveau code PIN doit contenir 4 chiffres',
            'new_pin.different' => 'Le nouveau code PIN doit être différent de l\'ancien',
        ];
    }
}
