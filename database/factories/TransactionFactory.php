<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['paiement', 'transfert']);
        $compteId = \App\Models\Compte::factory()->create()->id;

        return [
            'reference' => \App\Models\Transaction::generateReference(),
            'type' => $type,
            'statut' => 'reussi',
            'compte_emetteur_id' => $compteId,
            'compte_destinataire_id' => $type === 'transfert' ? \App\Models\Compte::where('id', '!=', $compteId)->inRandomOrder()->first()->id ?? $compteId : null,
            'montant' => fake()->randomFloat(2, 100, 10000),
            'frais' => 0,
        ];
    }
}
