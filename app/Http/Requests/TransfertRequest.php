<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransfertRequest extends FormRequest
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
            'numero_destinataire' => 'required|string|regex:/^[0-9]{9}$/|exists:users,telephone',
            'montant' => 'required|numeric|min:100|max:100000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'numero_destinataire.required' => 'Le numéro de téléphone du destinataire est obligatoire',
            'numero_destinataire.regex' => 'Format de numéro de téléphone invalide (9 chiffres)',
            'numero_destinataire.exists' => 'Numéro de téléphone destinataire introuvable',
            'montant.required' => 'Le montant est obligatoire',
            'montant.numeric' => 'Le montant doit être un nombre',
            'montant.min' => 'Le montant minimum est de 100 FCFA',
            'montant.max' => 'Le montant maximum est de 100 000 FCFA',
        ];
    }
}
