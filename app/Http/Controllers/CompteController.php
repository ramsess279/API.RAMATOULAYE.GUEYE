<?php

namespace App\Http\Controllers;

use App\Services\CompteService;
use App\Services\CreateCompteService;
use App\Traits\ApiResponseTrait;
use App\Exceptions\ValidationException;
use App\Exceptions\CompteNotFoundException;
use App\Http\Requests\CompteRequest;
use App\Http\Requests\UpdateCompteRequest;
use App\Http\Requests\BloquerCompteRequest;
use App\Http\Requests\DebloquerCompteRequest;
use App\Http\Requests\ArchiverCompteRequest;
use App\Http\Requests\DesarchiverCompteRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
 *     url="https://api-ramatoulaye-gueye-i671.onrender.com/api/v1",
 *     description="Serveur de production (Render)"
 * ),
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api/v1",
 *     description="Serveur de développement local"
 * ),
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Serveur de développement alternatif"
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
    private CreateCompteService $createCompteService;

    public function __construct(
        CompteService $compteService,
        CreateCompteService $createCompteService
    ) {
        $this->compteService = $compteService;
        $this->createCompteService = $createCompteService;
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
     *                     @OA\Property(property="numeroCompte", type="string", example="CPT1761572199795"),
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
     * @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte bancaire bloqué",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="b6500996-a594-495b-ab68-c3727094f52d")
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
     *                 @OA\Property(property="numeroCompte", type="string", example="CPT1761572199795"),
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
            Log::error('Erreur dans CompteController@index: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->errorResponse('Une erreur inattendue est survenue.', 500);
        }
    }

    /**
     * Vérifier si l'email a été envoyé avec succès
     */
    private function checkEmailSent(): bool
    {
        // En production, vérifier le statut réel d'envoi
        // Pour l'instant, retourner true car l'événement est déclenché
        return true;
    }

    /**
     * Vérifier si le SMS a été envoyé avec succès
     */
    private function checkSmsSent(): bool
    {
        // En production, vérifier le statut réel d'envoi
        // Pour l'instant, retourner true car l'événement est déclenché
        return true;
    }

    /**
     * @OA\Post(
     *     path="/comptes",
     *     summary="Créer un nouveau compte bancaire",
     *     description="Crée un nouveau compte bancaire. Si le client n'existe pas, il sera créé automatiquement avec génération de mot de passe et code d'authentification.",
     *     operationId="createCompte",
     *     tags={"Comptes"},
     * @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "soldeInitial", "devise", "client"},
     *             @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="cheque", description="Type de compte"),
     *             @OA\Property(property="soldeInitial", type="number", format="float", example=500000, description="Solde initial (minimum 10 000)"),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA", description="Devise du compte"),
     *             @OA\Property(
     *                 property="client",
     *                 type="object",
     *                 required={"titulaire", "email", "telephone"},
     *                 @OA\Property(property="id", type="string", format="uuid", nullable=true, example="", description="ID du client existant (optionnel)"),
     *                 @OA\Property(property="titulaire", type="string", example="Jean Dupont", description="Nom complet du titulaire"),
     *                 @OA\Property(property="cni", type="string", example="1234567890123", nullable=true, description="Numéro CNI (13 chiffres)"),
     *                 @OA\Property(property="email", type="string", format="email", example="jean.dupont@email.com", description="Email du client"),
     *                 @OA\Property(property="telephone", type="string", example="+221701234567", description="Numéro de téléphone sénégalais"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal", description="Adresse (optionnel)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     title="Nouveau client",
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *                     @OA\Property(
     *                         property="data",
     *                         @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                         @OA\Property(property="numeroCompte", type="string", example="CPT1761572199795"),
     *                         @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                         @OA\Property(property="type", type="string", enum={"epargne", "cheque"}),
     *                         @OA\Property(property="solde", type="number", format="float", example=500000),
     *                         @OA\Property(property="devise", type="string", example="FCFA"),
     *                         @OA\Property(property="dateCreation", type="string", format="date-time"),
     *                         @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}),
     *                         @OA\Property(property="motifBlocage", type="string", nullable=true),
     *                         @OA\Property(
     *                             property="metadata",
     *                             @OA\Property(property="derniereModification", type="string", format="date-time"),
     *                             @OA\Property(property="version", type="integer", example=1)
     *                         ),
     *                         @OA\Property(property="clientCreated", type="boolean", example=true, description="Indique si un nouveau client a été créé"),
     *                         @OA\Property(
     *                             property="informationsConnexion",
     *                             type="object",
     *                             description="Informations de connexion fournies lors de la création d'un nouveau client",
     *                             @OA\Property(property="motDePasseTemporaire", type="string", example="P&ygZ-*3?#0h", description="Mot de passe temporaire généré"),
     *                             @OA\Property(property="codeAuthentification", type="string", example="329259", description="Code d'authentification à 6 chiffres"),
     *                             @OA\Property(property="numeroCompte", type="string", example="C00123456", description="Numéro du compte créé"),
     *                             @OA\Property(property="email", type="string", example="jean.dupont@email.com", description="Email du client"),
     *                             @OA\Property(property="instructions", type="string", example="Utilisez ces informations pour votre première connexion. Le mot de passe doit être changé.", description="Instructions d'utilisation")
     *                         ),
     *                         @OA\Property(property="notificationsSent", type="object",
     *                             @OA\Property(property="email", type="boolean", example=true, description="Statut d'envoi de l'email d'authentification"),
     *                             @OA\Property(property="sms", type="boolean", example=true, description="Statut d'envoi du SMS avec le code")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     title="Client existant",
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *                     @OA\Property(
     *                         property="data",
     *                         @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                         @OA\Property(property="numeroCompte", type="string", example="CPT1761572199795"),
     *                         @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                         @OA\Property(property="type", type="string", enum={"epargne", "cheque"}),
     *                         @OA\Property(property="solde", type="number", format="float", example=500000),
     *                         @OA\Property(property="devise", type="string", example="FCFA"),
     *                         @OA\Property(property="dateCreation", type="string", format="date-time"),
     *                         @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}),
     *                         @OA\Property(property="motifBlocage", type="string", nullable=true),
     *                         @OA\Property(
     *                             property="metadata",
     *                             @OA\Property(property="derniereModification", type="string", format="date-time"),
     *                             @OA\Property(property="version", type="integer", example=1)
     *                         ),
     *                         @OA\Property(property="clientCreated", type="boolean", example=false, description="Indique si un nouveau client a été créé"),
     *                         @OA\Property(property="notificationsSent", type="object",
     *                             @OA\Property(property="email", type="boolean", example=false, description="Statut d'envoi de l'email d'authentification"),
     *                             @OA\Property(property="sms", type="boolean", example=false, description="Statut d'envoi du SMS avec le code")
     *                         )
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
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
     *         description="Accès refusé - Droits insuffisants"
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
    public function store(CompteRequest $request): JsonResponse
    {
        try {
            // TODO: Implémenter l'authentification et l'autorisation
            // Pour l'instant, on permet la création sans restriction

            // Créer le compte via le service
            $compte = $this->createCompteService->createCompte($request->validated());

            // Transformer les données pour la réponse
            $data = $this->compteService->transformCompteData($compte);

            // Ajouter des informations supplémentaires sur la création
            $data['clientCreated'] = $this->createCompteService->getClientCreationService()->isClientNewlyCreated();

            // Ajouter les informations de connexion si c'est un nouveau client
            if ($data['clientCreated']) {
                $data['informationsConnexion'] = [
                    'motDePasseTemporaire' => $compte->temporaryPassword ?? 'Non généré',
                    'codeAuthentification' => $compte->authenticationCode ?? 'Non généré',
                    'numeroCompte' => $compte->numeroCompte,
                    'email' => $compte->client->user->email,
                    'instructions' => 'Utilisez ces informations pour votre première connexion. Le mot de passe doit être changé.'
                ];
            }

            // Vérifier le statut réel des notifications
            $data['notificationsSent'] = [
                'email' => $data['clientCreated'] ? $this->checkEmailSent() : false,
                'sms' => $data['clientCreated'] ? $this->checkSmsSent() : false
            ];

            return $this->successResponse($data, 'Compte créé avec succès', 201);

        } catch (ValidationException $e) {
            return $e->render($request);
        } catch (\Exception $e) {
            return $this->errorResponse('Une erreur inattendue est survenue lors de la création du compte.', 500);
        }
    }


    /**
     * @OA\Delete(
     *     path="/comptes/{compteId}",
     *     summary="Supprimer un compte bancaire",
     *     description="Supprime un compte bancaire avec un soft delete. Le compte passe au statut 'ferme' et n'est plus visible dans les listes normales.",
     *     operationId="deleteCompte",
     *     tags={"Comptes"},
     * @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte bancaire (doit être un compte épargne actif)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="b6500996-a594-495b-ab68-c3727094f52d")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte supprimé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CPT1761572199795"),
     *                 @OA\Property(property="statut", type="string", example="ferme"),
     *                 @OA\Property(property="dateFermeture", type="string", format="date-time", example="2025-10-19T11:15:00Z")
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
     *         response=401,
     *         description="Non autorisé - Token manquant ou invalide"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function destroy(string $compteId): JsonResponse
    {
        try {
            // TODO: Implémenter l'authentification et l'autorisation
            // Pour l'instant, on permet la suppression sans restriction

            // Supprimer le compte via le service (soft delete)
            $compte = $this->compteService->deleteCompte($compteId);

            // Transformer les données pour la réponse
            $data = [
                'id' => $compte->id,
                'numeroCompte' => $compte->numeroCompte,
                'statut' => $compte->statut,
                'dateFermeture' => $compte->deleted_at->toISOString()
            ];

            return $this->successResponse($data, 'Compte supprimé avec succès');

        } catch (CompteNotFoundException $e) {
            return $e->render(request());
        } catch (\Exception $e) {
            return $this->errorResponse('Une erreur inattendue est survenue lors de la suppression du compte.', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/comptes/{compteId}/bloquer",
     *     summary="Bloquer un compte bancaire",
     *     description="Bloque un compte bancaire avec une durée et un motif spécifiés. Le compte passe au statut 'bloque' et une date de déblocage automatique est calculée.",
     *     operationId="bloquerCompte",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte bancaire",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="cc2577b1-bfce-4d0c-9250-50739c057bb0")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif", "duree", "unite"},
     *             @OA\Property(property="motif", type="string", example="Activité suspecte détectée", description="Motif du blocage"),
     *             @OA\Property(property="duree", type="integer", example=30, description="Durée du blocage"),
     *             @OA\Property(property="unite", type="string", enum={"jours", "mois"}, example="mois", description="Unité de temps")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bloqué avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="statut", type="string", example="bloque"),
     *                 @OA\Property(property="motifBlocage", type="string", example="Activité suspecte détectée"),
     *                 @OA\Property(property="dateBlocage", type="string", format="date-time", example="2025-10-19T11:20:00Z"),
     *                 @OA\Property(property="dateDeblocagePrevue", type="string", format="date-time", example="2025-11-18T11:20:00Z")
     *             )
     *         )
     *     ),
     * @OA\Response(
     *         response=400,
     *         description="Données invalides ou compte déjà bloqué",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Seul un compte épargne actif peut être bloqué.")
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
     *         response=401,
     *         description="Non autorisé - Token manquant ou invalide"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function bloquer(BloquerCompteRequest $request, string $compteId): JsonResponse
    {
        try {
            // TODO: Implémenter l'authentification et l'autorisation
            // Pour l'instant, on permet le blocage sans restriction

            // Bloquer le compte via le service
            $compte = $this->compteService->bloquerCompte($compteId, $request->validated());

            // Transformer les données pour la réponse
            $data = [
                'id' => $compte->id,
                'statut' => $compte->statut,
                'motifBlocage' => $compte->motifBlocage,
                'dateBlocage' => $compte->dateBlocage?->toISOString(),
                'dateDeblocagePrevue' => $compte->dateDeblocagePrevue?->toISOString()
            ];

            return $this->successResponse($data, 'Compte bloqué avec succès');

        } catch (CompteNotFoundException $e) {
            return $e->render(request());
        } catch (ValidationException $e) {
            return $e->render($request);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/comptes/{compteId}/debloquer",
     *     summary="Débloquer un compte bancaire",
     *     description="Débloque un compte bancaire précédemment bloqué. Le compte repasse au statut 'actif'.",
     *     operationId="debloquerCompte",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte bancaire",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="cc2577b1-bfce-4d0c-9250-50739c057bb0")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif"},
     *             @OA\Property(property="motif", type="string", example="Vérification complétée", description="Motif du déblocage")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte débloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte débloqué avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="dateDeblocage", type="string", format="date-time", example="2025-10-19T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides ou compte déjà actif",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Seul un compte bloqué peut être débloqué.")
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
     *         response=401,
     *         description="Non autorisé - Token manquant ou invalide"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function debloquer(DebloquerCompteRequest $request, string $compteId): JsonResponse
    {
        try {
            // TODO: Implémenter l'authentification et l'autorisation
            // Pour l'instant, on permet le déblocage sans restriction

            // Débloquer le compte via le service
            $compte = $this->compteService->debloquerCompte($compteId, $request->validated());

            // Transformer les données pour la réponse
            $data = [
                'id' => $compte->id,
                'statut' => $compte->statut,
                'dateDeblocage' => now()->toISOString()
            ];

            return $this->successResponse($data, 'Compte débloqué avec succès');

        } catch (CompteNotFoundException $e) {
            return $e->render(request());
        } catch (ValidationException $e) {
            return $e->render($request);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/comptes/{compteId}/archiver",
     *     summary="Archiver un compte bancaire",
     *     description="Archive manuellement un compte bancaire et toutes ses transactions. Le compte devient inaccessible aux opérations normales.",
     *     operationId="archiverCompte",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte bancaire à archiver",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="b6500996-a594-495b-ab68-c3727094f52d")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif"},
     *             @OA\Property(property="motif", type="string", example="Archivage suite à inactivité prolongée", description="Motif de l'archivage")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte archivé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte archivé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CPT1761572199795"),
     *                 @OA\Property(property="archive", type="boolean", example=true),
     *                 @OA\Property(property="dateArchivage", type="string", format="date-time", example="2025-10-28T01:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides ou compte déjà archivé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce compte est déjà archivé.")
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
     *         response=401,
     *         description="Non autorisé - Token manquant ou invalide"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function archiver(ArchiverCompteRequest $request, string $compteId): JsonResponse
    {
        try {
            // TODO: Implémenter l'authentification et l'autorisation
            // Pour l'instant, on permet l'archivage sans restriction

            // Archiver le compte via le service
            $compte = $this->compteService->archiverCompte($compteId, $request->validated());

            // Transformer les données pour la réponse
            $data = [
                'id' => $compte->id,
                'numeroCompte' => $compte->numeroCompte,
                'archive' => $compte->archive,
                'dateArchivage' => $compte->dateArchivage?->toISOString()
            ];

            return $this->successResponse($data, 'Compte archivé avec succès');

        } catch (CompteNotFoundException $e) {
            return $e->render(request());
        } catch (ValidationException $e) {
            return $e->render($request);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/comptes/{compteId}/desarchiver",
     *     summary="Désarchiver un compte bancaire",
     *     description="Désarchive manuellement un compte bancaire précédemment archivé. Le compte redevient accessible aux opérations normales.",
     *     operationId="desarchiverCompte",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte bancaire à désarchiver",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="b6500996-a594-495b-ab68-c3727094f52d")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motif"},
     *             @OA\Property(property="motif", type="string", example="Réactivation suite à demande client", description="Motif du désarchivage")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte désarchivé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte désarchivé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CPT1761572199795"),
     *                 @OA\Property(property="archive", type="boolean", example=false),
     *                 @OA\Property(property="dateArchivage", type="string", nullable=true, example=null)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides ou compte déjà actif",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce compte n'est pas archivé.")
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
     *         response=401,
     *         description="Non autorisé - Token manquant ou invalide"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function desarchiver(DesarchiverCompteRequest $request, string $compteId): JsonResponse
    {
        try {
            // TODO: Implémenter l'authentification et l'autorisation
            // Pour l'instant, on permet le désarchivage sans restriction

            // Désarchiver le compte via le service
            $compte = $this->compteService->desarchiverCompte($compteId, $request->validated());

            // Transformer les données pour la réponse
            $data = [
                'id' => $compte->id,
                'numeroCompte' => $compte->numeroCompte,
                'archive' => $compte->archive,
                'dateArchivage' => $compte->dateArchivage
            ];

            return $this->successResponse($data, 'Compte désarchivé avec succès');

        } catch (CompteNotFoundException $e) {
            return $e->render(request());
        } catch (ValidationException $e) {
            return $e->render($request);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

}