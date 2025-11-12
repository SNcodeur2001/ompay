<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\CompteRepositoryInterface;
use App\Http\Requests\PaiementRequest;
use App\Http\Requests\TransfertRequest;
use App\Http\Resources\TransactionResource;
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
     *             required={"montant"},
     *             @OA\Property(property="code_marchand", type="string", example="MARCHAND001", description="Code unique du marchand (optionnel si telephone_marchand est fourni)"),
     *             @OA\Property(property="telephone_marchand", type="string", example="781562042", description="Numéro de téléphone du marchand (optionnel si code_marchand est fourni)"),
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
     *                     @OA\Property(property="date", type="string", format="date-time", example="2025-11-10 09:00:00"),
     *                     @OA\Property(property="expediteur", type="string", example="Amadou Diop"),
     *                     @OA\Property(property="destinataire", type="string", example="Boutique Express"),
     *                     @OA\Property(property="sens_transfert", type="string", enum={"retrait", "depot"}, example="retrait")
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

            // Trouver le marchand par code ou téléphone
            $marchand = null;
            if (isset($validated['code_marchand'])) {
                $marchand = \App\Models\Marchand::where('code_marchand', $validated['code_marchand'])->first();
            } elseif (isset($validated['telephone_marchand'])) {
                $marchand = \App\Models\Marchand::whereHas('user', function($q) use ($validated) {
                    $q->where('telephone', $validated['telephone_marchand']);
                })->first();
            }

            if (!$marchand) {
                return $this->errorResponse('Marchand non trouvé', 404);
            }

            $transaction = $this->transactionService->effectuerPaiement(
                $compte,
                $marchand,
                (float) $validated['montant']
            );

            return $this->successResponse([
                'transaction' => new TransactionResource($transaction->load(['compteEmetteur.user', 'marchand']), $compte)
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
     *                     @OA\Property(property="date", type="string", format="date-time", example="2025-11-10 09:00:00"),
     *                     @OA\Property(property="expediteur", type="string", example="Amadou Diop"),
     *                     @OA\Property(property="destinataire", type="string", example="Fatou Sarr"),
     *                     @OA\Property(property="sens_transfert", type="string", enum={"retrait", "depot"}, example="retrait")
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
                'transaction' => new TransactionResource($transaction->load(['compteEmetteur.user', 'compteDestinataire.user']), $compte)
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
     *                         @OA\Property(property="date", type="string", format="date-time", example="2025-11-10 09:00:00"),
     *                         @OA\Property(property="expediteur", type="string", example="Amadou Diop"),
     *                     @OA\Property(property="destinataire", type="string", example="Fatou Sarr"),
     *                     @OA\Property(property="sens_transfert", type="string", enum={"retrait", "depot"}, example="retrait")
     *                 )
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

            $transactions = $compte->transactionsEmises()
                ->orWhere('compte_destinataire_id', $compte->id)
                ->with([
                    'compteEmetteur.user:id,nom,prenom,telephone',
                    'compteDestinataire.user:id,nom,prenom,telephone',
                    'marchand:id,raison_sociale'
                ])
                ->orderBy('created_at', 'desc')
                ->get(['id', 'reference', 'type', 'montant', 'created_at', 'compte_emetteur_id', 'compte_destinataire_id', 'marchand_id']);

            // Formater les transactions selon les spécifications
            $formattedTransactions = $transactions->map(function ($transaction) use ($compte) {
                return new TransactionResource($transaction, $compte);
            });

            return $this->successResponse(['transactions' => $formattedTransactions], 'Transactions récupérées avec succès');
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
     *                     @OA\Property(property="date", type="string", format="date-time", example="2025-11-10 09:00:00"),
     *                     @OA\Property(property="expediteur", type="string", example="Amadou Diop"),
     *                         @OA\Property(property="destinataire", type="string", example="Fatou Sarr"),
     *                         @OA\Property(property="sens_transfert", type="string", enum={"retrait", "depot"}, example="retrait")
     *                     )
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
                'transaction' => new TransactionResource($transaction->load(['compteEmetteur.user', 'compteDestinataire.user', 'marchand']), $compte)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Aucun compte trouvé', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération de la transaction', 500);
        }
    }
}