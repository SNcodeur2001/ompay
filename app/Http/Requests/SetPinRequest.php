<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetPinRequest extends FormRequest
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
            'code_pin' => 'required|string|size:4|regex:/^[0-9]{4}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'code_pin.required' => 'Le code PIN est obligatoire.',
            'code_pin.size' => 'Le code PIN doit contenir exactement 4 chiffres.',
            'code_pin.regex' => 'Le code PIN doit contenir uniquement des chiffres.',
        ];
    }
}
