<?php

namespace App\Repositories\Eloquent;

use App\Models\Compte;
use App\Models\User;
use App\Repositories\Interfaces\CompteRepositoryInterface;

class CompteRepository implements CompteRepositoryInterface
{
    public function findByUser(User $user): ?Compte
    {
        return $user->compte;
    }

    public function findByNumero(string $numero): ?Compte
    {
        return Compte::where('numero_compte', $numero)->first();
    }

    public function getSolde(Compte $compte): float
    {
        return $compte->solde ?? 0;
    }

    public function updateSolde(Compte $compte, float $montant): bool
    {
        $compte->solde = $montant;
        return $compte->save();
    }
}