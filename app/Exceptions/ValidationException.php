<?php

namespace App\Exceptions;

class ValidationException extends ApiException
{
    public function __construct(array $errors, string $message = 'Les données fournies sont invalides.')
    {
        parent::__construct($message, 422, $errors);
    }
}