# 🔐 Guide d'Authentification OAuth2

## Vue d'ensemble

L'API utilise **OAuth2** avec Laravel Passport pour l'authentification. Tous les endpoints nécessitent un token d'accès valide, sauf les endpoints d'authentification eux-mêmes.

## 📋 Comptes de test

| Rôle | Email | Mot de passe | Permissions |
|------|-------|-------------|-------------|
| **Admin** | `admin@banque.com` | `admin123` | Toutes les opérations |
| **Client** | `amadou.diallo@email.com` | `client123` | Lecture seule |
| **Client** | `fatou.sarr@email.com` | `client123` | Lecture seule |
| **Client** | `moussa.ndiaye@email.com` | `client123` | Lecture seule |
| **Client** | `aminata.ba@email.com` | `client123` | Lecture seule |
| **Client** | `ibrahima.gueye@email.com` | `client123` | Lecture seule |

## 🚀 Obtenir un token d'accès

### Endpoint
```
POST /api/v1/auth/login
```

### Requête
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@banque.com",
    "password": "admin123"
  }'
```

### Réponse de succès
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": {
      "id": "uuid",
      "nom": "Admin",
      "prenom": "System",
      "email": "admin@banque.com",
      "role": "admin"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

## 🔄 Rafraîchir un token

### Endpoint
```
POST /api/v1/auth/refresh
```

### Requête
```bash
curl -X POST http://localhost:8000/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
  }'
```

## 🚪 Déconnexion

### Endpoint
```
POST /api/v1/auth/logout
```

### Requête
```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer VOTRE_ACCESS_TOKEN"
```

## 📡 Utiliser un token d'accès

Pour tous les autres endpoints, incluez le header d'autorisation :

```bash
curl -X GET http://localhost:8000/api/v1/comptes \
  -H "Authorization: Bearer VOTRE_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

## 👥 Rôles et permissions

### Administrateur (`role: admin`)
- ✅ Créer, modifier, supprimer des comptes
- ✅ Bloquer/débloquer des comptes
- ✅ Archiver des comptes
- ✅ Consulter tous les comptes
- ✅ Toutes les opérations CRUD

### Client (`role: client`)
- ✅ Consulter ses propres comptes
- ❌ Créer/modifier/supprimer des comptes
- ❌ Bloquer/débloquer des comptes
- ❌ Archiver des comptes

## ⚠️ Gestion des erreurs

### Token manquant ou invalide
```json
{
  "success": false,
  "message": "Token d'authentification manquant ou invalide",
  "error": {
    "code": "AUTHENTICATION_REQUIRED",
    "message": "Vous devez être connecté pour accéder à cette ressource"
  }
}
```

### Permissions insuffisantes
```json
{
  "success": false,
  "message": "Accès refusé - Permissions insuffisantes",
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "message": "Cette ressource nécessite le rôle 'admin', mais vous avez le rôle 'client'"
  }
}
```

## 🔒 Sécurité

- **Tokens JWT** signés avec RSA
- **Expiration automatique** : 1 heure pour les access tokens
- **Cookies sécurisés** : HTTPOnly et Secure en production
- **Middleware de validation** : Vérification des rôles et permissions
- **Logs d'audit** : Suivi de toutes les opérations d'authentification

## 📖 Documentation Swagger

Consultez la documentation interactive :
```
http://localhost:8000/api/documentation
```

L'onglet **"Authentification"** contient tous les détails des endpoints OAuth2.

---

**Note :** Tous les comptes de test sont créés automatiquement lors du seeding de la base de données avec `php artisan db:seed`.