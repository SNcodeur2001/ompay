<?php

namespace App\Repositories\Interfaces;

use App\Models\Compte;
use App\Models\User;

interface CompteRepositoryInterface
{
    public function findByUser(User $user): ?Compte;
    public function findByNumero(string $numero): ?Compte;
    public function getSolde(Compte $compte): float;
    public function updateSolde(Compte $compte, float $montant): bool;
}