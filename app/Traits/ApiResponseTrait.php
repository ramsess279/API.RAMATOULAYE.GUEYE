<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Retourne une réponse de succès standardisée
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Opération réussie', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Retourne une réponse d'erreur standardisée
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message = 'Une erreur est survenue', int $statusCode = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Retourne une réponse paginée standardisée
     *
     * @param mixed $data
     * @param int $currentPage
     * @param int $totalPages
     * @param int $totalItems
     * @param int $itemsPerPage
     * @param bool $hasNext
     * @param bool $hasPrevious
     * @param array $links
     * @param string $message
     * @return JsonResponse
     */
    protected function paginatedResponse(
        $data,
        int $currentPage,
        int $totalPages,
        int $totalItems,
        int $itemsPerPage,
        bool $hasNext,
        bool $hasPrevious,
        array $links = [],
        string $message = 'Données récupérées avec succès'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'itemsPerPage' => $itemsPerPage,
                'hasNext' => $hasNext,
                'hasPrevious' => $hasPrevious,
            ],
            'links' => $links,
        ], 200);
    }
}