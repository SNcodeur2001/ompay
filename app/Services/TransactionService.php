<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\Transaction;
use App\Models\Marchand;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionService
{
    /**
     * Effectuer un dépôt d'argent
     */
    public function effectuerDepot(Compte $compte, float $montant): Transaction
    {
        return DB::transaction(function () use ($compte, $montant) {
            // Vérifier les limites de dépôt
            $this->verifierLimitesDepot($montant);

            // Créer la transaction de dépôt
            $transaction = Transaction::create([
                'reference' => Transaction::generateReference(),
                'type' => 'depot',
                'statut' => 'reussi',
                'compte_destinataire_id' => $compte->id,
                'montant' => $montant,
                'frais' => 0,
                'description' => "Dépôt d'argent",
            ]);

            // Créditer le compte
            $compte->credit($montant);

            return $transaction;
        });
    }

    /**
     * Effectuer un paiement à un marchand
     */
    public function effectuerPaiement(Compte $compteEmetteur, string $codeMarchand, float $montant): Transaction
    {
        return DB::transaction(function () use ($compteEmetteur, $codeMarchand, $montant) {
            // Vérifier les limites
            $this->verifierLimitesPaiement($montant);

            // Trouver le marchand
            $marchand = Marchand::where('code_marchand', $codeMarchand)->first();
            if (!$marchand) {
                throw new Exception('Marchand non trouvé');
            }

            // Calculer les frais (0 pour paiement)
            $frais = 0;

            // Vérifier le solde
            if (!$compteEmetteur->hasSufficientBalance($montant + $frais)) {
                throw new Exception('Solde insuffisant');
            }

            // Créer la transaction
            $transaction = Transaction::create([
                'reference' => Transaction::generateReference(),
                'type' => 'paiement',
                'statut' => 'reussi',
                'compte_emetteur_id' => $compteEmetteur->id,
                'marchand_id' => $marchand->id,
                'montant' => $montant,
                'frais' => $frais,
                'description' => "Paiement marchand {$marchand->raison_sociale}",
            ]);

            return $transaction;
        });
    }

    /**
     * Effectuer un transfert P2P
     */
    public function effectuerTransfert(Compte $compteEmetteur, string $numeroDestinataire, float $montant): Transaction
    {
        return DB::transaction(function () use ($compteEmetteur, $numeroDestinataire, $montant) {
            // Vérifier les limites
            $this->verifierLimitesTransfert($montant);

            // Trouver le compte destinataire par numéro de téléphone
            $compteDestinataire = Compte::whereHas('user', function ($query) use ($numeroDestinataire) {
                $query->where('telephone', $numeroDestinataire);
            })->first();

            if (!$compteDestinataire) {
                throw new Exception('Destinataire non trouvé');
            }

            // Empêcher le transfert vers soi-même
            if ($compteEmetteur->id === $compteDestinataire->id) {
                throw new Exception('Impossible de transférer vers son propre compte');
            }

            // Calculer les frais
            $frais = $this->calculerFraisTransfert($montant);

            // Vérifier le solde
            if (!$compteEmetteur->hasSufficientBalance($montant + $frais)) {
                throw new Exception('Solde insuffisant');
            }

            // Créer la transaction
            $transaction = Transaction::create([
                'reference' => Transaction::generateReference(),
                'type' => 'transfert',
                'statut' => 'reussi',
                'compte_emetteur_id' => $compteEmetteur->id,
                'compte_destinataire_id' => $compteDestinataire->id,
                'montant' => $montant,
                'frais' => $frais,
                'description' => "Transfert vers {$compteDestinataire->numero_compte}",
            ]);

            // Débiter l'émetteur et créditer le destinataire
            $compteEmetteur->debit($montant + $frais);
            $compteDestinataire->credit($montant);

            return $transaction;
        });
    }

    /**
     * Calculer les frais de transfert
     */
    private function calculerFraisTransfert(float $montant): float
    {
        $fraisConfig = config('om_pay.frais.transfert', []);

        foreach ($fraisConfig as $plage) {
            if ($montant >= $plage[0] && $montant <= $plage[1]) {
                return $plage[2];
            }
        }

        return 0;
    }

    /**
     * Vérifier les limites de paiement
     */
    private function verifierLimitesPaiement(float $montant): void
    {
        $limites = config('om_pay.limites.paiement', []);

        if (isset($limites['min']) && $montant < $limites['min']) {
            throw new Exception("Montant minimum: {$limites['min']} FCFA");
        }

        if (isset($limites['max']) && $montant > $limites['max']) {
            throw new Exception("Montant maximum: {$limites['max']} FCFA");
        }
    }

    /**
     * Vérifier les limites de transfert
     */
    private function verifierLimitesTransfert(float $montant): void
    {
        $limites = config('om_pay.limites.transfert', []);

        if (isset($limites['min']) && $montant < $limites['min']) {
            throw new Exception("Montant minimum: {$limites['min']} FCFA");
        }

        if (isset($limites['max']) && $montant > $limites['max']) {
            throw new Exception("Montant maximum: {$limites['max']} FCFA");
        }
    }

    /**
     * Vérifier les limites de dépôt
     */
    private function verifierLimitesDepot(float $montant): void
    {
        $limites = config('om_pay.limites.depot', []);

        if (isset($limites['min']) && $montant < $limites['min']) {
            throw new Exception("Montant minimum: {$limites['min']} FCFA");
        }

        if (isset($limites['max']) && $montant > $limites['max']) {
            throw new Exception("Montant maximum: {$limites['max']} FCFA");
        }
    }
}