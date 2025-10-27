<?php

namespace App\Http\Controllers;

use App\Services\CompteService;
use App\Traits\ApiResponseTrait;
use App\Exceptions\ValidationException;
use App\Exceptions\CompteNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
class CompteController extends Controller
{
    use ApiResponseTrait;

    private CompteService $compteService;

    public function __construct(CompteService $compteService)
    {
        $this->compteService = $compteService;
    }

    /**
     * @OA\Get(
     *     path="/comptes",
     *     summary="Lister tous les comptes bancaires",
     *     description="Récupère la liste paginée de tous les comptes bancaires selon les permissions de l'utilisateur",
     *     operationId="getComptes",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de compte",
     *         required=false,
     *         @OA\Schema(type="string", enum={"epargne", "cheque"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "bloque", "ferme"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro de compte",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Tri par champ",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dateCreation", "solde", "titulaire"})
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre de tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Données récupérées avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                     @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                     @OA\Property(property="type", type="string", enum={"epargne", "cheque"}),
     *                     @OA\Property(property="solde", type="number", format="float", example=1250000),
     *                     @OA\Property(property="devise", type="string", example="FCFA"),
     *                     @OA\Property(property="dateCreation", type="string", format="date-time"),
     *                     @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}),
     *                     @OA\Property(property="motifBlocage", type="string", nullable=true),
     *                     @OA\Property(
     *                         property="metadata",
     *                         @OA\Property(property="derniereModification", type="string", format="date-time"),
     *                         @OA\Property(property="version", type="integer", example=1)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 @OA\Property(property="currentPage", type="integer"),
     *                 @OA\Property(property="totalPages", type="integer"),
     *                 @OA\Property(property="totalItems", type="integer"),
     *                 @OA\Property(property="itemsPerPage", type="integer"),
     *                 @OA\Property(property="hasNext", type="boolean"),
     *                 @OA\Property(property="hasPrevious", type="boolean")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 @OA\Property(property="self", type="string"),
     *                 @OA\Property(property="next", type="string", nullable=true),
     *                 @OA\Property(property="first", type="string"),
     *                 @OA\Property(property="last", type="string"),
     *                 @OA\Property(property="previous", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Paramètres invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorisé - Token manquant ou invalide"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données de validation invalides"
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Trop de requêtes - Rate limiting"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // TODO: Implémenter l'authentification et l'autorisation
            // Pour l'instant, on récupère tous les comptes sans restriction

            // Récupération des comptes paginés
            $comptes = $this->compteService->getComptesPagines($request);

            // Transformation des données
            $data = $this->compteService->transformComptesData($comptes);

            // Utilisation directe des méthodes du paginator Laravel
            return $this->paginatedResponse(
                $data,
                $comptes->currentPage(),
                $comptes->lastPage(),
                $comptes->total(),
                $comptes->perPage(),
                $comptes->hasMorePages(),
                $comptes->currentPage() > 1,
                [
                    'self' => $comptes->url($comptes->currentPage()),
                    'next' => $comptes->nextPageUrl(),
                    'first' => $comptes->url(1),
                    'last' => $comptes->url($comptes->lastPage()),
                    'previous' => $comptes->previousPageUrl(),
                ]
            );

        } catch (ValidationException $e) {
            return $e->render($request);
        } catch (\Exception $e) {
            return $this->errorResponse('Une erreur inattendue est survenue.', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/comptes/{compteId}",
     *     summary="Récupérer un compte spécifique",
     *     description="Récupère les détails d'un compte bancaire spécifique selon les permissions de l'utilisateur",
     *     operationId="getCompte",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte bancaire",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="cc2577b1-bfce-4d0c-9250-50739c057bb0")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du compte récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Données récupérées avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", enum={"epargne", "cheque"}),
     *                 @OA\Property(property="solde", type="number", format="float", example=1250000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time"),
     *                 @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}),
     *                 @OA\Property(property="motifBlocage", type="string", nullable=true),
     *                 @OA\Property(
     *                     property="metadata",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time"),
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="error",
     *                 @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas"),
     *                 @OA\Property(
     *                     property="details",
     *                     @OA\Property(property="compteId", type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Paramètres invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorisé - Token manquant ou invalide"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données de validation invalides"
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Trop de requêtes - Rate limiting"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function show(string $compteId): JsonResponse
    {
        try {
            // TODO: Implémenter l'authentification et l'autorisation
            // Pour l'instant, on récupère le compte sans restriction

            // Récupération du compte par ID
            $compte = $this->compteService->getCompteById($compteId);

            // Transformation des données
            $data = $this->compteService->transformCompteData($compte);

            return $this->successResponse($data, 'Données récupérées avec succès');

        } catch (CompteNotFoundException $e) {
            return $e->render(request());
        } catch (\Exception $e) {
            return $this->errorResponse('Une erreur inattendue est survenue.', 500);
        }
    }
}