<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
   /**
    * Handle an incoming request.
    *
    * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
    */
   public function handle(Request $request, Closure $next): Response
   {
       $startTime = microtime(true);

       $response = $next($request);

       $endTime = microtime(true);
       $duration = round(($endTime - $startTime) * 1000, 2); // en millisecondes

       // Log des informations de l'opération
       Log::info('Opération API exécutée', [
           'method' => $request->method(),
           'url' => $request->fullUrl(),
           'host' => $request->getHost(),
           'operation' => $this->getOperationName($request),
           'status_code' => $response->getStatusCode(),
           'duration_ms' => $duration,
           'user_agent' => $request->userAgent(),
           'ip' => $request->ip(),
           'timestamp' => now()->toISOString()
       ]);

       return $response;
   }

   /**
    * Détermine le nom de l'opération basée sur la route
    */
   private function getOperationName(Request $request): string
   {
       $route = $request->route();

       if ($route) {
           $action = $route->getAction();

           // Si c'est une route nommée
           if (isset($action['as'])) {
               return $action['as'];
           }

           // Si c'est un contrôleur
           if (isset($action['controller'])) {
               return $action['controller'];
           }
       }

       // Fallback sur la méthode HTTP + URI
       return $request->method() . ' ' . $request->path();
   }
}