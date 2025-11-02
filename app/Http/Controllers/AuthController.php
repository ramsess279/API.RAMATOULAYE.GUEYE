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
     *             @OA\Property(property="password", type="string", example="admin123", description="Mot de passe"),
     *             @OA\Property(property="code", type="string", example="123456", description="Code d'authentification (optionnel pour nouveaux clients)")
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
      *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIzIiwianRpIjoiYzlhYWIyNmMxNjU5YmY5YjQ5MTY5MjBiOTZkMTk3ZmQ2MTdlMDNlZDE5YWMxZWQ0NmU1NjJkZjcxYjFkMjc3MmZmNDgxNmEyN2E4ZDNlYjciLCJpYXQiOjE3NjE3Nzk4OTQuODc3Mzg3LCJuYmYiOjE3NjE3Nzk4OTQuODc3Mzk1LCJleHAiOjE3OTMzMTU4OTQuMjQwODIyLCJzdWIiOiJhMGQ1NmVhZC1mYTA4LTQ5ZDAtOTlhNy0xMmZlNGU4OTg4NTQiLCJzY29wZXMiOltdfQ.FCyNG7PRvmYuV93q01WyUrXwistklxBnwZCPDS_eOyEMybdP1jJNGmeqDGCRZkFI249g8mxbn-or9UR-J1UgIsUP9wBqAMptSSrNzT7UdFRVIw6DHOde9BnDOpISRxX_7Ib_rkMHy7lo_SNx96SIbzETAtZODFS02RrgqaUAYW3WQJCIE4NyDpMDr-zNLHxy_v4hMOVsf-6a0MBLUR0ftXSyE9_xOvKtfwKycrrmMSDqQgUXlo4vgHTqnaM-2b_uNJYiW801LN3QIf4lgXPZYY53Qd1UgTEdaikwL4O3dAy4g5Ke__0zYNlX0h54ThWEsD_TmU2Hj8b_nnMG-D3fHet6SZ4jkm7LPhOLnm-MQvOXMfz7qUjO29pprXeC3va__ZQ76TSdUIaKUWYBDl-1OFhwpvkxEQGgynwY6FxxqKR4BHlE3uAJITviHCSv02S-4fOvWAPCE-FIrDFt66ANFCQ7oufq73vLNy4k3BFsMHMiGYrx_LcsIRF_cUAy0fQUWtgW86hSdyFfvNCMchdRHswFJ29qAwvxx2dG_2YdsW4p_x9oSS95kG18qSz6oe7ODk1jmjAuR9YOh8DbQISdRWfIrAz0Gb7soEwRJgcMi_ZE4ubrEDohMEX7rSPXw3Xqjqx5-6SXkVq1iPU_hlpzxHMxGantlrJUxoFcvGiUvVM"),
      *                 @OA\Property(property="refresh_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIzIiwianRpIjoiYzlhYWIyNmMxNjU5YmY5YjQ5MTY5MjBiOTZkMTk3ZmQ2MTdlMDNlZDE5YWMxZWQ0NmU1NjJkZjcxYjFkMjc3MmZmNDgxNmEyN2E4ZDNlYjciLCJpYXQiOjE3NjE3Nzk4OTQuODc3Mzg3LCJuYmYiOjE3NjE3Nzk4OTQuODc3Mzk1LCJleHAiOjE3OTMzMTU4OTQuMjQwODIyLCJzdWIiOiJhMGQ1NmVhZC1mYTA4LTQ5ZDAtOTlhNy0xMmZlNGU4OTg4NTQiLCJzY29wZXMiOltdfQ.FCyNG7PRvmYuV93q01WyUrXwistklxBnwZCPDS_eOyEMybdP1jJNGmeqDGCRZkFI249g8mxbn-or9UR-J1UgIsUP9wBqAMptSSrNzT7UdFRVIw6DHOde9BnDOpISRxX_7Ib_rkMHy7lo_SNx96SIbzETAtZODFS02RrgqaUAYW3WQJCIE4NyDpMDr-zNLHxy_v4hMOVsf-6a0MBLUR0ftXSyE9_xOvKtfwKycrrmMSDqQgUXlo4vgHTqnaM-2b_uNJYiW801LN3QIf4lgXPZYY53Qd1UgTEdaikwL4O3dAy4g5Ke__0zYNlX0h54ThWEsD_TmU2Hj8b_nnMG-D3fHet6SZ4jkm7LPhOLnm-MQvOXMfz7qUjO29pprXeC3va__ZQ76TSdUIaKUWYBDl-1OFhwpvkxEQGgynwY6FxxqKR4BHlE3uAJITviHCSv02S-4fOvWAPCE-FIrDFt66ANFCQ7oufq73vLNy4k3BFsMHMiGYrx_LcsIRF_cUAy0fQUWtgW86hSdyFfvNCMchdRHswFJ29qAwvxx2dG_2YdsW4p_x9oSS95kG18qSz6oe7ODk1jmjAuR9YOh8DbQISdRWfIrAz0Gb7soEwRJgcMi_ZE4ubrEDohMEX7rSPXw3Xqjqx5-6SXkVq1iPU_hlpzxHMxGantlrJUxoFcvGiUvVM"),
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
            'password' => 'required|string|min:6',
            'code' => 'nullable|string|size:6' // Code optionnel pour nouveaux clients
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Les données fournies sont invalides', 422, $validator->errors());
        }

        // Trouver l'utilisateur par email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('Identifiants invalides', 401);
        }

        // Vérifier le mot de passe
        if (!Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Identifiants invalides', 401);
        }

        // Pour les nouveaux clients (non vérifiés), vérifier le code d'authentification
        if (!$user->is_verified && $user->verification_code) {
            if (empty($request->code)) {
                return $this->errorResponse('Code d\'authentification requis pour votre première connexion', 401);
            }

            if ($request->code !== $user->verification_code) {
                return $this->errorResponse('Code d\'authentification invalide', 401);
            }

            // Vérifier si le code n'a pas expiré
            if ($user->verification_code_expires_at && now()->isAfter($user->verification_code_expires_at)) {
                return $this->errorResponse('Code d\'authentification expiré', 401);
            }

            // Marquer l'utilisateur comme vérifié et supprimer le code
            $user->is_verified = true;
            $user->verification_code = null;
            $user->verification_code_expires_at = null;
            $user->save();
        }

        // Authentifier l'utilisateur manuellement
        Auth::login($user);

        // Créer le token d'accès avec Passport
        $tokenResult = $user->createToken('Personal Access Token');
        $accessToken = $tokenResult->accessToken;

        // Créer le refresh token
        $refreshTokenResult = $user->createToken('Refresh Token');
        $refreshToken = $refreshTokenResult->accessToken;

        // Préparer la réponse
        $data = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
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
