<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\UuidTrait;

class Admin extends Model
{
    use HasFactory, UuidTrait;

    protected $fillable = [
        'user_id',
        'numero_employe',
        'niveau_acces',
        'permissions',
        'derniere_connexion'
    ];

    protected $casts = [
        'permissions' => 'array',
        'derniere_connexion' => 'datetime',
        'niveau_acces' => 'string'
    ];

    /**
     * Relation avec l'utilisateur de base
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Vérifie si l'admin a une permission spécifique
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    /**
     * Vérifie si l'admin est un super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->niveau_acces === 'super_admin';
    }
}
