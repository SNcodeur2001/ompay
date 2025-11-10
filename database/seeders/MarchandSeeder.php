<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Marchand;

class MarchandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marchands = [
            [
                'nom' => 'Auchan',
                'prenom' => 'Dakar',
                'telephone' => '338000000',
                'code_marchand' => 'MCH-AUCH1',
                'raison_sociale' => 'Auchan Dakar',
                'categorie' => 'Supermarché',
            ],
            [
                'nom' => 'CFAO',
                'prenom' => 'Technologies',
                'telephone' => '338000001',
                'code_marchand' => 'MCH-CFAO1',
                'raison_sociale' => 'CFAO Technologies',
                'categorie' => 'Électronique',
            ],
            [
                'nom' => 'Total',
                'prenom' => 'Energies',
                'telephone' => '338000002',
                'code_marchand' => 'MCH-TOTT1',
                'raison_sociale' => 'Total Energies Dakar',
                'categorie' => 'Station-service',
            ],
            [
                'nom' => 'Super',
                'prenom' => 'V',
                'telephone' => '338000003',
                'code_marchand' => 'MCH-SUPV1',
                'raison_sociale' => 'Super V Dakar',
                'categorie' => 'Épicerie',
            ],
        ];

        foreach ($marchands as $marchandData) {
            // Créer l'utilisateur marchand
            $user = User::create([
                'nom' => $marchandData['nom'],
                'prenom' => $marchandData['prenom'],
                'telephone' => $marchandData['telephone'],
                'type' => 'marchand',
            ]);

            // Créer le marchand
            Marchand::create([
                'user_id' => $user->id,
                'code_marchand' => $marchandData['code_marchand'],
                'raison_sociale' => $marchandData['raison_sociale'],
                'categorie' => $marchandData['categorie'],
            ]);
        }
    }
}
