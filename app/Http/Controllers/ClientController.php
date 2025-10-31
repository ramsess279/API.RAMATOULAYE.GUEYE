<?php

namespace App\Http\Controllers;

use App\Services\ClientService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller pour la gestion des clients
 */
class ClientController extends Controller
{
    use ApiResponseTrait;

    private ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * @OA\Get(
     *     path="/clients/search",
     *     summary="Rechercher un client par téléphone ou CNI",
     *     description="Permet aux administrateurs de rechercher un client spécifique par son numéro de téléphone ou son numéro CNI",
     *     operationId="searchClient",
     *     tags={"Clients"},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Terme de recherche : numéro de téléphone ou numéro CNI",
     *         required=true,
     *         @OA\Schema(type="string", example="221701234567", description="Téléphone au format sénégalais ou CNI à 13 chiffres")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client trouvé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client trouvé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="nom", type="string", example="Diallo"),
     *                 @OA\Property(property="prenom", type="string", example="Amadou"),
     *                 @OA\Property(property="nomComplet", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="email", type="string", format="email", example="amadou.diallo@email.com"),
     *                 @OA\Property(property="telephone", type="string", example="+221701234567"),
     *                 @OA\Property(property="cni", type="string", example="1234567890123"),
     *                 @OA\Property(property="dateNaissance", type="string", format="date", example="1990-01-15"),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal"),
     *                 @OA\Property(property="genre", type="string", enum={"M", "F"}, example="M"),
     *                 @OA\Property(property="statut", type="string", enum={"actif", "inactif"}, example="actif"),
     *                 @OA\Property(property="dateDelivranceCni", type="string", format="date-time"),
     *                 @OA\Property(property="dateExpirationCni", type="string", format="date-time"),
     *                 @OA\Property(property="lieuDelivranceCni", type="string"),
     *                 @OA\Property(property="cniValide", type="boolean", example=true),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time"),
     *                 @OA\Property(property="derniereModification", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="metadata",
     *                     @OA\Property(property="version", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Aucun client trouvé avec ce numéro de téléphone ou CNI"),
     *             @OA\Property(
     *                 property="error",
     *                 @OA\Property(property="code", type="string", example="CLIENT_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Aucun client trouvé avec ce numéro de téléphone ou CNI")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Paramètre de recherche manquant ou invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le paramètre de recherche est requis"),
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
     *         response=500,
     *         description="Erreur interne du serveur"
     *     ),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function search(Request $request): JsonResponse
    {
        // Validation des paramètres
        $request->validate([
            'q' => 'required|string|min:10|max:13'
        ]);

        $searchTerm = $request->query('q');

        // Recherche du client
        $client = $this->clientService->rechercherClient($searchTerm);

        if (!$client) {
            return $this->errorResponse(
                'Aucun client trouvé avec ce numéro de téléphone ou CNI',
                404,
                [
                    'code' => 'CLIENT_NOT_FOUND',
                    'message' => 'Aucun client trouvé avec ce numéro de téléphone ou CNI',
                    'searchTerm' => $searchTerm
                ]
            );
        }

        // Transformation des données
        $data = $this->clientService->transformClientData($client);

        return $this->successResponse($data, 'Client trouvé avec succès');
    }
}