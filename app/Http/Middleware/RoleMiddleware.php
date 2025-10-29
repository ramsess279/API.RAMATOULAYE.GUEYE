<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de contrôle des rôles
 * Vérifie que l'utilisateur a le rôle requis pour accéder à la ressource
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role  Le rôle requis (admin, client)
     */
    public function handle(Request $request, Closure $next, string $role): Response
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

        // Vérifier le rôle de l'utilisateur
        $userRole = $this->getUserRole($user);

        if ($userRole !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé - Permissions insuffisantes',
                'error' => [
                    'code' => 'INSUFFICIENT_PERMISSIONS',
                    'message' => "Cette ressource nécessite le rôle '{$role}', mais vous avez le rôle '{$userRole}'"
                ]
            ], 403);
        }

        return $next($request);
    }

    /**
     * Détermine le rôle de l'utilisateur connecté
     */
    private function getUserRole($user): string
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
