<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompteController;

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
 *     url="http://api.ramatoulaye.gueye.com/api/v1",
 *     description="Serveur de production"
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api/v1",
 *     description="Serveur de développement local"
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
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    // Routes pour les comptes bancaires
    Route::middleware(['rating'])->group(function () {
        Route::get('/comptes', [CompteController::class, 'index'])
            ->name('comptes.index');
        Route::get('/comptes/{compteId}', [CompteController::class, 'show'])
            ->name('comptes.show');
    });
});
