<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\GenerateNumeroCompte;
use App\Services\CalculSoldeService;

class CompteModel extends Model
{
      use HasFactory ;

    protected $table = "comptes";

    protected $fillable = [
        "numeroCompte",
        "type",
        "statut",
        "client_id",
        "devise"
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'compte_id');
    }

    /**
     * Relation avec le client propriétaire du compte
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Calcule et retourne le solde du compte basé sur les transactions
     *
     * @return float
     */
    public function getSolde(): float
    {
        $calculSoldeService = new CalculSoldeService();
        return $calculSoldeService->calculerSolde($this);
    }

    /**
     * Accesseur pour le solde (pour compatibilité avec l'ancien code)
     *
     * @return float
     */
    public function getSoldeAttribute(): float
    {
        return $this->getSolde();
    }

       protected static function booted()
    {
        static::creating(function ($compte) {
            if (empty($compte->numeroCompte)) {
                $generator = new GenerateNumeroCompte();
                $compte->numeroCompte = $generator->generate();
            }
        });

        // Supprimé l'événement created pour éviter les conflits avec les factories
        // Les transactions seront générées manuellement dans les seeders
    }

}
