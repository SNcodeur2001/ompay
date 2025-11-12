<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\CompteRepositoryInterface;
use App\Http\Requests\PaiementRequest;
use App\Http\Requests\TransfertRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="Gestion des transactions (paiements, transferts)"
 * )
 */
class TransactionController extends Controller
{
    use ApiResponseTrait;

    private TransactionService $transactionService;
    private ?TransactionRepositoryInterface $transactionRepository;
    private CompteRepositoryInterface $compteRepository;

    public function __construct(
        TransactionService $transactionService,
        TransactionRepositoryInterface $transactionRepository,
        CompteRepositoryInterface $compteRepository
    ) {
        $this->transactionService = $transactionService;
        $this->transactionRepository = $transactionRepository;
        $this->compteRepository = $compteRepository;
    }

    /**
     * @OA\Post(
     *     path="/comptes/{numero_compte}/transactions/paiement",
     *     summary="Effectuer un paiement marchand",
     *     description="Effectue un paiement vers un marchand en utilisant son code marchand depuis le compte spécifié",
     *     operationId="paiement",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numero_compte",
     *         in="path",
     *         required=true,
     *         description="Numéro du compte émetteur",
     *         @OA\Schema(type="string", example="OM-2025-AB12-CD34")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code_marchand", "montant"},
     *             @OA\Property(property="code_marchand", type="string", example="MARCHAND001", description="Code unique du marchand"),
     *             @OA\Property(property="montant", type="number", format="float", example=2500.00, description="Montant du paiement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction", type="object",
     *                     @OA\Property(property="reference", type="string", example="TXN-20251110-ABC123"),
     *                     @OA\Property(property="type", type="string", example="paiement"),
     *                     @OA\Property(property="montant", type="number", format="float", example=2500.00),
     *                     @OA\Property(property="frais", type="number", format="float", example=0),
     *                     @OA\Property(property="statut", type="string", example="reussi"),
     *                     @OA\Property(property="compteEmetteur", type="object",
     *                         @OA\Property(property="numero_compte", type="string", example="OM-2025-AB12-CD34")
     *                     ),
     *                     @OA\Property(property="marchand", type="object",
     *                         @OA\Property(property="raison_sociale", type="string", example="Boutique Express"),
     *                         @OA\Property(property="code_marchand", type="string", example="MARCHAND001")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de traitement (solde insuffisant, montant invalide, etc.)"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Marchand ou compte non trouvé"
     *     )
     * )
     */
    public function paiement(string $numero_compte, PaiementRequest $request): JsonResponse
    {
        try {
            $compte = \App\Models\Compte::where('numero_compte', $numero_compte)->firstOrFail();

            // Vérifier que l'utilisateur est propriétaire du compte
            if ($compte->user_id !== auth()->id()) {
                return $this->errorResponse('Accès non autorisé', 403);
            }

            $validated = $request->validated();
            $transaction = $this->transactionService->effectuerPaiement(
                $compte,
                $validated['code_marchand'],
                (float) $validated['montant']
            );

            return $this->successResponse([
                'transaction' => $transaction->load(['compteEmetteur', 'marchand'])
            ], 'Paiement effectué avec succès');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Aucun compte trouvé', 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/comptes/{numero_compte}/transactions/transfert",
     *     summary="Effectuer un transfert P2P",
     *     description="Transfère de l'argent vers un autre compte utilisateur depuis le compte spécifié",
     *     operationId="transfert",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numero_compte",
     *         in="path",
     *         required=true,
     *         description="Numéro du compte émetteur",
     *         @OA\Schema(type="string", example="OM-2025-AB12-CD34")
     *     ),
     * @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"numero_destinataire", "montant"},
     *             @OA\Property(property="numero_destinataire", type="string", example="781562041", description="Numéro de téléphone du destinataire"),
     *             @OA\Property(property="montant", type="number", format="float", example=10000.00, description="Montant du transfert")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction", type="object",
     *                     @OA\Property(property="reference", type="string", example="TXN-20251110-DEF456"),
     *                     @OA\Property(property="type", type="string", example="transfert"),
     *                     @OA\Property(property="montant", type="number", format="float", example=10000.00),
     *                     @OA\Property(property="frais", type="number", format="float", example=100.00),
     *                     @OA\Property(property="statut", type="string", example="reussi"),
     *                     @OA\Property(property="compteEmetteur", type="object",
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="nom", type="string", example="Diop"),
     *                             @OA\Property(property="prenom", type="string", example="Amadou")
     *                         )
     *                     ),
     *                     @OA\Property(property="compteDestinataire", type="object",
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="nom", type="string", example="Sarr"),
     *                             @OA\Property(property="prenom", type="string", example="Fatou")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de traitement (solde insuffisant, montant invalide, etc.)"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destinataire non trouvé"
     *     )
     * )
     */
    public function transfert(string $numero_compte, TransfertRequest $request): JsonResponse
    {
        try {
            $compte = \App\Models\Compte::where('numero_compte', $numero_compte)->firstOrFail();

            // Vérifier que l'utilisateur est propriétaire du compte
            if ($compte->user_id !== auth()->id()) {
                return $this->errorResponse('Accès non autorisé', 403);
            }

            $validated = $request->validated();
            $transaction = $this->transactionService->effectuerTransfert(
                $compte,
                $validated['numero_destinataire'],
                (float) $validated['montant']
            );

            return $this->successResponse([
                'transaction' => $transaction->load(['compteEmetteur.user', 'compteDestinataire.user'])
            ], 'Transfert effectué avec succès');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Aucun compte trouvé', 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/comptes/{numero_compte}/transactions",
     *     summary="Liste des transactions du compte",
     *     description="Retourne la liste de toutes les transactions du compte spécifié",
     *     operationId="getTransactions",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numero_compte",
     *         in="path",
     *         required=true,
     *         description="Numéro du compte",
     *         @OA\Schema(type="string", example="OM-2025-AB12-CD34")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transactions", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="reference", type="string", example="TXN-20251110-ABC123"),
     *                         @OA\Property(property="type", type="string", enum={"paiement", "transfert", "depot"}, example="transfert"),
     *                         @OA\Property(property="montant", type="number", format="float", example=5000.00),
     *                         @OA\Property(property="frais", type="number", format="float", example=50.00),
     *                         @OA\Property(property="statut", type="string", enum={"reussi", "echec"}, example="reussi"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-10T09:00:00Z")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun compte trouvé"
     *     )
     * )
     */
    public function index(string $numero_compte): JsonResponse
    {
        try {
            $compte = \App\Models\Compte::where('numero_compte', $numero_compte)->firstOrFail();

            // Vérifier que l'utilisateur est propriétaire du compte
            if ($compte->user_id !== auth()->id()) {
                return $this->errorResponse('Accès non autorisé', 403);
            }

            $transactions = $compte->transactionsEmises()->orWhere('compte_destinataire_id', $compte->id)->with(['compteEmetteur', 'compteDestinataire', 'marchand'])->get();

            return $this->successResponse(['transactions' => $transactions], 'Transactions récupérées avec succès');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Aucun compte trouvé', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des transactions', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/comptes/{numero_compte}/transactions/{reference}",
     *     summary="Détail d'une transaction",
     *     description="Retourne les détails complets d'une transaction spécifique du compte",
     *     operationId="getTransaction",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numero_compte",
     *         in="path",
     *         required=true,
     *         description="Numéro du compte",
     *         @OA\Schema(type="string", example="OM-2025-AB12-CD34")
     *     ),
     *     @OA\Parameter(
     *         name="reference",
     *         in="path",
     *         required=true,
     *         description="Référence de la transaction",
     *         @OA\Schema(type="string", example="TXN-20251110-ABC123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la transaction",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction", type="object",
     *                     @OA\Property(property="reference", type="string", example="TXN-20251110-ABC123"),
     *                     @OA\Property(property="type", type="string", enum={"paiement", "transfert", "depot"}, example="transfert"),
     *                     @OA\Property(property="montant", type="number", format="float", example=5000.00),
     *                     @OA\Property(property="frais", type="number", format="float", example=50.00),
     *                     @OA\Property(property="statut", type="string", enum={"reussi", "echec"}, example="reussi"),
     *                     @OA\Property(property="description", type="string", example="Transfert vers OM-2025-EF56-GH78"),
     *                     @OA\Property(property="compteEmetteur", type="object",
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="nom", type="string", example="Diop"),
     *                             @OA\Property(property="prenom", type="string", example="Amadou")
     *                         )
     *                     ),
     *                     @OA\Property(property="compteDestinataire", type="object",
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="nom", type="string", example="Sarr"),
     *                             @OA\Property(property="prenom", type="string", example="Fatou")
     *                         )
     *                     ),
     *                     @OA\Property(property="marchand", type="object", nullable=true,
     *                         @OA\Property(property="raison_sociale", type="string", example="Boutique Express"),
     *                         @OA\Property(property="code_marchand", type="string", example="MARCHAND001")
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-10T09:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé à cette transaction"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction ou compte non trouvé"
     *     )
     * )
     */
    public function show(string $numero_compte, string $reference): JsonResponse
    {
        try {
            $compte = \App\Models\Compte::where('numero_compte', $numero_compte)->firstOrFail();

            // Vérifier que l'utilisateur est propriétaire du compte
            if ($compte->user_id !== auth()->id()) {
                return $this->errorResponse('Accès non autorisé', 403);
            }

            $transaction = $this->transactionRepository->findByReference($reference);

            if (!$transaction) {
                return $this->errorResponse('Transaction non trouvée', 404);
            }

            // Vérifier que la transaction appartient à ce compte
            $hasAccess = $transaction->compte_emetteur_id === $compte->id ||
                           $transaction->compte_destinataire_id === $compte->id;

            if (!$hasAccess) {
                return $this->errorResponse('Accès non autorisé à cette transaction', 403);
            }

            return $this->successResponse([
                'transaction' => $transaction->load(['compteEmetteur.user', 'compteDestinataire.user', 'marchand'])
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Aucun compte trouvé', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération de la transaction', 500);
        }
    }
}