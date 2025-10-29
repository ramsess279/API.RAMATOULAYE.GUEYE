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

/**
 * Authentification Controller
 * Gère l'authentification des utilisateurs (Admin/Client)
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
     *             @OA\Property(property="password", type="string", example="admin123", description="Mot de passe")
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

        // Créer le token d'accès avec Passport
        $tokenResult = $user->createToken('Personal Access Token');
        $accessToken = $tokenResult->accessToken ?? $tokenResult->token;

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
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ];

        // Créer la réponse avec le cookie
        $response = $this->successResponse($data, 'Connexion réussie');

        // Stocker le token dans un cookie sécurisé
        $response->withCookie(cookie(
            'access_token',
            $accessToken,
            60, // 1 heure
            '/',
            null,
            true, // secure
            true  // httpOnly
        ));

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
