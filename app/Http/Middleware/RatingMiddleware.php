<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RatingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            // Vérifier si l'utilisateur a dépassé le taux limite
            $this->checkRateLimit($user, $request);
        }

        return $next($request);
    }

    /**
     * Vérifie et enregistre les utilisateurs qui ont atteint le taux limite
     *
     * @param mixed $user
     * @param Request $request
     * @return void
     */
    private function checkRateLimit($user, Request $request): void
    {
        // Récupérer les informations de taux limite depuis les headers ou la configuration
        $rateLimitInfo = $request->header('X-RateLimit-Remaining');

        // Si le taux limite est atteint (remaining = 0 ou négatif)
        if ($rateLimitInfo !== null && (int)$rateLimitInfo <= 0) {
            // Enregistrer l'événement dans les logs
            Log::warning('Rate limit atteint', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role ?? 'unknown',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'timestamp' => now()->toISOString(),
            ]);

            // Ici, vous pourriez également :
            // - Envoyer une notification à l'admin
            // - Stocker dans une table dédiée pour analyse
            // - Bloquer temporairement l'utilisateur
            // - etc.
        }
    }
}