<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reference',
        'type',
        'statut',
        'compte_emetteur_id',
        'compte_destinataire_id',
        'marchand_id',
        'montant',
        'frais',
        'description',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'frais' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function compteEmetteur()
    {
        return $this->belongsTo(Compte::class, 'compte_emetteur_id');
    }

    public function compteDestinataire()
    {
        return $this->belongsTo(Compte::class, 'compte_destinataire_id');
    }

    public function marchand()
    {
        return $this->belongsTo(Marchand::class);
    }

    /**
     * Generate transaction reference
     */
    public static function generateReference(): string
    {
        return 'TRX-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful(): bool
    {
        return $this->statut === 'reussi';
    }

    /**
     * Check if transaction is a payment
     */
    public function isPaiement(): bool
    {
        return $this->type === 'paiement';
    }

    /**
     * Check if transaction is a transfer
     */
    public function isTransfert(): bool
    {
        return $this->type === 'transfert';
    }
}
