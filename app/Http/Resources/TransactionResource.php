<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    protected $currentAccount;

    public function __construct($resource, $currentAccount = null)
    {
        parent::__construct($resource);
        $this->currentAccount = $currentAccount;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'reference' => $this->reference,
            'type' => $this->type,
            'montant' => $this->montant,
            'date' => $this->created_at->format('Y-m-d H:i:s'),
        ];

        // Déterminer l'expéditeur et le destinataire selon le type
        if ($this->type === 'paiement') {
            // Pour les paiements, l'expéditeur est l'utilisateur du compte émetteur
            $emetteur = $this->whenLoaded('compteEmetteur')?->user ?? null;
            $data['expediteur'] = $emetteur ? trim($emetteur->nom . ' ' . $emetteur->prenom) : ($emetteur->telephone ?? 'Inconnu');

            // Le destinataire est le marchand
            $data['destinataire'] = $this->whenLoaded('marchand') ? $this->marchand->raison_sociale : 'Marchand inconnu';

            // Pour les paiements, c'est toujours un retrait depuis le compte connecté
            $data['sens_transfert'] = 'retrait';
        } elseif ($this->type === 'transfert') {
            // Pour les transferts, l'expéditeur est l'utilisateur du compte émetteur
            $emetteur = $this->whenLoaded('compteEmetteur')?->user ?? null;
            $data['expediteur'] = $emetteur ? trim($emetteur->nom . ' ' . $emetteur->prenom) : ($emetteur->telephone ?? 'Inconnu');

            // Le destinataire est l'utilisateur du compte destinataire
            $destinataire = $this->whenLoaded('compteDestinataire')?->user ?? null;
            $data['destinataire'] = $destinataire ? trim($destinataire->nom . ' ' . $destinataire->prenom) : ($destinataire->telephone ?? 'Inconnu');

            // Déterminer le sens du transfert selon le compte connecté
            if ($this->currentAccount && $this->compte_emetteur_id === $this->currentAccount->id) {
                $data['sens_transfert'] = 'retrait';
            } elseif ($this->currentAccount && $this->compte_destinataire_id === $this->currentAccount->id) {
                $data['sens_transfert'] = 'depot';
            } else {
                $data['sens_transfert'] = 'neutre'; // Cas improbable
            }
        } elseif ($this->type === 'depot') {
            // Pour les dépôts, l'expéditeur pourrait être le système ou un admin
            $data['expediteur'] = 'Système';

            // Le destinataire est l'utilisateur du compte destinataire
            $destinataire = $this->whenLoaded('compteDestinataire')?->user ?? null;
            $data['destinataire'] = $destinataire ? trim($destinataire->nom . ' ' . $destinataire->prenom) : ($destinataire->telephone ?? 'Inconnu');

            // Pour les dépôts, c'est toujours un dépôt vers le compte connecté
            $data['sens_transfert'] = 'depot';
        } else {
            $data['expediteur'] = 'Inconnu';
            $data['destinataire'] = 'Inconnu';
            $data['sens_transfert'] = 'neutre';
        }

        return $data;
    }
}
