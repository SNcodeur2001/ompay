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
            'code_marchand' => 'nullable|string|exists:marchands,code_marchand',
            'telephone_marchand' => 'nullable|string|regex:/^[0-9]{9}$/',
            'montant' => 'required|numeric|min:100|max:500000',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $codeMarchand = $this->input('code_marchand');
            $telephoneMarchand = $this->input('telephone_marchand');

            if (!$codeMarchand && !$telephoneMarchand) {
                $validator->errors()->add('marchand', 'Vous devez fournir soit le code marchand soit le numéro de téléphone du marchand');
            }

            if ($codeMarchand && $telephoneMarchand) {
                $validator->errors()->add('marchand', 'Vous ne pouvez fournir qu\'un seul identifiant de marchand');
            }

            // If telephone is provided, check if it belongs to a marchand
            if ($telephoneMarchand && !$codeMarchand) {
                $marchandExists = \App\Models\Marchand::whereHas('user', function($q) use ($telephoneMarchand) {
                    $q->where('telephone', $telephoneMarchand);
                })->exists();

                if (!$marchandExists) {
                    $validator->errors()->add('telephone_marchand', 'Aucun marchand trouvé avec ce numéro de téléphone');
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code_marchand.exists' => 'Code marchand invalide',
            'telephone_marchand.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres',
            'montant.required' => 'Le montant est obligatoire',
            'montant.numeric' => 'Le montant doit être un nombre',
            'montant.min' => 'Le montant minimum est de 100 FCFA',
            'montant.max' => 'Le montant maximum est de 500 000 FCFA',
        ];
    }
}
