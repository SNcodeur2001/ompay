<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ChangePinRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\SetPinRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Info(
 *     title="OM PAY API",
 *     version="1.0.0",
 *     description="API de paiement mobile OM PAY avec authentification OTP et gestion de comptes"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement"
 * )
 *
 * @OA\Server(
 *     url="https://ompay-isuf.onrender.com/api",
 *     description="Serveur de production"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
    use ApiResponseTrait;

    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @OA\Post(
      *     path="/auth/register",
      *     summary="Inscription d'un nouvel utilisateur",
      *     description="Crée un compte utilisateur, génère un QR code et envoie un OTP par email",
      *     operationId="register",
      *     tags={"Authentification"},
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"nom", "prenom", "telephone", "email"},
      *             @OA\Property(property="nom", type="string", example="Diop", description="Nom de l'utilisateur"),
      *             @OA\Property(property="prenom", type="string", example="Amadou", description="Prénom de l'utilisateur"),
      *             @OA\Property(property="telephone", type="string", example="781562041", description="Numéro de téléphone (9 chiffres)"),
      *             @OA\Property(property="email", type="string", example="mapathendiaye542@gmail.com", description="Adresse email de l'utilisateur")
      *         )
      *     ),
      *     @OA\Response(
      *         response=201,
      *         description="Utilisateur créé avec succès",
      *         @OA\JsonContent(
      *             @OA\Property(property="success", type="boolean", example=true),
      *             @OA\Property(property="message", type="string", example="Utilisateur créé avec succès. Vérifiez votre email pour le code OTP.")
      *         )
      *     ),
      *     @OA\Response(
      *         response=500,
      *         description="Erreur lors de l'inscription"
      *     )
      * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->validated());

            return $this->successResponse(
                [],
                'Utilisateur créé avec succès. Vérifiez votre email pour le code OTP.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de l\'inscription', 500);
        }
    }

    /**
     * @OA\Post(
      *     path="/auth/verify-otp",
      *     summary="Vérification du code OTP",
      *     description="Valide le code OTP reçu par email et définit un PIN temporaire (0000)",
      *     operationId="verifyOtp",
      *     tags={"Authentification"},
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"telephone", "otp"},
      *             @OA\Property(property="telephone", type="string", example="781562041", description="Numéro de téléphone"),
      *             @OA\Property(property="otp", type="string", example="123456", description="Code OTP à 6 chiffres")
      *         )
      *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vérification réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vérification réussie. Votre PIN temporaire est 0000. Veuillez le changer immédiatement."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", example="bearer-token-string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Code OTP invalide ou expiré"
     *     )
     * )
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $user = $this->authService->verifyOtp(
                $validated['telephone'],
                $validated['otp']
            );

            if (!$user) {
                return $this->errorResponse('Code OTP invalide ou expiré', 400);
            }

            return $this->successResponse([
                'access_token' => $user->access_token,
                'token_type' => 'Bearer',
            ], 'Vérification réussie. Votre PIN temporaire est 0000. Veuillez le changer immédiatement.');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la vérification OTP', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/set-pin",
     *     summary="Définition du PIN définitif",
     *     description="Permet à l'utilisateur de définir son PIN définitif après la première connexion avec le PIN temporaire",
     *     operationId="setPin",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code_pin"},
     *             @OA\Property(property="code_pin", type="string", example="1234", description="Nouveau code PIN définitif à 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PIN définitif défini avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="PIN définitif défini avec succès. Vous pouvez maintenant utiliser votre compte normalement.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="PIN déjà défini ou invalide"
     *     )
     * )
     */
    public function setPin(SetPinRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                \Log::error("Set PIN: No authenticated user");
                return $this->errorResponse('Utilisateur non authentifié', 401);
            }

            $user = $user->fresh();
            $validated = $request->validated();

            \Log::info("Set PIN attempt for user {$user->telephone}", [
                'setup_completed' => $user->setup_completed,
                'current_pin_hash' => $user->code_pin,
                'new_pin' => $validated['code_pin']
            ]);

            $success = $this->authService->setDefinitivePin($user, $validated['code_pin']);

            if (!$success) {
                \Log::warning("Set PIN failed for user {$user->telephone} - setup already completed");
                return $this->errorResponse('Vous avez déjà défini votre PIN définitif', 400);
            }

            \Log::info("Set PIN successful for user {$user->telephone}");
            return $this->successResponse([], 'PIN définitif défini avec succès. Vous pouvez maintenant utiliser votre compte normalement.');
        } catch (\Exception $e) {
            \Log::error("Set PIN error for user " . (auth()->user() ? auth()->user()->telephone : 'unknown'), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Erreur lors de la définition du PIN', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur vérifié avec son numéro de téléphone et code PIN (ou OTP pour la première connexion)",
     *     operationId="login",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone"},
     *             @OA\Property(property="telephone", type="string", example="781562041", description="Numéro de téléphone"),
     *             @OA\Property(property="code_pin", type="string", example="1234", description="Code PIN à 4 chiffres (pour les connexions après définition du PIN)"),
     *             @OA\Property(property="otp", type="string", example="123456", description="Code OTP à 6 chiffres (pour la première connexion après vérification)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", example="bearer-token-string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Téléphone ou code PIN incorrect"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Compte non vérifié"
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $user = $this->authService->login(
                $validated['telephone'],
                $validated['code_pin'] ?? null,
                $validated['otp'] ?? null
            );

            if (!$user) {
                return $this->errorResponse('Téléphone, code PIN ou code OTP incorrect', 401);
            }

            return $this->successResponse([
                'access_token' => $user->access_token,
                'token_type' => 'Bearer',
            ], 'Connexion réussie');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la connexion', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Déconnexion utilisateur",
     *     description="Invalide le token d'accès actuel",
     *     operationId="logout",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token invalide"
     *     )
     * )
     */
    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout(auth()->user());

            return $this->successResponse([], 'Déconnexion réussie');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la déconnexion', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/change-pin",
     *     summary="Changement du code PIN",
     *     description="Permet à l'utilisateur de changer son code PIN",
     *     operationId="changePin",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"old_pin", "new_pin"},
     *             @OA\Property(property="old_pin", type="string", example="1234", description="Ancien code PIN"),
     *             @OA\Property(property="new_pin", type="string", example="5678", description="Nouveau code PIN à 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code PIN modifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code PIN modifié avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ancien code PIN incorrect"
     *     )
     * )
     */
    public function changePin(ChangePinRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $success = $this->authService->changePin(
                auth()->user(),
                $validated['old_pin'],
                $validated['new_pin']
            );

            if (!$success) {
                return $this->errorResponse('Ancien code PIN incorrect', 400);
            }

            return $this->successResponse([], 'Code PIN modifié avec succès');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors du changement de PIN', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     summary="Informations de l'utilisateur connecté",
     *     description="Retourne les informations de l'utilisateur actuellement authentifié",
     *     operationId="me",
     *     tags={"Authentification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations utilisateur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string", example="uuid-string"),
     *                     @OA\Property(property="nom", type="string", example="Diop"),
     *                     @OA\Property(property="prenom", type="string", example="Amadou"),
     *                     @OA\Property(property="telephone", type="string", example="771234567"),
     *                     @OA\Property(property="compte", type="object",
     *                         @OA\Property(property="numero_compte", type="string", example="OM-2025-AB12-CD34"),
     *                         @OA\Property(property="solde", type="number", format="float", example=15000.50)
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function me(): JsonResponse
    {
        try {
            $user = $this->authService->getAuthenticatedUser();

            return $this->successResponse([
                'user' => $user->load('compte')
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération des données utilisateur', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/auth/test-brevo",
     *     summary="Test de connexion Brevo",
     *     description="Teste la connectivité avec l'API Brevo",
     *     operationId="testBrevo",
     *     tags={"Authentification"},
     *     @OA\Response(
     *         response=200,
     *         description="Résultat du test",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie")
     *         )
     *     )
     * )
     */
    public function testBrevo(): JsonResponse
    {
        try {
            $result = \App\Services\BrevoMailService::testConnection();

            if ($result['success']) {
                return $this->successResponse($result, 'Connexion Brevo réussie');
            } else {
                return $this->errorResponse('Échec de connexion Brevo', 500);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors du test Brevo', 500);
        }
    }
}