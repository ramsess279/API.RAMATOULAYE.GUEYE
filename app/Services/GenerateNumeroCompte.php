<?php
namespace App\Services;

use App\Models\Compte;

class GenerateNumeroCompte
{
    public function generate(): string
    {
        // Exemple simple : "CPT" + timestamp + random 3 chiffres
        return 'CPT' . time() . rand(100, 999);
    }
}
