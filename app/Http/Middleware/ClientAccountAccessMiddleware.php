<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Client;
use App\Models\CompteModel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de contrôle d'accès aux comptes pour les clients
 * Vérifie que le client n'accède qu'à ses propres comptes via son CNI
 */
class ClientAccountAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $compteIdParam  Le nom du paramètre contenant l'ID du compte (par défaut 'compteId')
     */
    public function handle(Request $request, Closure $next, string $compteIdParam = 'compteId'): Response
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié',
                'error' => [
                    'code' => 'AUTHENTICATION_REQUIRED',
                    'message' => 'Vous devez être connecté pour accéder à cette ressource'
                ]
            ], 401);
        }

        // Vérifier si c'est un admin - ils ont accès à tout
        if (\App\Models\Admin::where('user_id', $user->id)->exists()) {
            return $next($request);
        }

        // Vérifier si c'est un client
        $client = Client::where('user_id', $user->id)->first();
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client non trouvé',
                'error' => [
                    'code' => 'CLIENT_NOT_FOUND',
                    'message' => 'Votre profil client n\'a pas été trouvé'
                ]
            ], 404);
        }

        // Récupérer l'ID du compte depuis les paramètres de la route
        $compteId = $request->route($compteIdParam);
        if (!$compteId) {
            return response()->json([
                'success' => false,
                'message' => 'ID du compte manquant',
                'error' => [
                    'code' => 'MISSING_COMPTE_ID',
                    'message' => 'L\'identifiant du compte est requis'
                ]
            ], 400);
        }

        // Vérifier que le compte appartient bien au client via son CNI
        $compte = CompteModel::withTrashed()->find($compteId);
        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé',
                'error' => [
                    'code' => 'COMPTE_NOT_FOUND',
                    'message' => 'Le compte demandé n\'existe pas'
                ]
            ], 404);
        }

        // Vérifier que le CNI du compte correspond au CNI du client
        if ($compte->client->cni !== $client->cni) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'Vous n\'avez pas accès à ce compte'
                ]
            ], 403);
        }

        return $next($request);
    }
}