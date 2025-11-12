<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'telephone' => 'required|string|regex:/^[0-9]{9}$/',
            'code_pin' => 'nullable|string|regex:/^[0-9]{4}$/',
            'otp' => 'nullable|string|regex:/^[0-9]{6}$/',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->input('code_pin') && !$this->input('otp')) {
                $validator->errors()->add('code_pin', 'Le code PIN ou le code OTP est obligatoire');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'telephone.required' => 'Le numéro de téléphone est obligatoire',
            'telephone.regex' => 'Le numéro de téléphone doit contenir 9 chiffres',
            'code_pin.required' => 'Le code PIN est obligatoire',
            'code_pin.regex' => 'Le code PIN doit contenir 4 chiffres',
        ];
    }
}
