<?php

namespace App\Services;

use App\Models\CompteModel;
use App\Models\Transaction;

class CalculSoldeService
{
    /**
     * Calcule le solde d'un compte basé sur ses transactions
     *
     * @param CompteModel $compte
     * @return float
     */
    public function calculerSolde(CompteModel $compte): float
    {
        $transactions = $compte->transactions;

        $solde = 0.0;

        foreach ($transactions as $transaction) {
            switch ($transaction->type) {
                case 'depot':
                    $solde += $transaction->montant;
                    break;
                case 'retrait':
                    $solde -= $transaction->montant;
                    break;
                case 'transfert':
                    // Pour les transferts, on considère que c'est un débit du compte
                    $solde -= $transaction->montant;
                    break;
            }
        }

        return $solde;
    }

    /**
     * Calcule le solde total de tous les comptes actifs
     *
     * @return float
     */
    public function calculerSoldeTotal(): float
    {
        $comptes = CompteModel::where('statut', 'actif')->get();
        $soldeTotal = 0.0;

        foreach ($comptes as $compte) {
            $soldeTotal += $this->calculerSolde($compte);
        }

        return $soldeTotal;
    }
}