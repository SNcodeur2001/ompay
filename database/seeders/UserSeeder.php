<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Compte;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::create([
            'id' => Str::uuid(),
            'nom' => 'Admin',
            'prenom' => 'System',
            'telephone' => '771234567',
            'code_pin' => Hash::make('1234'),
            'type' => 'client',
            'statut' => 'actif',
        ]);
        Admin::create(['id' => Str::uuid(), 'user_id' => $adminUser->id]);

        $clientUser = User::create([
            'id' => Str::uuid(),
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'telephone' => '772345678',
            'code_pin' => Hash::make('1234'),
            'type' => 'client',
            'statut' => 'actif',
        ]);
        $client = Client::create(['id' => Str::uuid(), 'user_id' => $clientUser->id, 'profession' => 'Non spécifiée']);
        Compte::create([
            'id' => Str::uuid(),
            'user_id' => $clientUser->id,
            'numero_compte' => 'C000000001',
        ]);

        User::factory(48)->create();
    }
}
