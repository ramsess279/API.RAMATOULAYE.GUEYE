<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Client;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cookie;
use Laravel\Passport\Token;

/**
 * @OA\Info(
 *     title="API Gestion Bancaire - Authentification",
 *     version="1.0.0",
 *     description="API d'authentification pour la gestion bancaire",
 *     @OA\Contact(
 *         email="contact@ramatoulaye.gueye.com"
 *     )
 * )
 */
class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur (Admin ou Client) et retourne les tokens d'accès",
     *     operationId="login",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@banque.com", description="Email de l'utilisateur"),
     *             @OA\Property(property="password", type="string", example="password123", description="Mot de passe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nom", type="string", example="Dupont"),
     *                     @OA\Property(property="prenom", type="string", example="Jean"),
     *                     @OA\Property(property="email", type="string", example="admin@banque.com"),
     *                     @OA\Property(property="role", type="string", enum={"admin", "client"}, example="admin")
     *                 ),
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="refresh_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Identifiants invalides")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données de validation invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Les données fournies sont invalides', 422, $validator->errors());
        }

        // Tentative d'authentification
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Identifiants invalides', 401);
        }

        $user = Auth::user();

        // Créer le token d'accès
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        // Définir l'expiration du token (1 heure)
        $token->expires_at = now()->addHour();
        $token->save();

        // Créer le refresh token
        $refreshToken = $user->createToken('Refresh Token');
        $refreshToken->token->expires_at = now()->addDays(30); // 30 jours
        $refreshToken->token->save();

        // Déterminer le rôle de l'utilisateur
        $role = $this->getUserRole($user);

        // Préparer la réponse
        $data = [
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'role' => $role
            ],
            'access_token' => $tokenResult->accessToken,
            'refresh_token' => $refreshToken->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ];

        // Créer la réponse avec le cookie
        $response = $this->successResponse($data, 'Connexion réussie');

        // Stocker le token dans un cookie sécurisé
        $response->withCookie(cookie(
            'access_token',
            $tokenResult->accessToken,
            60, // 1 heure
            '/',
            null,
            true, // secure
            true  // httpOnly
        ));

        return $response;
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     summary="Rafraîchir le token d'accès",
     *     description="Génère un nouveau token d'accès en utilisant le refresh token",
     *     operationId="refresh",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...", description="Refresh token valide")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token rafraîchi"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Refresh token invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Refresh token invalide")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Refresh token requis', 422, $validator->errors());
        }

        // Trouver le token de rafraîchissement
        $refreshToken = Token::where('id', $request->refresh_token)->first();

        if (!$refreshToken || $refreshToken->expires_at < now()) {
            return $this->errorResponse('Refresh token invalide ou expiré', 401);
        }

        $user = $refreshToken->user;

        // Révoquer l'ancien token d'accès
        $user->tokens()->where('name', 'Personal Access Token')->delete();

        // Créer un nouveau token d'accès
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $token->expires_at = now()->addHour();
        $token->save();

        $data = [
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ];

        return $this->successResponse($data, 'Token rafraîchi avec succès');
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="Déconnexion utilisateur",
     *     description="Invalide le token d'accès actuel de l'utilisateur",
     *     operationId="logout",
     *     tags={"Authentification"},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        // Révoquer le token actuel
        $request->user()->token()->revoke();

        // Supprimer le cookie
        $response = $this->successResponse(null, 'Déconnexion réussie');
        $response->withCookie(Cookie::forget('access_token'));

        return $response;
    }

    /**
     * Détermine le rôle de l'utilisateur connecté
     */
    private function getUserRole(User $user): string
    {
        // Vérifier si c'est un admin
        if (Admin::where('user_id', $user->id)->exists()) {
            return 'admin';
        }

        // Vérifier si c'est un client
        if (Client::where('user_id', $user->id)->exists()) {
            return 'client';
        }

        return 'unknown';
    }
}
