<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nom',
        'prenom',
        'telephone',
        'email',
        'code_pin',
        'type',
        'statut',
        'otp_code',
        'otp_expires_at',
        'is_verified',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'code_pin',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'code_pin' => 'hashed',
        'otp_expires_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function compte()
    {
        return $this->hasOne(Compte::class);
    }

    public function marchand()
    {
        return $this->hasOne(Marchand::class);
    }

    public function transactionsEmises()
    {
        return $this->hasManyThrough(Transaction::class, Compte::class, 'user_id', 'compte_emetteur_id');
    }

    public function transactionsRecues()
    {
        return $this->hasManyThrough(Transaction::class, Compte::class, 'user_id', 'compte_destinataire_id');
    }

    /**
     * Check if user is a client
     */
    public function isClient(): bool
    {
        return $this->type === 'client';
    }

    /**
     * Check if user is a marchand
     */
    public function isMarchand(): bool
    {
        return $this->type === 'marchand';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->statut === 'actif';
    }

    /**
     * Check if user is verified
     */
    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    /**
     * Check if OTP is valid
     */
    public function isOtpValid(string $otp): bool
    {
        return $this->otp_code === $otp &&
               $this->otp_expires_at &&
               $this->otp_expires_at->isFuture();
    }
}
