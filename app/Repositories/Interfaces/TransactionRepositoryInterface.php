<?php

namespace App\Repositories\Interfaces;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    public function findByReference(string $reference): ?Transaction;
    public function getUserTransactions(User $user, int $perPage = 15): LengthAwarePaginator;
    public function getRecentTransactions(User $user, int $limit = 10): array;
    public function create(array $data): Transaction;
    public function updateStatus(Transaction $transaction, string $status): bool;
}