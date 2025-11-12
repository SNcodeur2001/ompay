<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'type' => $this->type,
            'is_verified' => $this->is_verified,
            'setup_completed' => $this->setup_completed,
            'compte' => $this->whenLoaded('compte', function () {
                return [
                    'numero_compte' => $this->compte->numero_compte,
                    'solde' => $this->compte->solde,
                    'qr_code_data' => $this->compte->qr_code_data,
                ];
            }),
        ];
    }
}
