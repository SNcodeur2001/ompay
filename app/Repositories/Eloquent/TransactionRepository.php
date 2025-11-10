<?php

namespace App\Repositories\Eloquent;

use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function findByReference(string $reference): ?Transaction
    {
        return Transaction::where('reference', $reference)->first();
    }

    public function getUserTransactions(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Transaction::where(function ($query) use ($user) {
            $query->whereHas('compteEmetteur', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->orWhereHas('compteDestinataire', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        })
        ->with(['compteEmetteur.user', 'compteDestinataire.user', 'marchand'])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
    }

    public function getRecentTransactions(User $user, int $limit = 10): array
    {
        return Transaction::where(function ($query) use ($user) {
            $query->whereHas('compteEmetteur', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->orWhereHas('compteDestinataire', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        })
        ->with(['compteEmetteur.user', 'compteDestinataire.user', 'marchand'])
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get()
        ->toArray();
    }

    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function updateStatus(Transaction $transaction, string $status): bool
    {
        $transaction->statut = $status;
        return $transaction->save();
    }
}