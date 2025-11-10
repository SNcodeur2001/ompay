<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaiementRequest extends FormRequest
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
            'code_marchand' => 'required|string|exists:marchands,code_marchand',
            'montant' => 'required|numeric|min:100|max:500000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code_marchand.required' => 'Le code marchand est obligatoire',
            'code_marchand.exists' => 'Code marchand invalide',
            'montant.required' => 'Le montant est obligatoire',
            'montant.numeric' => 'Le montant doit être un nombre',
            'montant.min' => 'Le montant minimum est de 100 FCFA',
            'montant.max' => 'Le montant maximum est de 500 000 FCFA',
        ];
    }
}
