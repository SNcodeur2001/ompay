<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OM Pay Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour les limites et frais de l'application OM Pay
    |
    */

    'limites' => [
        'paiement' => [
            'min' => 100,      // Montant minimum en FCFA
            'max' => 500000,   // Montant maximum en FCFA
        ],
        'transfert' => [
            'min' => 100,      // Montant minimum en FCFA
            'max' => 100000,   // Montant maximum en FCFA
        ],
    ],

    'frais' => [
        'transfert' => [
            [0, 5000, 0],          // 0-5000 FCFA : 0 FCFA de frais
            [5001, 50000, 100],    // 5001-50000 FCFA : 100 FCFA de frais
            [50001, 100000, 200],  // 50001-100000 FCFA : 200 FCFA de frais
        ],
        'paiement' => 0,  // Frais de paiement marchand (gratuit)
    ],

    /*
    |--------------------------------------------------------------------------
    | Codes marchands
    |--------------------------------------------------------------------------
    |
    | Liste des codes marchands disponibles pour les tests
    |
    */
    'codes_marchands' => [
        'MCH-AUCH1',  // Auchan Dakar
        'MCH-CFAO1',  // CFAO
        'MCH-TOTT1',  // Total
        'MCH-SUPV1',  // Super V
    ],
];