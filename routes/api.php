<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/**
 * @OA\Info(
 *     title="API Gestion Bancaire",
 *     version="1.0.0",
 *     description="API REST pour la gestion des comptes bancaires",
 *     @OA\Contact(
 *         email="contact@ramatoulaye.gueye.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api/v1",
 *     description="Serveur de développement local"
 * )
 *
 * @OA\Server(
 *     url="https://api-ramatoulaye-gueye-0d8p.onrender.com/api/v1",
 *     description="Serveur de production"
 * ),
 * @OA\Server(
 *     url="https://api-ramatoulaye-gueye-0d8p.onrender.com/api/v1",
 *     description="Serveur de production alternatif"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

// Routes API version 1
Route::prefix('v1')->group(function () {

    // Route de test pour diagnostiquer les problèmes Render
    Route::get('/test', function () {
        try {
            // Test de base de données
            $dbStatus = 'OK';
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                $dbStatus = 'ERROR: ' . $e->getMessage();
            }

            // Test des extensions PHP
            $extensions = [
                'pdo_pgsql' => extension_loaded('pdo_pgsql'),
                'pgsql' => extension_loaded('pgsql'),
                'mbstring' => extension_loaded('mbstring'),
                'openssl' => extension_loaded('openssl'),
            ];

            return response()->json([
                'status' => 'ok',
                'message' => 'Test route fonctionne',
                'timestamp' => now()->toISOString(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database' => $dbStatus,
                'extensions' => $extensions,
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ], 500);
        }
    });

    // Routes d'authentification
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Route pour l'authentification de l'utilisateur
    /**
     * @OA\Get(
     *     path="/user",
     *     summary="Récupérer les informations de l'utilisateur authentifié",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations de l'utilisateur"
     *     )
     * )
     */
    Route::middleware('auth.api')->get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $request->user()->id,
                'nom' => $request->user()->nom,
                'prenom' => $request->user()->prenom,
                'email' => $request->user()->email,
                'role' => $request->user()->role
            ]
        ]);
    });

    // Routes pour les comptes bancaires
    Route::middleware(['auth.api', 'logging'])->group(function () {
        // Routes accessibles aux admins et clients
        Route::get('/comptes', [CompteController::class, 'index'])
            ->name('comptes.index');
        Route::get('/comptes/{compteId}', [CompteController::class, 'show'])
            ->name('comptes.show');

        // Routes réservées aux admins uniquement
        Route::middleware('role:admin')->group(function () {
            Route::post('/comptes', [CompteController::class, 'store'])
                ->name('comptes.store');
            Route::patch('/comptes/{numeroCompte}', [CompteController::class, 'update'])
                ->name('comptes.update');
            Route::delete('/comptes/{compteId}', [CompteController::class, 'destroy'])
                ->name('comptes.destroy');
            Route::post('/comptes/{compteId}/bloquer', [CompteController::class, 'bloquer'])
                ->name('comptes.bloquer');
        });

    });

    // Routes pour les clients
    // TODO: Créer le ClientController
    // Route::middleware(['logging'])->group(function () {
    //     Route::patch('/clients/{clientId}', [ClientController::class, 'update'])
    //         ->name('clients.update');
    // });


});
