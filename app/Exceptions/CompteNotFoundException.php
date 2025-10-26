<?php

namespace App\Exceptions;

class CompteNotFoundException extends ApiException
{
    public function __construct(string $numeroCompte = null)
    {
        $message = $numeroCompte
            ? "Le compte avec le numéro '{$numeroCompte}' n'a pas été trouvé."
            : "Le compte demandé n'a pas été trouvé.";

        parent::__construct($message, 404);
    }
}