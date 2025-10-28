<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompteNotFoundException extends \Exception
{
    protected $compteId;

    public function __construct(string $compteId = null)
    {
        $this->compteId = $compteId;
        parent::__construct("Le compte avec l'ID spécifié n'existe pas", 404);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'COMPTE_NOT_FOUND',
                'message' => 'Le compte avec l\'ID spécifié n\'existe pas',
                'details' => [
                    'compteId' => $this->compteId ?? 'non spécifié'
                ]
            ]
        ], 404);
    }
}