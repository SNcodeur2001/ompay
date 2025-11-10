<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use App\Repositories\Interfaces\CompteRepositoryInterface;
use App\Http\Requests\DepotRequest;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Comptes",
 *     description="Gestion des comptes utilisateur"
 * )
 */
class CompteController extends Controller
{
    private CompteRepositoryInterface $compteRepository;
    private TransactionService $transactionService;

    public function __construct(
        CompteRepositoryInterface $compteRepository,
        TransactionService $transactionService
    ) {
        $this->compteRepository = $compteRepository;
        $this->transactionService = $transactionService;
    }

    /**
     * @OA\Get(
     *     path="/compte",
     *     summary="Informations du compte utilisateur",
     *     description="Retourne les informations du compte de l'utilisateur connecté incluant le solde calculé",
     *     operationId="getCompte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations du compte",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="compte", type="object",
     *                     @OA\Property(property="numero_compte", type="string", example="OM-2025-AB12-CD34"),
     *                     @OA\Property(property="solde", type="number", format="float", example=15000.50, description="Solde calculé (depots - retraits)"),
     *                     @OA\Property(property="qr_code_data", type="string", example="QR code data"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-10T09:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun compte trouvé"
     *     )
     * )
     */
    public function show(): JsonResponse
    {
        try {
            $user = auth()->user();
            $compte = $this->compteRepository->findByUser($user);

            if (!$compte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun compte trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'compte' => [
                        'numero_compte' => $compte->numero_compte,
                        'solde' => $compte->solde, // Utilise l'attribut calculé
                        'qr_code_data' => $compte->qr_code_data,
                        'created_at' => $compte->created_at,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du compte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/compte/depot",
     *     summary="Effectuer un dépôt d'argent",
     *     description="Crédite le compte de l'utilisateur avec le montant spécifié",
     *     operationId="depot",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"montant"},
     *             @OA\Property(property="montant", type="number", format="float", example=5000.00, description="Montant à déposer (min: 100, max: 1 000 000)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dépôt effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dépôt effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction", type="object",
     *                     @OA\Property(property="reference", type="string", example="TXN-20251110-ABC123"),
     *                     @OA\Property(property="type", type="string", example="depot"),
     *                     @OA\Property(property="montant", type="number", format="float", example=5000.00),
     *                     @OA\Property(property="statut", type="string", example="reussi"),
     *                     @OA\Property(property="compte_destinataire", type="object",
     *                         @OA\Property(property="numero_compte", type="string", example="OM-2025-AB12-CD34")
     *                     )
     *                 ),
     *                 @OA\Property(property="nouveau_solde", type="number", format="float", example=20000.50)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Montant invalide ou erreur de traitement"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun compte trouvé"
     *     )
     * )
     */
    public function depot(DepotRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $compte = $this->compteRepository->findByUser($user);

            if (!$compte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun compte trouvé'
                ], 404);
            }

            $transaction = $this->transactionService->effectuerDepot(
                $compte,
                $request->input('montant')
            );

            return response()->json([
                'success' => true,
                'message' => 'Dépôt effectué avec succès',
                'data' => [
                    'transaction' => $transaction->load('compteDestinataire'),
                    'nouveau_solde' => $compte->fresh()->solde,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}