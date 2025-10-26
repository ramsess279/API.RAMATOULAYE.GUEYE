# Documentation API - Gestion Bancaire

## Vue d'ensemble

Cette API permet de gérer les comptes bancaires dans le système de gestion bancaire. Elle est organisée en versions pour assurer la compatibilité ascendante.

**Base URL:** `http://api.ramatoulaye.gueye.com/api/v1`

## Authentification

Toutes les requêtes nécessitent une authentification via Bearer Token.

**Header requis:**
```
Authorization: Bearer {token}
```

## Endpoints

### 1. Lister tous les comptes

**Endpoint:** `GET /api/v1/comptes`

**Description:** Récupère la liste paginée de tous les comptes bancaires selon les permissions de l'utilisateur.

- **Admin:** Peut voir tous les comptes
- **Client:** Peut voir uniquement ses propres comptes

**Paramètres de requête:**

| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `page` | integer | 1 | Numéro de page |
| `limit` | integer | 10 | Nombre d'éléments par page (max: 100) |
| `type` | string | null | Filtrer par type (`epargne`, `cheque`) |
| `statut` | string | null | Filtrer par statut (`actif`, `bloque`, `ferme`) |
| `search` | string | null | Recherche par titulaire ou numéro de compte |
| `sort` | string | `dateCreation` | Tri (`dateCreation`, `solde`, `titulaire`) |
| `order` | string | `desc` | Ordre (`asc`, `desc`) |

**Headers:**

| Header | Valeur | Description |
|--------|--------|-------------|
| `Authorization` | `Bearer {token}` | Token d'authentification |
| `Accept` | `application/json` | Type de contenu accepté |

**Exemple de requête:**
```
GET /api/v1/comptes?page=1&limit=10&type=epargne&statut=actif&sort=dateCreation&order=desc
Host: api.ramatoulaye.gueye.com
Authorization: Bearer {token}
Accept: application/json
```

**Réponse de succès (200):**

```json
{
  "success": true,
  "message": "Comptes récupérés avec succès",
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "numeroCompte": "C00123456",
      "titulaire": "Amadou Diallo",
      "type": "epargne",
      "solde": 1250000,
      "devise": "FCFA",
      "dateCreation": "2023-03-15T00:00:00Z",
      "statut": "bloque",
      "motifBlocage": "Inactivité de 30+ jours",
      "metadata": {
        "derniereModification": "2023-06-10T14:30:00Z",
        "version": 1
      }
    }
  ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 3,
    "totalItems": 25,
    "itemsPerPage": 10,
    "hasNext": true,
    "hasPrevious": false
  },
  "links": {
    "self": "/api/v1/comptes?page=1&limit=10",
    "next": "/api/v1/comptes?page=2&limit=10",
    "first": "/api/v1/comptes?page=1&limit=10",
    "last": "/api/v1/comptes?page=3&limit=10"
  }
}
```

**Réponses d'erreur:**

- **400 Bad Request:** Paramètres invalides
```json
{
  "success": false,
  "message": "Les données fournies sont invalides.",
  "errors": {
    "page": ["Le numéro de page doit être un entier positif."]
  }
}
```

- **401 Unauthorized:** Token manquant ou invalide
```json
{
  "success": false,
  "message": "Non autorisé."
}
```

- **403 Forbidden:** Accès refusé
```json
{
  "success": false,
  "message": "Accès non autorisé à cette ressource."
}
```

- **422 Unprocessable Entity:** Données de validation invalides
```json
{
  "success": false,
  "message": "Les données fournies sont invalides.",
  "errors": {
    "type": ["Le type sélectionné n'est pas valide."]
  }
}
```

## Codes de statut HTTP

| Code | Description |
|------|-------------|
| 200 | Succès |
| 400 | Mauvaise requête |
| 401 | Non autorisé |
| 403 | Interdit |
| 404 | Non trouvé |
| 422 | Entité non traitable |
| 429 | Trop de requêtes (Rate limiting) |
| 500 | Erreur interne du serveur |

## Rate Limiting

L'API applique un rate limiting pour prévenir les abus. Les utilisateurs qui atteignent la limite sont automatiquement enregistrés dans les logs.

## CORS

L'API supporte les requêtes cross-origin depuis n'importe quelle origine.

## Format des données

Toutes les réponses suivent le format standardisé :

```json
{
  "success": boolean,
  "message": string,
  "data": mixed,
  "pagination": object (optionnel),
  "links": object (optionnel),
  "errors": object (optionnel)
}
```

## Notes importantes

- Seuls les comptes non supprimés (statut ≠ 'ferme') sont retournés
- Les soldes sont calculés dynamiquement à partir des transactions
- La recherche fonctionne sur le nom/prénom du titulaire et le numéro de compte
- La pagination est obligatoire pour les listes importantes