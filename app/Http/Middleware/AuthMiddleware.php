<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware d'authentification Passport
 * Vérifie que l'utilisateur est connecté avec un token valide
 */
class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est authentifié via Passport
        if (!Auth::guard('api')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Token d\'authentification manquant ou invalide',
                'error' => [
                    'code' => 'AUTHENTICATION_REQUIRED',
                    'message' => 'Vous devez être connecté pour accéder à cette ressource'
                ]
            ], 401);
        }

        return $next($request);
    }
}
