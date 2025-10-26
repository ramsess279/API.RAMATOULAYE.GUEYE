<?php

namespace App\Exceptions;

class UnauthorizedAccessException extends ApiException
{
    public function __construct(string $message = 'Accès non autorisé à cette ressource.')
    {
        parent::__construct($message, 403);
    }
}